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

namespace Joomla\Plugin\System\Migrator\Traits\Commands;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

trait ExportTrait
{
	use UtilitiesTrait;

	protected ?DatabaseInterface $donorDatabase = null;

	/**
	 * Method to get donor database interface.
	 *
	 * @throws \Throwable
	 *
	 * @return DatabaseInterface
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getDonorDatabase(): DatabaseInterface
	{
		if ($this->donorDatabase !== null)
		{
			return $this->donorDatabase;
		}

		$plugin = PluginHelper::getPlugin('system', 'migrator');
		$params = new Registry($plugin->params);
		if (empty($params->get('db_name')))
		{
			throw new \Exception('Database params not specified in plugin params', 500);
		}

		$app     = Factory::getApplication();
		$options = [
			'driver'   => $params->get('db_type', $app->get('dbtype')),
			'host'     => $params->get('db_host', $app->get('host')),
			'user'     => $params->get('db_user', $app->get('user')),
			'password' => $params->get('db_password', $app->get('password')),
			'database' => $params->get('db_name'),
			'prefix'   => $params->get('db_prefix', $app->get('prefix')),
		];

		$this->donorDatabase = (new DatabaseFactory())->getDriver($options['driver'], $options);
		try
		{
			$this->donorDatabase->getTableList();
		}
		catch (\Throwable $e)
		{

			$this->donorDatabase = null;
			throw $e;
		}

		return $this->donorDatabase;
	}

	/**
	 * Safe export result to file.
	 *
	 * @param   string  $file  Filename.
	 * @param   array   $data  Export data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function safeData(string $file, array $data): void
	{
		$this->ioStyle->text('Save data to: ' . $file);
		$this->progressbarStart();
		$data   = (new Registry($data))->toString();
		$folder = Path::clean(JPATH_ROOT . '/administrator/migrator');
		if (!is_dir($folder))
		{
			Folder::create($folder);
		}
		$filename = $folder . '/' . $file . '.json';
		if (is_file($filename))
		{
			File::delete($filename);
		}
		if (file_put_contents($filename, $data) === false)
		{
			throw new \Exception('Unable to write data to: ' . $filename);
		}

		$this->progressbarFinish();
	}
}