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

namespace Joomla\Plugin\System\Migrator\Console\Joomla\Export;

\defined('_JEXEC') or die;

use Joomla\Database\ParameterType;
use Joomla\Plugin\System\Migrator\Console\AbstractCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\ExportTrait;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class ExportJoomlaCommand extends AbstractCommand
{
	use ExportTrait;

	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'migrator:export:joomla';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Export: Joomla core components';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'exportContentCategories',
		'exportContentArticles',
		'exportTags',
	];

	/**
	 * Method to export categories.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportContentCategories(): void
	{
		$this->ioStyle->title('Migrator Export: Content Categories');

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
			->order('lft asc');
		$items = $db->setQuery($query)->loadObjectList('id');
		$this->progressbarFinish();

		if (count($items) === 0)
		{
			$this->ioStyle->info('Nothing to export.');

			return;
		}

		$result = [];
		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($items));
		foreach ($items as $item)
		{
			$parent              = ((int) $item->parent_id > 1 && isset($items[$item->parent_id]))
				? $items[$item->parent_id]->path : null;
			$result[$item->path] = [
				'title'       => $item->title,
				'alias'       => $item->alias,
				'parent'      => $parent,
				'description' => $item->description,
				'published'   => $item->published,
				'access'      => $item->access,
				'params'      => (new Registry($item->params))->toArray(),
				'metadesc'    => $item->metadesc,
				'metadata'    => (new Registry($item->metadata))->toArray(),
				'hits'        => $item->hits,
				'language'    => $item->language,
			];

			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		$this->safeData('com_content.categories', $result);
	}

	/**
	 * Method to export content articles.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportContentArticles(): void
	{
		$this->ioStyle->title('Migrator Export: Content Articles');

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db = $this->getDonorDatabase();

		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__content', 'a'))
			->order('id asc');
		$items = $db->setQuery($query)->loadObjectList();
		$this->progressbarFinish();

		if (count($items) === 0)
		{
			$this->ioStyle->info('Nothing to export.');

			return;
		}

		$this->ioStyle->text('Get categories mapping');
		$this->progressbarStart();
		$categories_ids = array_unique(ArrayHelper::toInteger(ArrayHelper::getColumn($items, 'catid')));
		$query          = $db->createQuery()
			->select(['id', 'path'])
			->from($db->quoteName('#__categories'))
			->whereIn($db->quoteName('id'), $categories_ids);
		$categories     = $db->setQuery($query)->loadAssocList('id', 'path');
		$this->progressbarFinish();

		$this->ioStyle->text('Get tags mapping');
		$this->progressbarStart();
		$query = $db->createQuery()
			->select(['id', 'path'])
			->from($db->quoteName('#__tags'))
			->where($db->quoteName('id') . ' > 1');
		$tags  = $db->setQuery($query)->loadAssocList('id', 'path');
		$this->progressbarFinish();

		$result = [];
		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($items));
		foreach ($items as $item)
		{
			$query         = $db->createQuery()
				->select('tag_id')
				->from($db->quoteName('#__contentitem_tag_map'))
				->where($db->quoteName('content_item_id') . ' =:pk')
				->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'))
				->bind(':pk', $item->id, ParameterType::INTEGER);
			$item_tags_ids = $db->setQuery($query)->loadColumn();
			$item_tags     = [];
			foreach ($item_tags_ids as $tag_id)
			{
				$item_tags[$tag_id] = $tags[$tag_id];
			}

			$item_category = (!isset($categories[$item->catid])) ? 'NOT_FOUND' : $categories[$item->catid];

			$path          = $item_category . '|' . $item->alias;
			$result[$path] = [
				'title'        => $item->title,
				'alias'        => $item->alias,
				'introtext'    => $item->introtext,
				'fulltext'     => $item->fulltext,
				'state'        => $item->state,
				'category'     => $item_category,
				'created'      => $item->created,
				'publish_up'   => $item->publish_up,
				'publish_down' => $item->publish_down,
				'images'       => (new Registry($item->images))->toArray(),
				'urls'         => (new Registry($item->urls))->toArray(),
				'attribs'      => (new Registry($item->attribs))->toArray(),
				'metakey'      => $item->metakey,
				'metadesc'     => $item->metadesc,
				'access'       => $item->access,
				'hits'         => $item->hits,
				'metadata'     => (new Registry($item->metadata))->toArray(),
				'featured'     => $item->featured,
				'language'     => $item->language,
				'tags'         => $item_tags,
			];


		}
		$this->progressbarFinish();

		$this->safeData('com_content.articles', $result);
	}

	/**
	 * Method to export tags.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportTags(): void
	{
		$this->ioStyle->title('Migrator Export: Tags');

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__tags'))
			->where($db->quoteName('id') . ' > 1')
			->order('lft asc');
		$items = $db->setQuery($query)->loadObjectList('id');
		$this->progressbarFinish();

		if (count($items) === 0)
		{
			$this->ioStyle->info('Nothing to export.');

			return;
		}

		$result = [];
		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($items));
		foreach ($items as $item)
		{
			$parent              = ((int) $item->parent_id > 1 && isset($items[$item->parent_id]))
				? $items[$item->parent_id]->path : null;
			$result[$item->path] = [
				'title'       => $item->title,
				'alias'       => $item->alias,
				'parent'      => $parent,
				'description' => $item->description,
				'published'   => $item->published,
				'access'      => $item->access,
				'params'      => (new Registry($item->params))->toArray(),
				'metadesc'    => $item->metadesc,
				'metadata'    => (new Registry($item->metadata))->toArray(),
				'images'      => (new Registry($item->images))->toArray(),
				'hits'        => $item->hits,
				'language'    => $item->language,
			];

			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		$this->safeData('com_tags.tags', $result);
	}
}