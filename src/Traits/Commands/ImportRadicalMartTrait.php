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

use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\LanguagesHelper;
use Joomla\Database\DatabaseAwareTrait;

\defined('_JEXEC') or die;

trait ImportRadicalMartTrait
{
	use DatabaseAwareTrait;

	/**
	 * RadicalMart items mapping.
	 *
	 * @var array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected array $_mapping = [];

	/**
	 * Multilanguage enabled cache.
	 *
	 * @var string|bool|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected string|bool|null $_multilanguage = null;

	/**
	 * Method to find RadicalMart item id from 1c id.
	 *
	 * @param   string  $type            Item type [category|product|field]
	 * @param   string  $integration_id  1C item id.
	 * @param   string  $selector        Raicalmart search subcol.
	 *
	 * @throws \Exception
	 *
	 * @return int RadicalMart item id, 0 if not found.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function findItemId(string $type, string $integration_id, string $selector = 'migrator_selector'): int
	{
		if (!isset($this->_mapping[$type]) || count($this->_mapping[$type]) > 1000)
		{
			$this->_mapping[$type] = [];
		}

		if (isset($this->_mapping[$type][$integration_id]))
		{
			return $this->_mapping[$type][$integration_id];
		}

		$tables = [
			'category' => '#__radicalmart_categories',
			'field'    => '#__radicalmart_fields',
			'product'  => '#__radicalmart_products',
			'meta'     => '#__radicalmart_metas',
		];
		if (!isset($tables[$type]))
		{
			throw new \Exception('RadicalMart Item does not support');
		}

		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName($tables[$type]))
			->where('JSON_VALUE(plugins, ' . $db->quote('$."' . $selector . '"') . ') = :1c_id')
			->bind(':1c_id', $integration_id);
		$find  = $db->setQuery($query, 0, 1)->loadResult();

		$result = (!empty($find)) ? (int) $find : 0;

		$this->_mapping[$type][$integration_id] = $result;

		return $result;
	}

	/**
	 * Method to check multilanguage.
	 *
	 * @throws \Exception
	 *
	 * @return string|bool Site default language on success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getMultilanguage(): string|bool
	{
		if ($this->_multilanguage !== null)
		{
			return $this->_multilanguage;
		}

		$this->_multilanguage = (Multilanguage::isEnabled()
			&& PluginHelper::isEnabled('radicalmart', 'translation'))
			? LanguagesHelper::getDefaultTag('site') : false;

		return $this->_multilanguage;
	}
}