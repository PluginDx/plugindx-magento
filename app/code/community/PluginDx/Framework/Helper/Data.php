<?php
/**
 * PluginDx_Framework
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   PluginDx
 * @package    PluginDx_Framework
 * @copyright  Copyright (c) 2017 Fast Division
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * PluginDx Data Helper
 */
class PluginDx_Framework_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getReportUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/plugindx/report')
                    . '?isAjax=true&form_key='
                    . Mage::getSingleton('core/session')->getFormKey();
    }
}
