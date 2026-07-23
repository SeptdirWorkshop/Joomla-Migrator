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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ModelInterface;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

trait ImportTrait
{
	/**
	 * Safe export result to file.
	 *
	 * @param   string  $file  Filename.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getData(string $file): array
	{
		$this->ioStyle->text('Get data from: ' . $file);
		$this->progressbarStart();
		$path = Path::clean(JPATH_ROOT . '/administrator/migrator/' . $file . '.json');
		if (!is_file($path))
		{
			throw new \Exception('File not found: ' . $path, 404);
		}
		$data = (new Registry(file_get_contents($path)))->toArray();
		$this->progressbarFinish();

		return $data;
	}

	/**
	 * Method to get component model object.
	 *
	 * @param   string  $component  The component to boot.
	 * @param   string  $name       The name of the model.
	 * @param   string  $prefix     Optional model prefix.
	 * @param   array   $config     Optional configuration array for the model.
	 *
	 * @throws \Exception
	 *
	 * @return ModelInterface The model object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getComponentModel(string $component, string $name, string $prefix = 'Administrator',
	                                     array  $config = ['ignore_request' => true]): ModelInterface
	{
		return Factory::getApplication()->bootComponent($component)->getMVCFactory()->createModel($name, $prefix, $config);
	}

	/**
	 * Metho to get errors messages array from model.
	 *
	 * @param   ModelInterface  $model  Model object.
	 *
	 * @return array|bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getModelErrorsMessages(ModelInterface $model): array|bool
	{
		if (empty($model->getErrors()))
		{
			return false;
		}

		$messages = [];
		foreach ($model->getErrors() as $error)
		{
			$messages[] = ($error instanceof \Exception) ? $error->getMessage() : $error;
		}

		return $messages;
	}
}