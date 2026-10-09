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
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Plugin\System\Migrator\Console\AbstractCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\ExportTrait;

class ExportUsersCommand extends AbstractCommand
{
	use ExportTrait;

	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'migrator:export:users';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Export: Users';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'exportUsersGroups',
		'exportViewLevels',
		'exportUsers',
	];

	/**
	 * Method to export users groups.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportUsersGroups(): void
	{
		$this->ioStyle->title('Migrator Export: Users Groups');

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__usergroups'));
		$rows  = $db->setQuery($query)->loadObjectList();
		$this->progressbarFinish();

		$this->safeData('com_users.usersgroups', $rows);

		$db->disconnect();
	}

	/**
	 * Method to export users groups.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportViewLevels(): void
	{
		$this->ioStyle->title('Migrator Export: View levels');

		$this->ioStyle->text('Get items');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__viewlevels'));
		$rows  = $db->setQuery($query)->loadObjectList();
		$this->progressbarFinish();

		$this->safeData('com_users.viewlevels', $rows);

		$db->disconnect();
	}

	/**
	 * Method to export users.
	 *
	 * @throws \Throwable
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function exportUsers(): void
	{
		$this->ioStyle->title('Migrator Export: Users');

		$folder = Path::clean(JPATH_ROOT . '/administrator/migrator');
		if (is_dir($folder))
		{
			$this->ioStyle->text('Clean files');
			$this->progressbarStart();
			$files = Folder::files($folder, 'com_users.users.');
			$count = count($files);
			if ($count > 0)
			{
				$this->progressbar->setMaxSteps($count);
				foreach ($files as $file)
				{
					if (!str_starts_with($file, 'com_users.users.')) {
						$this->progressbarAdvance();
						continue;
					}

					File::delete(Path::clean($folder . '/' . $file));
					$this->progressbarAdvance();
				}
			}
			$this->progressbarFinish();
		}

		$this->ioStyle->text('Get total');
		$this->progressbarStart();
		$db    = $this->getDonorDatabase();
		$query = $db->createQuery()
			->select('COUNT(id)')
			->from($db->quoteName('#__users'))
			->order('id asc');
		$total = $db->setQuery($query)->loadResult();
		$this->progressbarFinish();

		$last  = 0;
		$limit = 2500;
		$steps = ceil($total / $limit);
		for ($s = 1; $s <= $steps; $s++)
		{
			$progress = ' (' . $s . '/' . $steps . ')';
			$this->ioStyle->text('Get items' . $progress);
			$this->progressbarStart();
			$query = $db->createQuery()
				->select('*')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('id') . ' > :last')
				->bind(':last', $last, ParameterType::INTEGER)
				->order('id asc');
			$rows  = $db->setQuery($query, 0, $limit)->loadObjectList();
			$count = count($rows);
			$this->progressbarFinish();

			if ($count === 0)
			{
				break;
			}

			$this->ioStyle->text('Prepare data' . $progress);
			$this->progressbarStart(count($rows));
			foreach ($rows as $row)
			{
				$last = (int) $row->id;
				$this->progressbarAdvance();

				$query  = $db->createQuery()
					->select('group_id')
					->from($db->quoteName('#__user_usergroup_map'))
					->where($db->quoteName('user_id') . ' = :last')
					->bind(':last', $last, ParameterType::INTEGER);
				$groups = $db->setQuery($query)->loadColumn();

				$row->groups = $groups;
			}
			$this->progressbarFinish();

			$this->safeData('com_users.users.' . $s, $rows, $progress);

			$db->disconnect();

			if ($count < $limit)
			{
				break;
			}
		}
	}
}