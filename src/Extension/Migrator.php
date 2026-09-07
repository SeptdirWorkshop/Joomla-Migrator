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

namespace Joomla\Plugin\System\Migrator\Extension;

\defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

class Migrator extends CMSPlugin implements SubscriberInterface
{
	use MVCFactoryAwareTrait;
	use DatabaseAwareTrait;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents::BEFORE_EXECUTE => 'onBeforeExecute',
		];
	}

	/**
	 * Method to register CLI commands.
	 *
	 * @param   ApplicationEvent  $event  Event object.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onBeforeExecute(ApplicationEvent $event): void
	{
		/** @var ConsoleApplication $app */
		$app = Factory::getApplication();

		// Load from files
		$commands = [];
		$root     = Path::clean(JPATH_PLUGINS . '/system/migrator/src/Console');
		$files    = Folder::files($root, '.php', true, true);
		foreach ($files as $file)
		{
			$filename = ltrim(str_replace($root, '', $file), '/\\');
			$class    = str_replace('/', '\\', File::stripExt($filename));
			if ($class === 'AbstractCommand' || $class === 'CleanCommand')
			{
				continue;
			}

			$commands[] = 'Joomla\\Plugin\\System\Migrator\\Console\\' . $class;
		}

		foreach ($commands as $commandFQN)
		{
			try
			{
				if (!class_exists($commandFQN))
				{
					continue;
				}

				$command = new $commandFQN();

				if (method_exists($command, 'setMVCFactory'))
				{
					$command->setMVCFactory($this->getMVCFactory());
				}

				if (method_exists($command, 'setDatabase'))
				{
					$command->setDatabase($this->getDatabase());
				}

				$app->addCommand($command);
			}
			catch (\Throwable)
			{
				continue;
			}
		}
	}

}