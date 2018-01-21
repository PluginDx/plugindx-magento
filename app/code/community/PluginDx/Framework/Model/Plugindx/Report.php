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
 * PluginDx Report Builder
 */
class PluginDx_Framework_Model_Plugindx_Report
{
    private $_report;

    public function build($config)
    {
        if (is_array($config)) {
            $this->_report = $config;
        } else {
            $this->_report = json_decode($config, true);
        }

        $this->_getConfig();
        $this->_getCollections();
        $this->_getHelpers();
        $this->_getServerInfo();
        $this->_getLogs();
        $this->_getExtra();

        return json_encode($this->_report);
    }

    private function _getConfig()
    {
        if (!isset($this->_report['config'])) {
            return;
        }

        $configFields = $this->_report['config'];

        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {
                    foreach ($configFields as $fieldIndex => $field) {
                        $storeCode = $store->getCode();
                        $storeName = $store->getName();
                        $value = Mage::getStoreConfig($field['path'], $store->getId());

                        $this->_report['config'][$fieldIndex]['values'][$storeCode]['store'] = $storeName;
                        $this->_report['config'][$fieldIndex]['values'][$storeCode]['value'] = $value;
                    }
                }
            }
        }

        foreach ($configFields as $fieldIndex => $field) {
            if (isset($field['type'])) {
                $this->_report['config'][$fieldIndex]['type'] = $field['type'];
            }
        }
    }

    private function _getCollections()
    {
        if (!isset($this->_report['collections'])) {
            return;
        }

        $collections = $this->_report['collections'];

        foreach ($collections as $collectionIndex => $collection) {
            $data = Mage::getModel($collection['model'])->getCollection();

            if (isset($collection['name'])) {
                $this->_report['collections'][$collectionIndex]['name'] = $collection['name'];
            }

            if (isset($collection['attributes'])) {
                $data->addFieldToSelect($collection['attributes']);
            }

            // TODO: Test multiple filters and possibly build array in advance
            if (isset($collection['filters'])) {
                foreach ($collection['filters'] as $filter) {
                    $data->addFieldToFilter($filter['attribute'], array(
                        $filter['condition'] => $filter['value']
                    ));
                }
            }

            if (isset($collection['count'])) {
                $this->_report['collections'][$collectionIndex]['data'] = $data->count();
            } else {
                $this->_report['collections'][$collectionIndex]['data'] = $data->getData();
            }
        }
    }

    private function _getHelpers()
    {
        if (!isset($this->_report['helpers'])) {
            return;
        }

        $helpers = $this->_report['helpers'];

        foreach ($helpers as $helperIndex => $helper) {
            $helperData = '';

            switch ($helper['path']) {
                case 'magento/edition':
                    $helperData = Mage::getEdition();
                    break;
                case 'magento/version':
                    $helperData = Mage::getVersion();
                    break;
                case 'magento/modules':
                    $helperData = array_keys((array) Mage::getConfig()->getNode('modules')->children());
                    break;
                case 'magento/module_version':
                    $helperData = Mage::getConfig()->getModuleConfig($this->_report['module'])->version[0];
                    break;
                case 'magento/locale':
                    $helperData = Mage::app()->getLocale()->getLocaleCode();
                    break;
                case 'magento/applied_patches':
                    $helperData = $this->_getPatches();
                    break;
            }

            $this->_report['helpers'][$helperIndex]['value'] = $helperData;
        }
    }

    private function _getPatches()
    {
        $io = new Varien_Io_File();
        $patches = array();
        $patchFile = Mage::getBaseDir('etc') . DS . 'applied.patches.list';

        if (!$io->fileExists($patchFile)) {
            return array();
        }

        $io->open(array(
            'path' => $io->dirname($patchFile)
        ));

        $io->streamOpen($patchFile, 'r');

        while ($buffer = $io->streamRead()) {
            if (stristr($buffer, '|')) {
                list($dateApplied, $patch, $magentoVersion, $patchVersion, $commitHash, $patchDate, $commitHead, $reverted) = array_map('trim', explode('|', $buffer));

                if (empty($reverted)) {
                    $patches[] = array(
                        'date_applied' => $dateApplied,
                        'patch' => $patch,
                        'patch_version' => $patchVersion,
                        'patch_date' => $patchDate,
                        'magento_version' => $magentoVersion
                    );
                }
            }
        }

        $io->streamClose();
        return $patches;
    }

    private function _getServerInfo()
    {
        if (!isset($this->_report['server'])) {
            return;
        }

        $serverFields = $this->_report['server'];
        $serverInfo = $this->_parseServerInfo();

        foreach ($serverFields as $fieldIndex => $field) {
            $fieldKeys = explode('/', $field['path']);
            $fieldValue = $serverInfo;

            foreach ($fieldKeys as $fieldKey) {
                if (isset($fieldValue[$fieldKey])) {
                    $fieldValue = $fieldValue[$fieldKey];
                }
            }

            $this->_report['server'][$fieldIndex]['value'] = $fieldValue;
        }
    }

    private function _getLogs()
    {
        if (!isset($this->_report['logs'])) {
            return;
        }

		$logs = $this->_report['logs'];

        foreach ($logs as $logIndex => $log) {
            if (isset($log['path'])) {
                $logResults = array();

                foreach (@glob(Mage::getBaseDir('log') . DS . $log['path']) as $filename) {
                    $logResults[] = $filename;
                }

                if (isset($logResults[0])) {
                    $this->_report['logs'][$logIndex]['value'] = $this->_tailFile($logResults[0], $log['lines']);
                }
            }
        }
    }

    private function _getExtra()
    {
        if (isset($this->_report['integration_id'])) {
            try {
                $extraData = $this->_dispatchEvent('plugindx_framework_report');
                $this->_report['extra'] = $extraData;
            } catch (Exception $e) {
                $this->_report['extra'] = array(
                    'error' => array(
                        'message' => $e->getMessage()
                    )
                );
            }
        }
    }

    private function _dispatchEvent($eventName)
    {
        $extraData = new Varien_Object();
        Mage::dispatchEvent($eventName . '_' . $this->_report['integration_id'], array('extra_data', $extraData));
        return $extraData;
    }

    private function _parseServerInfo()
    {
        ob_start();
        phpinfo(INFO_MODULES);
        $s = ob_get_contents();
        ob_end_clean();

        $s = strip_tags($s, '<h2><th><td>');
        $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
        $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
        $t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        $r = array();
        $count = count($t);
        $p1 = '<info>([^<]+)<\/info>';
        $p2 = '/'.$p1.'\s*'.$p1.'\s*'.$p1.'/';
        $p3 = '/'.$p1.'\s*'.$p1.'/';

        for ($i = 1; $i < $count; $i++) {
            if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
                $name = trim($matchs[1]);
                $vals = explode("\n", $t[$i + 1]);
                foreach ($vals AS $val) {
                    if (preg_match($p2, $val, $matchs)) {
                        $r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
                    } elseif (preg_match($p3, $val, $matchs)) {
                        $r[$name][trim($matchs[1])] = trim($matchs[2]);
                    }
                }
            }
        }

        return $r;
    }

	/**
	 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
	 * @author Torleif Berger, Lorenzo Stanco
	 * @link http://stackoverflow.com/a/15025877/995958
	 * @license http://creativecommons.org/licenses/by/3.0/
	 */
	private function _tailFile($filepath, $lines = 100, $adaptive = true) {
		$f = @fopen($filepath, "rb");
		if (false === $f) {
			return false;
		}
		if (!$adaptive) {
			$buffer = 4096;
		} else {
			$buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
		}
		fseek($f, -1, SEEK_END);
		if ("\n" != fread($f, 1)) {
			$lines -= 1;
		}
		$output = '';
		$chunk = '';
		while (ftell($f) > 0 && $lines >= 0) {
			$seek = min(ftell($f), $buffer);
			fseek($f, -$seek, SEEK_CUR);
			$output = ($chunk = fread($f, $seek)) . $output;
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			$lines -= substr_count($chunk, "\n");
		}
		while ($lines++ < 0) {
			$output = substr($output, strpos($output, "\n") + 1);
		}
		fclose($f);
		return trim($output);
	}
}
