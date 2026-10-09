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

namespace Joomla\Plugin\System\Migrator\Console\Joomla\Import;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseAwareTrait;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Plugin\System\Migrator\Console\AbstractCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\ImportTrait;

class ImportUsersCommand extends AbstractCommand
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
	protected static $defaultName = 'migrator:import:users';

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
		'importUsersGroups',
		'importViewLevels',
		'importUsers',
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

		$this->ioStyle->warning('Importing users may cause you to lose access to the site and lead to issues regarding the authorship of content and other entities.');
		$this->confirm = $this->ioStyle->confirm('Are you sure you want to import users?');

		return $this->confirm;
	}

	/**
	 * Method to import users groups.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function importUsersGroups(): void
	{
		if (!$this->confirmImport())
		{
			return;
		}

		$this->ioStyle->title('Migrator Import: Users Groups');

		$data = $this->getData('com_users.usersgroups');
		if (count($data) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$db = $this->getDatabase();
		$this->ioStyle->text('Truncate data');
		$this->progressbarStart();
		$db->truncateTable('#__usergroups');
		$this->progressbarFinish();

		$this->ioStyle->text('Import data');
		$this->progressbarStart(count($data));
		foreach ($data as $datum)
		{
			$object = (object) $datum;
			$db->insertObject('#__usergroups', $object);
			$this->progressbarAdvance();
		}
		$this->progressbarFinish();
		$db->disconnect();
	}

	/**
	 * Method to import view levels.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function importViewLevels(): void
	{
		if (!$this->confirmImport())
		{
			return;
		}

		$this->ioStyle->title('Migrator Import: View Levels');

		$data = $this->getData('com_users.viewlevels');
		if (count($data) === 0)
		{
			$this->ioStyle->info('Nothing to import.');

			return;
		}

		$db = $this->getDatabase();
		$this->ioStyle->text('Truncate data');
		$this->progressbarStart();
		$db->truncateTable('#__viewlevels');
		$this->progressbarFinish();

		$this->ioStyle->text('Import data');
		$this->progressbarStart(count($data));
		foreach ($data as $datum)
		{
			$object = (object) $datum;
			$db->insertObject('#__viewlevels', $object);
			$this->progressbarAdvance();
		}
		$this->progressbarFinish();
		$db->disconnect();
	}

	/**
	 * Method to import users.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function importUsers(): void
	{
		if (!$this->confirmImport())
		{
			return;
		}

		$this->ioStyle->title('Migrator Import: Prepare users');

		$this->ioStyle->text('Get files');
		$this->progressbarStart();
		$folder = Path::clean(JPATH_ROOT . '/administrator/migrator');
		$files  = Folder::files($folder, 'com_users.users.');
		if (count($files) === 0)
		{
			$this->progressbarFinish();
			$this->ioStyle->info('Nothing to import.');

			return;
		}
		$result = [];
		foreach ($files as $file)
		{
			if (!str_starts_with($file, 'com_users.users.'))
			{
				continue;
			}

			$k          = (int) str_replace(['com_users.users.', '.json'], '', $file);
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
		$tables = ['#__user_keys', '#__user_mfa', '#__user_notes', '#__user_profiles', '#__users',
			'#__user_usergroup_map', '#__session'];
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
				$object = (object) $datum;
				$groups = $object->groups;
				$db->insertObject('#__users', $object);
				foreach ($groups as $group)
				{
					$insert           = new \stdClass();
					$insert->user_id  = $object->id;
					$insert->group_id = $group;
					$db->insertObject('#__user_usergroup_map', $insert);
				}

				$this->progressbarAdvance();
			}
			$this->progressbarFinish();

			$db->disconnect();
		}
	}
}