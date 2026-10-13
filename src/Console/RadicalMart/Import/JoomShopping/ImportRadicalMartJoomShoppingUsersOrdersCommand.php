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
	protected static $defaultName = 'migrator:import:joomshopping_users_orders';

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

		$this->ioStyle->title('Migrator Import: Prepare RadicalMart Customers JoomShopping Users');

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

			$this->ioStyle->title('Migrator Import: Users ' . $progress);
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
}