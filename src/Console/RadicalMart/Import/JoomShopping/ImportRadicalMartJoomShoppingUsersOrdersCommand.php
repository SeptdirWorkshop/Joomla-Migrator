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

use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\QuantityHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\UserHelper;
use Joomla\Component\RadicalMart\Administrator\Model\CustomerModel;
use Joomla\Component\RadicalMart\Administrator\Traits\Command\UtilitiesTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Plugin\System\Migrator\Console\AbstractCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportRadicalMartTrait;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportTrait;
use Joomla\Registry\Registry;

class ImportRadicalMartJoomShoppingUsersOrdersCommand extends AbstractCommand
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
	protected static $defaultName = 'migrator:import:radicalmart:joomshopping_users_orders';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Import: Users';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'importJoomShoppingCustomers',
		'importJoomShoppingOrders',
	];

	/**
	 * Confirm import check.
	 *
	 * @var bool|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected bool|null $confirm = null;

	/**
	 * Method to confirm start import.
	 *
	 * @return bool True if cinfirm, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function confirmImport(): bool
	{
		if ($this->confirm !== null)
		{
			return $this->confirm;
		}

		$this->ioStyle->warning('Importing users and order may cause you to lose data.');
		$this->confirm = $this->ioStyle->confirm('Are you sure you want to import users?');

		return $this->confirm;
	}

	/**
	 * Method to import customers.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importJoomShoppingCustomers(): void
	{
		if (!$this->confirmImport())
		{
			return;
		}

		$this->ioStyle->title('Migrator Import: Prepare RadicalMart Customers from JoomShopping Users');

		$this->ioStyle->text('Get files');
		$this->progressbarStart();
		$folder = Path::clean(JPATH_ROOT . '/administrator/migrator');
		$files  = Folder::files($folder, 'com_joomshopping.users');
		if (count($files) === 0)
		{
			$this->progressbarFinish();
			$this->ioStyle->info('Nothing to import.');

			return;
		}
		$result = [];
		foreach ($files as $file)
		{
			if (!str_starts_with($file, 'com_joomshopping.users.'))
			{
				continue;
			}

			$k          = (int) str_replace(['com_joomshopping.users.', '.json'], '', $file);
			$result[$k] = $file;
		}

		ksort($result);
		$files = $result;
		$steps = count($files);
		$this->progressbarFinish();

		if ($steps === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$db = $this->getDatabase();
		$this->ioStyle->text('Truncate data');
		$tables = ['#__radicalmart_customers', '#__radicalmart_users_phones', '#__radicalmart_carts'];
		$this->progressbarStart(count($tables));
		foreach ($tables as $table)
		{
			$db->truncateTable($table);
			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		foreach ($files as $s => $filename)
		{
			$progress = ' (' . $s . '/' . $steps . ')';
			$file     = str_replace('.json', '', $filename);

			$this->ioStyle->title('Migrator Import: RadicalMart Customers from JoomShopping Users' . $progress);
			$data = $this->getData($file);
			if (count($data) === 0)
			{
				$this->ioStyle->info('Nothing to import.');

				continue;
			}

			$this->ioStyle->text('Import data');
			$this->progressbarStart(count($data));
			foreach ($data as $datum)
			{
				$last  = (int) $datum['user_id'];
				$query = $db->createQuery()
					->select('id')
					->from('#__users')
					->where($db->quoteName('id') . ' = :last')
					->bind(':last', $last, ParameterType::INTEGER);
				$find  = $db->setQuery($query)->loadResult();
				if (empty($find))
				{
					$this->progressbarAdvance();
					continue;
				}

				$phone = (!empty($datum['phone'])) ? UserHelper::cleanPhone($datum['phone']) : '';
				$save  = [
					'id'       => $last,
					'contacts' => [
						'first_name'  => $datum['f_name'],
						'second_name' => $datum['m_name'],
						'last_name'   => $datum['l_name'],
						'email'       => $datum['email'],
						'phone'       => $phone,
					]
				];

				if (!empty($phone))
				{
					$save['user'] = [
						'edit'  => 1,
						'phone' => $phone,
					];
				}

				/** @var CustomerModel $model */
				$model = $this->getComponentModel('com_radicalmart', 'Customer');
				$model->setState('customer.id', $last);
				$model->setState('save.task', 'save');

				$result = $model->save($save);
				if ($result === false)
				{
					throw new \Exception(implode(PHP_EOL, $this->getModelErrorsMessages($model)), 500);
				}

				$this->progressbarAdvance();
			}
			$this->progressbarFinish();
			$this->cleanRadicalMartRAM();

			$db->disconnect();
		}
	}

	/**
	 * Method to import customers.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function importJoomShoppingOrders(): void
	{
		if (!$this->confirmImport())
		{
			return;
		}

		$this->ioStyle->title('Migrator Import: Prepare RadicalMart Orders from JoomShopping');

		$this->ioStyle->text('Get files');
		$this->progressbarStart();
		$folder = Path::clean(JPATH_ROOT . '/administrator/migrator');
		$files  = Folder::files($folder, 'com_joomshopping.orders');
		if (count($files) === 0)
		{
			$this->progressbarFinish();
			$this->ioStyle->info('Nothing to import.');

			return;
		}
		$result = [];
		foreach ($files as $file)
		{
			if (!str_starts_with($file, 'com_joomshopping.orders.'))
			{
				continue;
			}

			$k          = (int) str_replace(['com_joomshopping.orders.', '.json'], '', $file);
			$result[$k] = $file;
		}

		ksort($result);
		$files = $result;
		$steps = count($files);
		$this->progressbarFinish();

		if ($steps === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$db = $this->getDatabase();
		$this->ioStyle->text('Truncate data');
		$tables = ['#__radicalmart_orders'];
		$this->progressbarStart(count($tables));
		foreach ($tables as $table)
		{
			$db->truncateTable($table);
			$this->progressbarAdvance();
		}
		$this->progressbarFinish();

		$this->ioStyle->text('Get default status');
		$this->progressbarStart();
		$status_selector   = $this->getMigratorSelector('status', '0');
		$default_status_id = $this->findItemId('status', $status_selector);
		if (empty($default_status_id))
		{
			$default_status_id = 1;
		}
		$this->progressbarFinish();

		foreach ($files as $s => $filename)
		{
			$progress = ' (' . $s . '/' . $steps . ')';
			$file     = str_replace('.json', '', $filename);

			$this->ioStyle->title('Migrator Import: RadicalMart Orders from JoomShopping' . $progress);
			$data = $this->getData($file);
			if (count($data) === 0)
			{
				$this->ioStyle->info('Nothing to import.');

				continue;
			}

			$this->ioStyle->text('Import data');
			$this->progressbarStart(count($data));
			foreach ($data as $datum)
			{
				if (!empty($datum['problem']))
				{
					$this->progressbarAdvance();
					continue;
				}

				$phone       = (!empty($datum['d_mobil_phone'])) ? UserHelper::cleanPhone($datum['d_mobil_phone']) : '';
				$code        = $datum['currency_code_iso'];
				$total_base  = PriceHelper::clean($datum['order_subtotal'], $code);
				$total_fee   = PriceHelper::clean($datum['order_tax'], $code);
				$total_final = PriceHelper::clean($datum['order_total'], $code);
				$save        = [
					'id'          => $datum['order_id'],
					'number'      => $datum['order_number'],
					'products'    => [],
					'currency'    => $code,
					'contacts'    => [
						'first_name'  => $datum['d_f_name'],
						'second_name' => $datum['d_m_name'],
						'last_name'   => $datum['d_l_name'],
						'email'       => $datum['d_email'],
						'phone'       => $phone,
					],
					'total'       => [
						'base'  => $total_base,
						'fee'   => $total_fee,
						'final' => $total_final,
					],
					'payment'     => [],
					'status'      => $default_status_id,
					'state'       => 1,
					'created'     => (new Date($datum['order_date']))->toSql(),
					'created_by'  => $datum['user_id'],
					'modified'    => (new Date($datum['order_date']))->toSql(),
					'modified_by' => $datum['user_id'],
				];

				foreach ($datum['items'] as $item)
				{
					if (empty($item['product_id']))
					{
						continue;
					}

					if (empty($item['attributes']))
					{
						$selector = $this->getMigratorSelector('product', $item['product_id']);
					}
					else
					{
						$selector = $this->getMigratorSelector('product', $item['variant_id']);
					}

					$product_id = $this->findItemId('product', $selector);

					$price    = PriceHelper::clean($item['product_item_price'], $save['currency']);
					$quantity = QuantityHelper::clean($item['product_quantity']);
					$sum      = PriceHelper::clean($price * $quantity, $save['currency']);

					$product_key = 'p' . $product_id;
					$product     = [
						'id'        => $product_id,
						'base'      => $price,
						'discount'  => '',
						'final'     => $price,
						'quantity'  => $quantity,
						'sum_base'  => $sum,
						'sum_final' => $sum,
						'plugins'   => [],
					];
					if (!empty($item['freeattributes']))
					{
						foreach ($item['freeattributes'] as $attr_id => $attr_value)
						{
							$product['plugins']['free_attr_' . $attr_id] = $attr_value;
						}
					}

					$save['products'][$product_key] = $product;
				}

				if (count($save['products']) === 0)
				{
					$this->progressbarAdvance();
					continue;
				}

				$status_selector = $this->getMigratorSelector('status', $datum['order_status']);
				$status_id       = $this->findItemId('status', $status_selector);
				if (!empty($status_id))
				{
					$save['status'] = $status_id;
				}


				$payment_selector  = $this->getMigratorSelector('payment_method', $datum['payment_method_id']);
				$payment_method_id = $this->findItemId('payment_method', $payment_selector);
				if (!empty($payment_method_id))
				{
					$save['payment']['id'] = $payment_method_id;
				}

				$insert           = (object) $save;
				$insert->products = (new Registry($save['products']))->toString();
				$insert->contacts = (new Registry($save['contacts']))->toString();
				$insert->total    = (new Registry($save['total']))->toString();
				$insert->payment  = (new Registry($save['payment']))->toString();

				$db->insertObject('#__radicalmart_orders', $insert);
				$db->disconnect();
				$this->cleanRadicalMartRAM();
				$this->progressbarAdvance();
			}

			$this->progressbarFinish();


			$db->disconnect();
		}
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
}