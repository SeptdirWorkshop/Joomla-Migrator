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

namespace Joomla\Plugin\System\Migrator\Console\RadicalMart\Import\JoomShopping;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Component\RadicalMart\Administrator\Model\CategoryModel;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Plugin\System\Migrator\Console\AbstractCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportRadicalMartTrait;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportTrait;

class ImportRadicalMartJoomShoppingCommand extends AbstractCommand
{
	use DatabaseAwareTrait;
	use ImportTrait;
	use ImportRadicalMartTrait;
	use MVCFactoryAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'migrator:import:radicalmart:joomshopping';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Import: RadicalMart from JoomShopping';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'importCategories',
		'importManufacturers',
	];

	/**
	 * Method to import categories.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importCategories(): void
	{
		$this->ioStyle->title('Migrator Import: RadicalMart Categories from JoomShopping Categories');

		$data = $this->getData('com_joomshopping.categories');
		if (count($data) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$this->ioStyle->text('Import data');
		$this->loadSuperUserIdentity();
		$this->progressbarStart(count($data));

		$multilanguage = $this->getMultilanguage();
		foreach ($data as $datum)
		{
			$selector = $this->getMigratorSelector('category', $datum['id']);
			$id       = $this->findItemId('category', $selector);
			if (empty($id))
			{
				$id = 0;
			}

			$parent_id = $this->findItemId('category', $this->getMigratorSelector('category', $datum['parent_id']));
			if (empty($parent_id))
			{
				$parent_id = 1;
			}

			$this->setMultilanguageDatum($datum, ['title', 'alias', 'introtext', 'fulltext',
				'meta_title', 'meta_description']);

			$save = [
				'id'        => $id,
				'parent_id' => $parent_id,
				'title'     => $datum['title'],
				'alias'     => $datum['alias'],
				'type'      => 'category',
				'introtext' => $datum['introtext'],
				'fulltext'  => $datum['fulltext'],
				'media'     => [

				],
				'state'     => $datum['state'],
				'show'      => 1,
				'params'    => [
					'seo_category_title'       => $datum['meta_title'],
					'seo_category_description' => $datum['meta_description'],
				],
				'plugins'   => [
					'migrator_selector' => $selector,
				],
				'language'  => '*'
			];

			if ($multilanguage && !empty($datum['translation']))
			{
				$save['plugins']['translation'] = [];
				foreach ($datum['translation'] as $lang => $translation)
				{
					$save['plugins']['translation'][$lang] = [
						'title'     => $translation['title'],
						'introtext' => $translation['introtext'],
						'fulltext'  => $translation['fulltext'],
						'params'    => [
							'seo_category_title'       => $translation['meta_title'],
							'seo_category_description' => $translation['meta_description'],
						]
					];
				}
			}

			/** @var CategoryModel $model */
			$model = $this->getComponentModel('com_radicalmart', 'Category');
			$model->setState('category.id', $id);
			$model->setState('save.task', 'save');

			$result = $model->save($save);
			if ($result === false)
			{
				throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
			}

			$this->_mapping['category'][$selector] = $result;

			$this->progressbarAdvance();
		}

		$this->progressbarFinish();
	}

	/**
	 * Method to import categories.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importManufacturers(): void
	{
		$this->ioStyle->title('Migrator Import: RadicalMart Categories from JoomShopping Manufacturers');

		$data = $this->getData('com_joomshopping.manufacturers');
		if (count($data) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$this->ioStyle->text('Import data');
		$this->loadSuperUserIdentity();
		$this->progressbarStart(count($data));

		$multilanguage = $this->getMultilanguage();
		foreach ($data as $datum)
		{
			$selector = $this->getMigratorSelector('manufacturer', $datum['id']);
			$id       = $this->findItemId('category', $selector);
			if (empty($id))
			{
				$id = 0;
			}

			$this->setMultilanguageDatum($datum, ['title', 'alias', 'introtext', 'fulltext',
				'meta_title', 'meta_description']);

			$save = [
				'id'        => $id,
				'parent_id' => 1,
				'title'     => $datum['title'],
				'alias'     => $datum['alias'],
				'type'      => 'manufacturer',
				'introtext' => $datum['introtext'],
				'fulltext'  => $datum['fulltext'],
				'media'     => [

				],
				'state'     => $datum['state'],
				'show'      => 0,
				'params'    => [
					'seo_category_title'       => $datum['meta_title'],
					'seo_category_description' => $datum['meta_description'],
				],
				'plugins'   => [
					'migrator_selector' => $selector,
				],
				'language'  => '*'
			];

			if ($multilanguage && !empty($datum['translation']))
			{
				$save['plugins']['translation'] = [];
				foreach ($datum['translation'] as $lang => $translation)
				{
					$save['plugins']['translation'][$lang] = [
						'title'     => $translation['title'],
						'introtext' => $translation['introtext'],
						'fulltext'  => $translation['fulltext'],
						'params'    => [
							'seo_category_title'       => $translation['meta_title'],
							'seo_category_description' => $translation['meta_description'],
						]
					];
				}
			}

			/** @var CategoryModel $model */
			$model = $this->getComponentModel('com_radicalmart', 'Category');
			$model->setState('category.id', $id);
			$model->setState('save.task', 'save');

			$result = $model->save($save);
			if ($result === false)
			{
				throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
			}

			$this->_mapping['category'][$selector] = $result;

			$this->progressbarAdvance();
		}

		$this->progressbarFinish();
	}

	/**
	 * Method to get migrator selector.
	 *
	 * @param   string  $type  Item type.
	 * @param   int     $id    Item id.
	 *
	 * @return string Migrator item selector.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getMigratorSelector(string $type, int $id): string
	{
		return 'joomshopping_' . $type . '_' . $id;
	}

	/**
	 * Method to set default translation data to datum root.
	 *
	 * @param   array  $datum  Item datum.
	 * @param   array  $keys   Merge keys.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function setMultilanguageDatum(array &$datum, array $keys): void
	{
		$default = $this->getMultilanguage();
		if (empty($default))
		{
			return;
		}

		foreach ($keys as $key)
		{
			if (!empty($datum['translation'][$default][$key]))
			{
				$datum[$key] = $datum['translation'][$default][$key];
			}
		}
	}
}