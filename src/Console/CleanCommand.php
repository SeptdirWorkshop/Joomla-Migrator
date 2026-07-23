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

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

class CleanCommand extends AbstractCommand
{
	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'migrator:clean';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Clean Files';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'executeCommand',
	];

	/**
	 * Method to remove migrator folder.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function executeCommand(): void
	{
		$this->ioStyle->title('Migrator Clean');
		$this->ioStyle->text('Remove files folder');
		$this->progressbarStart();
		$path = Path::clean(JPATH_ROOT . '/administrator/migrator');
		if (is_dir($path))
		{
			Folder::delete($path);
		}
		$this->progressbarFinish();
	}
}