<?php

/**
 * Class Aoe_AttributeConfigurator_Model_Shell
 *
 * Container for all Shell related Methods
 *
 * @category Model
 * @package  Aoe_AttributeConfigurator
 * @author   FireGento Team <team@firegento.com>
 * @author   AOE Magento Team <team-magento@aoe.com>
 * @license  Open Software License v. 3.0 (OSL-3.0)
 * @link     https://github.com/AOEpeople/AttributeConfigurator
 * @see      https://github.com/magento-hackathon/AttributeConfigurator
 */
class Aoe_AttributeConfigurator_Model_Shell extends Mage_Core_Model_Abstract
{
    const PARAM_RUN_ALL     = 'runAll',
          PARAM_IMPORT_FILE = 'importFile';

    /**
     * Init Shell Settings
     *
     * @param array $args Arguments
     * @return void
     */
    public function setIni(array $args)
    {
        foreach ($args as $arg => $value) {
            ini_set($arg, $value);
        }
    }

    /**
     * Validation of Shell Run Context
     *
     * @param Aoe_AttributeConfigurator_Shell_Command $shell Shell Script Model
     * @return void
     */
    public function validate($shell)
    {
        $importFile = $shell->getArg(self::PARAM_IMPORT_FILE);
        if ($importFile) {
            $importFileCheck = $this->_checkImportFilePath($importFile);
            if ($importFileCheck) {
                // set the import file on the config helper
                $this->_getConfigHelper()->setImportFilePath(realpath($importFile));
            } else {
                $this->exitConfigurator(
                    sprintf(
                        'Unable to access import file \'%s\'',
                        $importFile
                    )
                );
            }
        } else {
            $config = $this->_checkConfig();
            if (!$config) {
                $this->exitConfigurator($this->_configError());
            }
        }

        $install = $this->_checkInstall();
        if (!$install) {
            $this->exitConfigurator($this->_installError());
        }

        $runAll = $shell->getArg(self::PARAM_RUN_ALL);

        if (!$runAll) {
            $this->exitConfigurator($this->_usageHelp());
        }

        if ($runAll) {
            $this->_runAll();

            return;
        }
        $this->exitConfigurator($this->_usageHelp());
    }

    /**
     * Prints Exit Message while ending the Shell Script
     *
     * @param string $msg Exit Message
     * @return void
     */
    protected function exitConfigurator($msg)
    {
        // @codingStandardsIgnoreStart
        die($msg);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Check if System Setting is correct
     *
     * @return bool
     */
    protected function _checkConfig()
    {
        return $this->_checkImportFilePath($this->_getConfigHelper()->getImportFilePath());
    }

    /**
     * @param string $importFilePath Import file path
     * @return bool
     */
    protected function _checkImportFilePath($importFilePath)
    {
        return $this->_getConfigHelper()->checkFile($importFilePath);
    }

    /**
     * @return Aoe_AttributeConfigurator_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('aoe_attributeconfigurator/config');
    }

    /**
     * Return Error Message
     *
     * @return string
     */
    protected function _configError()
    {
        return <<<USAGE
Error: System Config Settings missing or XML File could not be read.

USAGE;
    }

    /**
     * Check if Installation is correct
     *
     * @return string
     */
    protected function _checkInstall()
    {
        /** @var Aoe_AttributeConfigurator_Helper_Data $helper */
        $helper = Mage::helper('aoe_attributeconfigurator/data');

        return $helper->checkExtensionInstallStatus();
    }

    /**
     * Return Error Message
     *
     * @return string
     */
    protected function _installError()
    {
        return <<<USAGE
Error: Aoe_Attributeconfigurator has not been installed correctly. Check your System.

USAGE;
    }

    /**
     * Retrieve usage help message
     *
     * @return string
     */
    public function _usageHelp()
    {
        return <<<USAGE
Usage:  php aoe_attributeconfigurator.php -- <options>

  Options:
  --runAll                                      Run complete Import
  --importFile <filepath>                       Optional: Path to import file as override to configuration
  help                                          This help

USAGE;
    }

    /**
     * runAll Hook
     *
     * @return void
     */
    protected function _runAll()
    {
        /** @var Aoe_AttributeConfigurator_Model_Observer $observer */
        $observer = Mage::getModel('aoe_attributeconfigurator/observer');
        $observer->runAll();
    }
}
