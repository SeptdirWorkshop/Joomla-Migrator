<?php
/*
 * @package     Joomla Migrator Plugin
 * @subpackage  plg_system_migrator
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\System\Migrator\Console;

\defined('_JEXEC') or die;

use Joomla\Component\Categories\Administrator\Model\CategoryModel;
use Joomla\Component\Tags\Administrator\Model\TagModel;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportTrait;
use Joomla\Utilities\ArrayHelper;

class ImportJoomlaCommand extends AbstractCommand
{
	use DatabaseAwareTrait;
	use ImportTrait;

	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'migrator:import:joomla';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Import: Joomla core components';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'importContentCategories',
		'importTags',
		'importContentArticles',
	];

	/**
	 * Method to import categories.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importContentCategories(): void
	{
		$this->ioStyle->title('Migrator Import: Content Categories');

		$data = $this->getData('com_content.categories');
		if (count($data) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$this->ioStyle->text('Import data');
		$this->loadSuperUserIdentity();
		$result = [];
		$this->progressbarStart(count($data));
		$db = $this->getDatabase();
		foreach ($data as $path => $datum)
		{
			$query = $db->createQuery()
				->select('id')
				->from('#__categories')
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
				->where($db->quoteName('path') . ' = :path')
				->bind(':path', $path);
			$id    = $db->setQuery($query)->loadResult();
			if (empty($id))
			{
				$id = 0;
			}

			$datum['id']        = $id;
			$datum['extension'] = 'com_content';
			$datum['parent_id'] = (!empty($result[$datum['parent']])) ? $result[$datum['parent']] : 1;
			unset($datum['parent']);

			/** @var CategoryModel $model */
			$model = $this->getComponentModel('com_categories', 'Category');
			if ($model->save($datum) === false)
			{
				throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
			}
			$id = $model->getState('category.id');

			$result[$path] = $id;

			$db->disconnect();
			$this->progressbarAdvance();
		}

		$this->progressbarFinish();
	}

	/**
	 * Method to import tags.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importTags(): void
	{
		$this->ioStyle->title('Migrator Import: Tags');

		$data = $this->getData('com_tags.tags');
		if (count($data) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$this->ioStyle->text('Import data');
		$this->loadSuperUserIdentity();
		$result = [];
		$this->progressbarStart(count($data));
		$db = $this->getDatabase();
		foreach ($data as $path => $datum)
		{
			$query = $db->createQuery()
				->select('id')
				->from('#__tags')
				->where($db->quoteName('path') . ' = :path')
				->bind(':path', $path);
			$id    = $db->setQuery($query)->loadResult();
			if (empty($id))
			{
				$id = 0;
			}

			$datum['id']        = $id;
			$datum['parent_id'] = (!empty($result[$datum['parent']])) ? $result[$datum['parent']] : 0;
			unset($datum['parent']);

			/** @var TagModel $model */
			$model = $this->getComponentModel('com_tags', 'Tag');
			if ($model->save($datum) === false)
			{
				throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
			}
			$id = $model->getState('tag.id');

			$result[$path] = $id;

			$db->disconnect();
			$this->progressbarAdvance();
		}

		$this->progressbarFinish();
	}

	/**
	 * Method to import tags.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importContentArticles(): void
	{
		$this->ioStyle->title('Migrator Import: Content Articles');
		$this->loadSuperUserIdentity();
		if (!\defined('JPATH_COMPONENT'))
		{
			\define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_content');
		}

		$data = $this->getData('com_content.articles');
		if (count($data) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$this->ioStyle->text('Get categories mapping');
		$this->progressbarStart();
		$db               = $this->getDatabase();
		$categories_paths = array_unique(ArrayHelper::getColumn($data, 'category'));
		$query            = $db->createQuery()
			->select(['id', 'path'])
			->from($db->quoteName('#__categories'))
			->whereIn($db->quoteName('path'), $categories_paths, ParameterType::STRING);
		$categories       = $db->setQuery($query)->loadAssocList('path', 'id');
		$this->progressbarFinish();

		$this->ioStyle->text('Get tags mapping');
		$this->progressbarStart();
		$query = $db->createQuery()
			->select(['path', 'id'])
			->from($db->quoteName('#__tags'))
			->where($db->quoteName('id') . ' > 1');
		$tags  = $db->setQuery($query)->loadAssocList('path', 'id');
		$this->progressbarFinish();

		$this->ioStyle->text('Import data');
		$this->loadSuperUserIdentity();
		$this->progressbarStart(count($data));
		foreach ($data as $datum)
		{
			$catid = (!empty($categories[$datum['category']])) ? (int) $categories[$datum['category']] : 0;
			if (empty($catid))
			{
				throw new \Exception('Category `' . $datum['category'] . '` not found', 400);
			}

			$query = $db->createQuery()
				->select('id')
				->from('#__content')
				->where($db->quoteName('alias') . ' = :alias')
				->where($db->quoteName('catid') . ' = :catid')
				->bind(':alias', $datum['alias'])
				->bind(':catid', $catid);
			$id    = $db->setQuery($query)->loadResult();
			if (empty($id))
			{
				$id = 0;
			}

			$datum['id']    = $id;
			$datum['catid'] = $catid;
			unset($datum['category']);

			$item_tags = [];
			if (!empty($datum['tags']))
			{
				foreach ($datum['tags'] as $tag)
				{
					if (!empty($tags[$tag]))
					{
						$item_tags[] = (int) $tags[$tag];
					}
				}
			}
			$datum['tags'] = $item_tags;

			$datum['articletext'] = $datum['introtext'];
			if (!empty($datum['fulltext']))
			{
				$datum['articletext'] .= PHP_EOL . '<hr id="system-readmore">' . PHP_EOL . $datum['fulltext'];
			}
			unset($datum['introtext']);
			unset($datum['fulltext']);

			/** @var TagModel $model */
			$model = $this->getComponentModel('com_content', 'Article');
			if ($model->save($datum) === false)
			{
				throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
			}


			$this->progressbarAdvance();
		}

		$this->progressbarFinish();
	}
}