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

namespace Joomla\Plugin\System\Migrator\Console\JoomShopping;

\defined('_JEXEC') or die;

use Joomla\Plugin\System\Migrator\Console\AbstractCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\ExportTrait;

class ExportJoomShoppingCommand extends AbstractCommand
{
	use ExportTrait;

	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'migrator:export:joomshopping';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Export: JoomShopping';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'exportJoomShoppingCategories',
	];

	/**
	 * Tables languages.
	 *
	 * @var array|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected ?array $_languages = null;

	/**
	 * Method to export categories.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportJoomShoppingCategories(): void
	{
		$this->ioStyle->title('Migrator Export: JoomShopping Categories');

		$this->ioStyle->text('Get languages');
		$this->progressbarStart();
		$languages = $this->getLanguages('#__jshopping_categories');
		$this->progressbarFinish();

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__jshopping_categories'))
			->order('category_id ASC');
		$rows  = $db->setQuery($query)->loadObjectList();
		$this->progressbarFinish();

		$this->ioStyle->text('Create NestedSet');
		$this->progressbarStart();
		$categories = $this->buildCategoriesNestedSet($rows);
		$this->progressbarFinish();

		$result = [];
		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($rows));
		$mapping = [
			'name'              => 'title',
			'alias'             => 'alias',
			'short_description' => 'introtext',
			'description'       => 'fulltext',
			'meta_title'        => 'meta_title',
			'meta_description'  => 'meta_description',
			'meta_keywords'     => 'meta_keywords',
		];
		foreach ($categories as $source)
		{
			$item = [
				'id'               => (int) $source->category_id,
				'parent_id'        => (int) $source->category_parent_id,
				'state'            => (int) $source->category_publish,
				'access'           => (int) $source->access,
				'lft'              => $source->lft,
				'rgt'              => $source->rgt,
				'title'            => '',
				'alias'            => '',
				'introtext'        => '',
				'fulltext'         => '',
				'meta_title'       => '',
				'meta_description' => '',
				'meta_keywords'    => '',
				'translations'     => [],
			];

			foreach ($languages as $language)
			{
				if (!isset($item['translations'][$language]))
				{
					$item['translations'][$language] = [];
				}

				foreach ($mapping as $from => $to)
				{
					$from_key   = $from . '_' . $language;
					$from_value = (property_exists($source, $from_key)) ? $source->{$from_key} : '';

					if (empty($item[$to]))
					{
						$item[$to] = $from_value;
					}

					$item['translations'][$language][$to] = $from_value;
				}
			}

			$result[$item['id']] = $item;

			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		$this->safeData('com_joomshopping.categories', $result);
	}

	/**
	 * Method to get table languages.
	 *
	 * @param   string|null  $table  Table name.
	 *
	 * @throws \Throwable
	 *
	 * @return array Table languages array.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getLanguages(?string $table = null): array
	{
		if ($table === null)
		{
			return [];
		}

		if ($this->_languages === null)
		{
			$this->_languages = [];
		}

		if (isset($this->_languages[$table]))
		{
			return $this->_languages[$table];
		}

		$db      = $this->getDonorDatabase();
		$columns = array_keys($db->getTableColumns($table));
		$result  = [];
		foreach ($columns as $column)
		{
			if (!str_starts_with($column, 'name_'))
			{
				continue;
			}

			$result[] = str_replace('name_', '', $column);
		}

		$this->_languages[$table] = $result;

		return $result;
	}

	/**
	 * Method to build Categories NestedSet
	 *
	 * @param   array  $categories  Source categories data.
	 *
	 * @return array NestedSet categories data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function buildCategoriesNestedSet(array $categories): array
	{
		$items    = [];
		$children = [];

		foreach ($categories as $category)
		{
			$id       = (int) $category->category_id;
			$parentId = (int) $category->category_parent_id;

			$items[$id]            = $category;
			$children[$parentId][] = $id;
		}

		foreach ($children as &$ids)
		{
			usort($ids, function ($a, $b) use ($items) {
				$result = (int) $items[$a]->ordering <=> (int) $items[$b]->ordering;

				return $result ?: $a <=> $b;
			});
		}
		unset($ids);

		$counter = 1;
		$result  = [];
		$walk    = function (int $id) use (&$walk, &$counter, &$result, $items, $children) {
			$category = $items[$id];

			$category->lft = $counter++;

			foreach ($children[$id] ?? [] as $childId)
			{
				$walk($childId);
			}

			$category->rgt = $counter++;

			$result[] = $category;
		};

		foreach ($children[0] ?? [] as $id)
		{
			$walk($id);
		}

		usort($result, fn($a, $b) => $a->lft <=> $b->lft);

		return $result;
	}
}