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
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
use Joomla\Component\RadicalMart\Administrator\Model\CategoryModel;
use Joomla\Component\RadicalMart\Administrator\Model\FieldModel;
use Joomla\Component\RadicalMart\Administrator\Model\MetaModel;
use Joomla\Component\RadicalMart\Administrator\Model\ProductModel;
use Joomla\Component\RadicalMart\Administrator\Traits\Command\UtilitiesTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Plugin\System\Migrator\Console\AbstractCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportRadicalMartTrait;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportTrait;

class ImportRadicalMartJoomShoppingCommand extends AbstractCommand
{
	use DatabaseAwareTrait;
	use ImportTrait;
	use ImportRadicalMartTrait;
	use MVCFactoryAwareTrait;
	use UtilitiesTrait;

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
//		'importCategories',
//		'importManufacturers',
		'importAttributes',
		'importProducts',
	];

	/**
	 * Method to import categories.
	 *
	 * @throws \Throwable
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
		$this->cleanRadicalMartRAM();
	}

	/**
	 * Method to import manufacturers.
	 *
	 * @throws \Throwable
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
		$this->cleanRadicalMartRAM();
	}

	/**
	 * Method to import attributes.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importAttributes(): void
	{
		$this->ioStyle->title('Migrator Import: RadicalMart Fields from JoomShopping Attributes');

		$data = $this->getData('com_joomshopping.attributes');
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
			$selector = $this->getMigratorSelector('attribute', $datum['id']);
			$id       = $this->findItemId('field', $selector);
			if (empty($id))
			{
				$id = 0;
			}

			$this->setMultilanguageDatum($datum, ['title', 'alias', 'description']);

			$save = [
				'id'             => $id,
				'title'          => $datum['title'],
				'alias'          => $datum['alias'],
				'area'           => 'products',
				'all_categories' => 1,
				'plugin'         => 'standard',
				'description'    => $datum['description'],
				'options'        => [],
				'params'         => [
					'type'                   => 'list',
					'multiple'               => 0,
					'null_value'             => 1,
					'display_products'       => 0,
					'display_products_as'    => 'string',
					'display_product'        => 0,
					'display_product_as'     => 'string',
					'display_filter'         => 0,
					'display_filter_as'      => 'checkboxes',
					'display_variability'    => 1,
					'display_variability_as' => 'list',

				],
				'state'          => 1,
				'plugins'        => [
					'migrator_selector' => $selector,
				],
				'language'       => '*'
			];

			if ($multilanguage && !empty($datum['translation']))
			{
				$save['plugins']['translation'] = [];
				foreach ($datum['translation'] as $lang => $translation)
				{
					$save['plugins']['translation'][$lang] = [
						'title'       => $translation['title'],
						'description' => $translation['description'],
					];
				}
			}

			foreach ($datum['options'] as $option)
			{
				$this->setMultilanguageDatum($option, ['text']);
				$save_option = [
					'value'    => 'attr_value_id-' . $option['value'],
					'text'     => $option['text'],
					'image'    => '',
					'ordering' => $option['ordering'],
					'plugins'  => [],
				];

				if ($multilanguage && !empty($option['translation']))
				{
					foreach ($option['translation'] as $option_lang => $option_translation)
					{
						$save_option['plugins']['translation'][$option_lang] = [
							'text' => $option_translation['text'],
						];
					}
				}

				$save['options'][] = $save_option;
			}

			/** @var FieldModel $model */
			$model = $this->getComponentModel('com_radicalmart', 'Field');
			$model->setState('field.id', $id);
			$model->setState('save.task', 'save');

			$result = $model->save($save);
			if ($result === false)
			{
				throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
			}

			$this->_mapping['field'][$selector] = $result;

			$this->progressbarAdvance();
		}

		$this->progressbarFinish();
		$this->cleanRadicalMartRAM();
	}

	/**
	 * Method to import products.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importProducts(): void
	{
		$this->ioStyle->title('Migrator Prepare: Import RadicalMart Product from JoomShopping Products');

		$this->ioStyle->text('Get files');
		$this->progressbarStart();
		$folder = Path::clean(JPATH_ROOT . '/administrator/migrator');
		$files  = Folder::files($folder, 'com_joomshopping.products');
		$this->progressbarFinish();
		if (count($files) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$this->ioStyle->text('Get currencies');
		$data = $this->getData('com_joomshopping.currencies');
		if (empty($data))
		{
			throw new \Exception('Currencies not found', 404);
		}

		$this->ioStyle->text('Parse currencies');
		$this->progressbarStart(count($data));

		$currencies       = [];
		$currencies_rates = [];
		foreach ($data as $datum)
		{
			$currencies[$datum['id']]         = PriceHelper::getCurrency($datum['code']);
			$currencies_rates[$datum['code']] = $datum['rate'];
			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		$multilanguage = $this->getMultilanguage();
		$this->loadSuperUserIdentity();

		$s     = 0;
		$steps = count($files);
		foreach ($files as $filename)
		{
			$s++;
			$progress = ' (' . $s . '/' . $steps . ')';
			$file     = str_replace('.json', '', $filename);

			$this->ioStyle->title('Migrator Import: RadicalMart Products from JoomShopping Products ' . $progress);

			$data = $this->getData($file);
			if (count($data) === 0)
			{
				$this->ioStyle->info('Nothing to import.');

				continue;
			}

			$this->ioStyle->text('Import data');
			$this->progressbarStart(count($data));
			$c = 0;
			foreach ($data as $datum)
			{
				$c++;
				$this->setMultilanguageDatum($datum, ['title', 'alias', 'introtext', 'fulltext', 'meta_title', 'meta_description']);

				$category                         = null;
				$categories_additional_categories = [];
				if (!empty($datum['categories']))
				{
					foreach ($datum['categories'] as $catid)
					{
						$cat_selector = $this->getMigratorSelector('category', $catid);
						$cat_find     = $this->findItemId('category', $cat_selector);
						if (empty($cat_find))
						{
							continue;
						}
						if ($category == null)
						{
							$category = $cat_find;
						}
						else
						{
							$categories_additional_categories[] = $cat_find;
						}
					}
				}
				if (empty($category))
				{
					$category = 1;
				}

				$categories_additional_manufacturers = [];
				if (!empty($datum['manufacturer']))
				{
					$manufacturer_selector = $this->getMigratorSelector('manufacturer', $datum['manufacturer']);
					$manufacturer_find     = $this->findItemId('category', $manufacturer_selector);
					if (!empty($manufacturer_find))
					{
						$categories_additional_manufacturers[] = $manufacturer_find;
					}
				}

				$save = [
					'id'                                  => 0,
					'title'                               => $datum['title'],
					'alias'                               => $datum['alias'],
					'code'                                => $datum['code'],
					'category'                            => $category,
					'categories_additional_categories'    => $categories_additional_categories,
					'categories_additional_manufacturers' => $categories_additional_manufacturers,
					'introtext'                           => $datum['introtext'],
					'fulltext'                            => $datum['fulltext'],
					'prices'                              =>
						$this->prepareProductPrices($datum['price'], $currencies_rates),
					'stock'                               => ['all' => 0],
					'in_stock'                            => 1,
					'shipping'                            => [],
					'fields'                              => [],
					'state'                               => $datum['state'],
					'media'                               => [],
					'params'                              => [
						'seo_product_title'       => $datum['meta_title'],
						'seo_product_description' => $datum['meta_description'],
					],
					'plugins'                             => [],
					'language'                            => '*',
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


				if (empty($datum['variants']))
				{
					$selector = $this->getMigratorSelector('product', $datum['id']);
					$id       = $this->findItemId('product', $selector);
					if (empty($id))
					{
						$id = 0;
					}

					$save['id']                           = $id;
					$save['plugins']['migrator_selector'] = $selector;

					/** @var ProductModel $model */
					$model = $this->getComponentModel('com_radicalmart', 'Product');
					$model->setState('product.id', $id);
					$model->setState('save.task', 'save');

					$result = $model->save($save);
					if ($result === false)
					{
						throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
					}

					$this->_mapping['product'][$selector] = $result;
				}
				else
				{
					$meta          = $save;
					$meta_selector = $this->getMigratorSelector('meta', $datum['id']);
					$meta_id       = $this->findItemId('meta', $meta_selector);
					if (empty($meta_id))
					{
						$meta_id = 0;
					}

					$source              = $save;
					$source['alias']     = '';
					$source['introtext'] = '';
					$source['fulltext']  = '';
					$source['params']    = [];
					$source['plugins']   = [];

					$meta_fields   = [];
					$meta_products = [];
					foreach ($datum['variants'] as $variant)
					{
						$product_save          = $source;
						$product_save_selector = $this->getMigratorSelector('product', $variant['id']);
						$product_save_id       = $this->findItemId('product', $product_save_selector);
						if (empty($product_save_id))
						{
							$product_save_id = 0;
						}

						$product_save_subtitle = [];
						foreach ($variant['fields'] as $variant_field_id => $variant_field_value)
						{
							if (empty($variant_field_value))
							{
								continue;
							}

							$variant_value_rm = 'attr_value_id-' . $variant_field_value;

							$field_selector = $this->getMigratorSelector('attribute', $variant_field_id);
							$field_find     = $this->findFieldData($field_selector);
							if (empty($field_find))
							{
								continue;
							}

							if (empty($field_find->options[$variant_value_rm]))
							{
								continue;
							}

							$meta_fields[] = $field_find->id;

							$product_save['fields'][$field_find->alias] = $variant_value_rm;

							$product_save_subtitle[] = $field_find->title . ': ' . $field_find->options[$variant_value_rm];
						}

						$product_save['id']               = $product_save_id;
						$product_save['meta_variability'] = $meta_id;

						$product_save['title']  .= ' (' . implode(' | ', $product_save_subtitle) . ')';
						$product_save['prices'] = $this->prepareProductPrices($variant['price'], $currencies_rates);

						$product_save['plugins']['migrator_selector'] = $product_save_selector;

						/** @var ProductModel $model */
						$model = $this->getComponentModel('com_radicalmart', 'Product');
						$model->setState('product.id', $product_save_id);
						$model->setState('save.task', 'save');
						$model->setState('update.meta_variability', 0);

						$result = $model->save($product_save);
						if ($result === false)
						{
							throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
						}

						$this->_mapping['product'][$product_save_selector] = $result;

						$meta_products[] = ['id' => $result];
					}

					if (empty($meta_id))
					{
						$meta_id = 0;
					}

					$meta['id']                           = $meta_id;
					$meta['type']                         = 'variability';
					$meta['prices']                       = [];
					$meta['products']                     = $meta_products;
					$meta['fields']                       = [];
					$meta['params']['variability_fields'] = $meta_fields;

					/** @var MetaModel $model */
					$model = $this->getComponentModel('com_radicalmart', 'Meta');
					$model->setState('meta.id', $meta_id);
					$model->setState('save.task', 'save');

					$result = $model->save($meta);
					if ($result === false)
					{
						throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
					}
				}

				if ($c >= 20)
				{
					$this->cleanRadicalMartRAM();
					$c = 0;
				}

				$this->progressbarAdvance();
			}

			$this->progressbarFinish();
		}
	}

	/**
	 * Method to convert prices.
	 *
	 * @param   float|int|null  $js_price_value    JoomShopping price value.
	 * @param   array           $currencies_rates  Currencties rates.
	 *
	 * @throws \Exception
	 *
	 * @return array RadicalMart prices values
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function prepareProductPrices(float|int|null $js_price_value, array $currencies_rates): array
	{
		$result = [];
		foreach ($currencies_rates as $currency_code => $rate)
		{
			$currency                   = PriceHelper::getCurrency($currency_code);
			$value                      = (!empty($js_price_value)) ? PriceHelper::clean($js_price_value * $rate, $currency_code) : 0;
			$result[$currency['group']] = [
				'base'             => $value,
				'discount'         => '',
				'discount_enabled' => 0,
				'final'            => $value,
				'currency'         => $currency_code,
			];
		}

		return $result;
	}

	/**
	 * Method to get migrator selector.
	 *
	 * @param   string      $type  Item type.
	 * @param   int|string  $id    Item id.
	 *
	 * @return string Migrator item selector.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getMigratorSelector(string $type, int|string $id): string
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