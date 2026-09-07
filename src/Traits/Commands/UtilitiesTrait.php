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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;
use Symfony\Component\Console\Helper\ProgressBar;

trait UtilitiesTrait
{
	use DatabaseAwareTrait;

	/**
	 * Is Superuser identity already loaded.
	 *
	 * @var bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected bool $superUserIdentityLoad = false;

	/**
	 * Is URI already ReInstance.
	 *
	 * @var bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected bool $uriReInstance = false;

	/**
	 * Current Progress bar object.
	 *
	 * @var ProgressBar|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?ProgressBar $progressbar = null;

	/**
	 * Method to reinitialize Joomla Uri for site.
	 *
	 * @param   bool  $force  Force reinitialize.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function uriReinitialize(bool $force = false): void
	{
		if ($this->uriReInstance === true && $force === false)
		{
			return;
		}

		$source = [
			'PHP_SELF'    => $_SERVER['PHP_SELF'],
			'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
		];

		$_SERVER['PHP_SELF']    = '/index.php';
		$_SERVER['SCRIPT_NAME'] = '/index.php';

		Uri::reset();
		Uri::getInstance();
		Uri::root();
		Uri::base();
		Uri::current();
		$this->uriReInstance = true;

		$_SERVER['PHP_SELF']    = $source['PHP_SELF'];
		$_SERVER['SCRIPT_NAME'] = $source['SCRIPT_NAME'];
	}

	/**
	 * Method to load superuser identity to application.
	 *
	 * @param   bool  $force  Force load identity.
	 *
	 * @throws \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function loadSuperUserIdentity(bool $force = false): void
	{
		if (!$this->superUserIdentityLoad || $force)
		{
			$db    = $this->getDatabase();
			$query = $db->createQuery()
				->select('rules')
				->from($db->quoteName('#__assets'))
				->where($db->quoteName('name') . ' = ' . $db->quote('root.1'));
			$rules = (new Registry($db->setQuery($query, 0, 1)->loadResult()))->toArray();

			if (empty($rules) || empty($rules['core.admin']))
			{
				throw new \Exception('Superusers rules missing');
			}

			$groups = [];
			foreach ($rules['core.admin'] as $group => $rule)
			{
				if ((int) $rule === 1)
				{
					$groups[] = $group;
				}
			}
			if (empty($groups))
			{
				throw new \Exception('Superusers groups not found');
			}

			$query   = $db->createQuery()
				->select($db->quoteName('user_id'))
				->from($db->quoteName('#__user_usergroup_map'))
				->whereIn($db->quoteName('group_id'), $groups);
			$user_id = $db->setQuery($query, 0, 1)->loadResult();
			if (empty($user_id))
			{
				throw new \Exception('Superuser not fount');
			}

			$user = new User($user_id);
			Factory::getApplication()->loadIdentity($user);
			$this->superUserIdentityLoad = true;
		}
	}

	/**
	 * Method to start progress bar.
	 *
	 * @param   int          $max       Max progress bar steps.
	 * @param   bool         $showTime  Show elapsed and remaining after progress bar.
	 * @param   string|null  $format    Custom progress bar format.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function progressbarStart(int $max = 1, bool $showTime = true, ?string $format = null): void
	{
		if ($this->progressbar !== null)
		{
			$this->progressbarFinish();
		}
		if ($max === 0)
		{
			return;
		}

		$this->progressbar = $this->ioStyle->createProgressBar($max);

		if (empty($format))
		{
			$format = ' %current%/%max% [%bar%] %percent:3s%%';
		}
		if ($showTime)
		{
			$format .= PHP_EOL . ' Time: %elapsed:6s% | Remaining: %remaining% ';
		}
		$this->progressbar->setFormat($format);
		$this->progressbar->start();
	}

	/**
	 * Advance progress bar.
	 *
	 * @param   int  $step  Advance steps count
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function progressbarAdvance(int $step = 1): void
	{
		if ($this->progressbar === null)
		{
			return;
		}

		$this->progressbar->advance($step);
		$this->progressbar->display();
	}

	/**
	 * Finish current progress bar.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function progressbarFinish(): void
	{
		if ($this->progressbar === null)
		{
			return;
		}

		$this->progressbar->finish();
		$this->progressbar = null;

		$this->ioStyle->newLine(2);
	}
}