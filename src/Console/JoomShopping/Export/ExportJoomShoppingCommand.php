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

namespace Joomla\Plugin\System\Migrator\Console\JoomShopping\Export;

\defined('_JEXEC') or die;

use Joomla\Database\ParameterType;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
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
		'exportJoomShoppingManufacturers',
		'exportJoomShoppingAttributes',
		'exportJoomShoppingCurrencies',
		'exportJoomShoppingProducts',
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
	 * @throws \Throwable
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

		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($rows));
		$result              = [];
		$translation_mapping = [
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
				'image'            => (!empty($source->category_image))
					? 'components/com_jshopping/files/img_categories/' . $source->category_image : '',
				'title'            => '',
				'alias'            => '',
				'introtext'        => '',
				'fulltext'         => '',
				'meta_title'       => '',
				'meta_description' => '',
				'meta_keywords'    => '',
				'translation'      => [],
			];

			$this->setItemTranslationData($item, $source, $languages, $translation_mapping);

			$result[$item['id']] = $item;

			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		$this->safeData('com_joomshopping.categories', $result);
	}

	/**
	 * Method to export manufacturers.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportJoomShoppingManufacturers(): void
	{
		$this->ioStyle->title('Migrator Export: JoomShopping Manufacturers');

		$this->ioStyle->text('Get languages');
		$this->progressbarStart();
		$languages = $this->getLanguages('#__jshopping_manufacturers');
		$this->progressbarFinish();

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__jshopping_manufacturers'))
			->order('ordering ASC');
		$rows  = $db->setQuery($query)->loadObjectList();
		$this->progressbarFinish();

		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($rows));
		$result              = [];
		$translation_mapping = [
			'name'              => 'title',
			'alias'             => 'alias',
			'short_description' => 'introtext',
			'description'       => 'fulltext',
			'meta_title'        => 'meta_title',
			'meta_description'  => 'meta_description',
			'meta_keywords'     => 'meta_keywords',
		];
		foreach ($rows as $source)
		{
			$item = [
				'id'               => (int) $source->manufacturer_id,
				'state'            => (int) $source->manufacturer_publish,
				'ordering'         => (int) $source->ordering,
				'image'            => (!empty($source->logo))
					? 'components/com_jshopping/files/img_manufs/' . $source->manufacturer_logo : '',
				'title'            => '',
				'alias'            => '',
				'introtext'        => '',
				'fulltext'         => '',
				'meta_title'       => '',
				'meta_description' => '',
				'meta_keywords'    => '',
				'translation'      => [],
			];

			$this->setItemTranslationData($item, $source, $languages, $translation_mapping);

			$result[$item['id']] = $item;

			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		$this->safeData('com_joomshopping.manufacturers', $result);
	}

	/**
	 * Method to export attributes.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportJoomShoppingAttributes(): void
	{
		$this->ioStyle->title('Migrator Export: JoomShopping Attributes');

		$this->ioStyle->text('Get languages');
		$this->progressbarStart();
		$languages = $this->getLanguages('#__jshopping_attr');
		$this->progressbarFinish();

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__jshopping_attr'))
			->order('attr_id ASC');
		$rows  = $db->setQuery($query)->loadObjectList();
		$this->progressbarFinish();

		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($rows));
		$result                     = [];
		$translation_mapping        = [
			'name'        => 'title',
			'description' => 'description',
		];
		$option_translation_mapping = [
			'name' => 'text',
		];
		foreach ($rows as $source)
		{
			$item = [
				'id'          => (int) $source->attr_id,
				'title'       => '',
				'alias'       => '',
				'description' => '',
				'translation' => [],
				'options'     => [],
			];
			$this->setItemTranslationData($item, $source, $languages, $translation_mapping);

			$query   = $db->createQuery()
				->select('*')
				->from($db->quoteName('#__jshopping_attr_values'))
				->where('attr_id = :attr_id')
				->bind(':attr_id', $item['id'], ParameterType::INTEGER)
				->order('value_ordering ASC');
			$options = $db->setQuery($query)->loadObjectList();

			foreach ($options as $option_source)
			{
				$option = [
					'value'    => (int) $option_source->value_id,
					'text'     => '',
					'ordering' => (int) $option_source->value_ordering,
					'image'    => (!empty($option_source->image))
						? 'components/com_jshopping/files/img_attributes/' . $option_source->image : '',
				];

				$this->setItemTranslationData($option, $option_source, $languages, $option_translation_mapping);

				$item['options'][] = $option;
			}

			$result[$item['id']] = $item;

			$this->progressbarAdvance();
		}

		$this->progressbarFinish();

		$this->safeData('com_joomshopping.attributes', $result);
	}

	/**
	 * Method to export currencies.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportJoomShoppingCurrencies(): void
	{
		$this->ioStyle->title('Migrator Export: JoomShopping Currencies');

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__jshopping_currencies'))
			->where($db->quoteName('currency_publish') . ' = 1');
		$rows  = $db->setQuery($query)->loadObjectList();

		$db        = $this->getDonorDatabase();
		$query     = $db->createQuery()
			->select(['currency_id', 'language'])
			->from($db->quoteName('#__jshopping_currencies_to_language'));
		$languages = $db->setQuery($query)->loadAssocList('currency_id', 'language');

		$this->progressbarFinish();

		$this->ioStyle->text('Prepare data');
		$this->progressbarStart(count($rows));
		$result = [];
		foreach ($rows as $source)
		{
			$item = [
				'id'       => (int) $source->currency_id,
				'title'    => $source->currency_name,
				'code'     => $source->currency_code,
				'rate'     => (float) $source->currency_value,
				'language' => (!empty($languages[$source->currency_id])) ? $languages[$source->currency_id] : '*',
			];

			$result[$item['id']] = $item;
		}
		$this->progressbarFinish();

		$this->safeData('com_joomshopping.currencies', $result);
	}

	/**
	 * Method to export products.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportJoomShoppingProducts(): void
	{
		$this->ioStyle->title('Migrator Export: JoomShopping Products');

		$this->ioStyle->text('Get languages');
		$this->progressbarStart();
		$languages = $this->getLanguages('#__jshopping_products');
		$this->progressbarFinish();

		$folder = Path::clean(JPATH_ROOT . '/administrator/migrator');
		if (is_dir($folder))
		{
			$this->ioStyle->text('Clean files');
			$this->progressbarStart();
			$files = Folder::files($folder, 'com_joomshopping.products', false, true);
			$count = count($files);
			if ($count > 0)
			{
				$this->progressbar->setMaxSteps($count);
				foreach ($files as $file)
				{
					File::delete($file);
					$this->progressbarAdvance();
				}
			}
			$this->progressbarFinish();
		}

		$this->ioStyle->text('Get total');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('COUNT(product_id)')
			->from($db->quoteName('#__jshopping_products'))
			->where($db->quoteName('parent_id') . ' = 0');
		$total = $db->setQuery($query)->loadResult();
		$this->progressbarFinish();

		$limit = 100;
		$last  = 0;
		$steps = ceil($total / $limit);
		for ($s = 1; $s <= $steps; $s++)
		{

			$progress = ' (' . $s . '/' . $steps . ')';
			$this->ioStyle->text('Get items' . $progress);
			$this->progressbarStart();
			$db    = $this->getDonorDatabase();
			$query = $db->createQuery()
				->select('*')
				->from($db->quoteName('#__jshopping_products'))
				->where($db->quoteName('parent_id') . ' = 0')
				->where($db->quoteName('product_id') . ' > :last')
				->bind(':last', $last, ParameterType::INTEGER)
				->order('product_id ASC');
			$rows  = $db->setQuery($query, 0, $limit)->loadObjectList();
			$this->progressbarFinish();

			$this->ioStyle->text('Prepare data' . $progress);
			$this->progressbarStart(count($rows));
			$result              = [];
			$translation_mapping = [
				'name'              => 'title',
				'alias'             => 'alias',
				'short_description' => 'introtext',
				'description'       => 'fulltext',
				'meta_title'        => 'meta_title',
				'meta_description'  => 'meta_description',
				'meta_keywords'     => 'meta_keywords',
			];
			$attributes          = null;
			foreach ($rows as $source)
			{
				$last = (int) $source->product_id;
				$this->progressbarAdvance();

				$item = [
					'id'           => (int) $source->product_id,
					'state'        => (int) $source->product_publish,
					'code'         => $source->manufacturer_code,
					'manufacturer' => (int) $source->product_manufacturer_id,
					'categories'   => [],
					'price'        => (float) $source->product_price,
					'currency'     => (int) $source->currency_id,
					'discount'     => 0,

					'image'  => (!empty($source->image))
						? 'components/com_jshopping/files/img_products/' . $source->image : '',
					'images' => [],

					'title'            => '',
					'alias'            => '',
					'introtext'        => '',
					'fulltext'         => '',
					'meta_title'       => '',
					'meta_description' => '',
					'meta_keywords'    => '',

					'variants'    => [],
					'translation' => [],
				];
				$this->setItemTranslationData($item, $source, $languages, $translation_mapping);

				$query              = $db->createQuery()
					->select('category_id')
					->from($db->quoteName('#__jshopping_products_to_categories'))
					->where($db->quoteName('product_id') . ' = :product_id')
					->bind(':product_id', $source->product_id, ParameterType::INTEGER);
				$item['categories'] = $db->setQuery($query)->loadColumn();

				$query          = $db->createQuery()
					->select('image_name')
					->from($db->quoteName('#__jshopping_products_images'))
					->where($db->quoteName('product_id') . ' = :product_id')
					->bind(':product_id', $source->product_id, ParameterType::INTEGER)
					->order('ordering ASC');
				$item['images'] = $db->setQuery($query)->loadColumn();


				$query    = $db->createQuery()
					->select('*')
					->from($db->quoteName('#__jshopping_products_attr'))
					->where($db->quoteName('product_id') . ' = :product_id')
					->bind(':product_id', $source->product_id, ParameterType::INTEGER);
				$variants = $db->setQuery($query)->loadAssocList();
				foreach ($variants as $variant_source)
				{
					$variant = [
						'id'     => $variant_source['product_id'] . '_' . $variant_source['product_attr_id'],
						'code'   => $variant_source['manufacturer_code'],
						'price'  => (float) $variant_source['price'],
						'fields' => [],
					];

					foreach ($variant_source as $variant_source_key => $variant_source_value)
					{
						if (str_starts_with($variant_source_key, 'attr_') === false)
						{
							continue;
						}

						$variant_source_id = (int) str_replace('attr_', '', $variant_source_key);

						$variant['fields'][$variant_source_id] = (int) $variant_source_value;
					}

					$item['variants'][$variant['id']] = $variant;
				}

				$result[$item['id']] = $item;
			}
			$this->progressbarFinish();

			$this->safeData('com_joomshopping.products.' . $s, $result, $progress);

			$db->disconnect();
		}
	}

	/**
	 * Method to set item translation data.
	 *
	 * @param   array   $item       Result item.
	 * @param   object  $source     Source item.
	 * @param   array   $languages  Languages keys array.
	 * @param   array   $mapping    Fields mapping.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function setItemTranslationData(array &$item, object $source, array $languages, array $mapping): void
	{
		foreach ($languages as $language)
		{
			if (!isset($item['translation'][$language]))
			{
				$item['translation'][$language] = [];
			}

			foreach ($mapping as $from => $to)
			{
				$from_key   = $from . '_' . $language;
				$from_value = (property_exists($source, $from_key)) ? $source->{$from_key} : '';

				if (empty($item[$to]))
				{
					$item[$to] = $from_value;
				}

				$item['translation'][$language][$to] = $from_value;
			}
		}
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