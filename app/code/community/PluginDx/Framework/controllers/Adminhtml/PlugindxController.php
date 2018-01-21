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
 * PluginDx Admin Router
 */
class PluginDx_Framework_Adminhtml_PlugindxController extends Mage_Adminhtml_Controller_Action
{
    public function reportAction()
    {
        $report = Mage::getModel('plugindx/report');
        $config = $this->getRequest()->getParam('config');

        if ($config) {
            try {
                $generatedReport = $report->build($config);
                $this->getResponse()->setBody($generatedReport);
            } catch (Exception $e) {
                $this->_badRequest('Unable to build report: ' . $e->getMessage());
            }
        } else {
            $this->_badRequest('Invalid config data');
        }
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->getResponse()->setHeader('Content-Type', 'application/json');

        return $this;
    }

    protected function _isAllowed()
    {
        if ($this->getRequest()->getActionName() === 'report') {
            return Mage::getSingleton('admin/session')->isAllowed('admin/system');
        }

        return false;
    }

    private function _badRequest($reason) {
        $this->getResponse()->setHeader('Content-Length', '0');
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
        $this->getResponse()->setHeader('HTTP/1.0', '400 Bad Request');
        $this->getResponse()->setHeader('X-Failure-Reason', $reason);
        $this->getResponse()->setHeader('X-Failed-Retry', '1');
    }
}
