<?php

/**
 * Class Aoe_AttributeConfigurator_Model_Sync_Import
 *
 * @category Model
 * @package  Aoe_AttributeConfigurator
 * @author   Firegento <contact@firegento.com>
 * @author   AOE Magento Team <team-magento@aoe.com>
 * @license  Open Software License v. 3.0 (OSL-3.0)
 * @link     https://github.com/AOEpeople/AttributeConfigurator
 * @see      https://github.com/magento-hackathon/AttributeConfigurator
 */
class Aoe_AttributeConfigurator_Model_Sync_Import
{
    /** @var Aoe_AttributeConfigurator_Model_Config $_config */
    protected $_config;

    /**
     * Sync Import Method coordinates the migration process from
     * XML File Data into the Magento Database
     *
     * return bool
     * @return void
     */
    public function import()
    {
        $this->_importAttributeSets();
        $this->_importAttributes();

        // TODO: Refactor this into the attribute model
        //if ($this->_validate($attributesets, $attributes)) {
            // 3. Connect Attributes with Attribute Sets using Attribute Groups
        //}
    }

    /**
     * Run the attributeset import task
     *
     * @return void
     * @throws Aoe_AttributeConfigurator_Model_Sync_Import_Exception
     */
    protected function _importAttributeSets()
    {
        /** @var Aoe_AttributeConfigurator_Model_Sync_Import_Attributeset $attributeSetModel */
        $attributeSetModel = Mage::getModel('aoe_attributeconfigurator/sync_import_attributeset');
        $attributeSetModel->run($this->_getConfig());
    }

    /**
     * Run the attribute import task
     *
     * @return void
     * @throws Aoe_AttributeConfigurator_Model_Sync_Import_Exception
     */
    protected function _importAttributes()
    {
        /** @var Aoe_AttributeConfigurator_Model_Attribute $attributeModel */
        $attributeModel = Mage::getModel('aoe_attributeconfigurator/attribute');
        $attributeModel->run($this->_getConfig());
    }


    /**
     * Lazy getter for the config model
     *
     * @return Aoe_AttributeConfigurator_Model_Config
     */
    protected function _getConfig()
    {
        if (isset($this->_config)) {
            return $this->_config;
        }

        /** @var Aoe_AttributeConfigurator_Model_Config $config */
        $config = Mage::getModel('aoe_attributeconfigurator/config');
        $this->_config = $config;

        return $config;
    }
}
