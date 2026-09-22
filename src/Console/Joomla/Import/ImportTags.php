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

class ImportTags extends ImportJoomlaCommand
{
	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'migrator:import:tags';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Migrator Import: Tags';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'importTags',
	];
}