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

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Migrator\Extension\Migrator;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function register(Container $container): void
	{
		// Register MVCFactory
		$container->registerServiceProvider(new MVCFactory('Joomla\\Component\\Content'));

		$container->set(PluginInterface::class,
			function (Container $container) {
				// Create plugin class
				$subject = $container->get(DispatcherInterface::class);
				$config  = (array) PluginHelper::getPlugin('system', 'migrator');
				$plugin  = new Migrator($subject, $config);

				// Set application
				$app = Factory::getApplication();
				$plugin->setApplication($app);

				// Set database
				$db = $container->get(DatabaseDriver::class);
				$plugin->setDatabase($db);

				// Set MVCFactory
				$mvcFactory = $container->get(MVCFactoryInterface::class);
				$plugin->setMVCFactory($mvcFactory);

				return $plugin;
			}
		);
	}
};