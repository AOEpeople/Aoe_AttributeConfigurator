<?php

/**
 * Class Aoe_AttributeConfigurator_Test_Model_Attribute
 *
 * @category Test
 * @package  Aoe_AttributeConfigurator
 * @author   FireGento Team <team@firegento.com>
 * @author   AOE Magento Team <team-magento@aoe.com>
 * @license  Open Software License v. 3.0 (OSL-3.0)
 * @link     https://github.com/AOEpeople/AttributeConfigurator
 * @see      https://github.com/magento-hackathon/AttributeConfigurator
 */
class Aoe_AttributeConfigurator_Test_Model_Sync_Import_Attribute extends Aoe_AttributeConfigurator_Test_Model_Case
{
    /**
     * @var array set => array of groups
     */
    private $_attributeSets = [
        'Gitarren & Saiteninstrumente' => [
            'One',
            'Two',
            'Three',
            'Four',
        ],
        'Flöten/Blechbläser' => [
            'One',
            'Two',
            'Salsa',
            'Lambada',
        ],
    ];

    /**
     * Set Up Attribute Sets
     */
    public function setUp()
    {
        parent::setUp();
        $entityTypeId = Mage::getModel('catalog/product')
            ->getResource()
            ->getEntityType()
            ->getId();

        foreach ($this->_attributeSets as $name => $groups) {
            /** @var Mage_Eav_Model_Entity_Attribute_Set $attributeSet */
            $attributeSet = Mage::getModel('eav/entity_attribute_set')
                ->setEntityTypeId($entityTypeId)
                ->setAttributeSetName($name);

            $attributeSet->save();

            foreach ($groups as $group) {
                $modelGroup = Mage::getModel('eav/entity_attribute_group');
                $modelGroup->setAttributeGroupName($group)->setAttributeSetId($attributeSet->getId());
                $modelGroup->save();
            }
        }
    }

    /**
     * Delete Attribute Sets
     */
    public function tearDown()
    {
        /** @var Mage_Eav_Model_Entity_Attribute_Set $attributeSet */
        $attributeSet = Mage::getModel('eav/entity_attribute_set');
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $setCollection */
        $setCollection = $attributeSet->getCollection();
        $setCollection->addFieldToFilter('attribute_set_name', ['in' => array_keys($this->_attributeSets)]);
        $setCollection->walk(function (Mage_Eav_Model_Entity_Attribute_Set $item) {$item->delete();});

        parent::tearDown();
    }

    /**
     * @test
     * @return Aoe_AttributeConfigurator_Model_Sync_Import_Attribute
     */
    public function checkClass()
    {
        $model = Mage::getModel('aoe_attributeconfigurator/sync_import_attribute');
        $this->assertInstanceOf(
            'Aoe_AttributeConfigurator_Model_Sync_Import_Attribute',
            $model
        );

        return $model;
    }

    /**
     * @test
     * @return void
     */
    public function checkProcessAttributeCallCount()
    {
        /** @var EcomDev_PHPUnit_Mock_Proxy|Aoe_AttributeConfigurator_Model_Sync_Import_Attribute $mock */
        $mock = $this->getModelMock(
            'aoe_attributeconfigurator/sync_import_attribute',
            ['_processAttribute', '_createAttribute']
        );

        $mock->expects($this->exactly(2))
            ->method('_processAttribute');

        // Attribute already has been created by ProcessAttribute
        $mock->expects($this->exactly(0))
            ->method('_createAttribute');

        $this->_mockConfigHelperLoadingXml();
        $config = $this->_getConfigModel();

        $mock->run($config);
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @param string   $label          Data provider label
     * @param string[] $attributeCodes Data provider attribute codes
     * @return void
     */
    public function checkCreateAttributeCallCount($label, $attributeCodes)
    {
        // Remove Attribute from XML before checking again -> Attribute otherwise already exists
        $this->_removeAttributes($attributeCodes);

        /** @var EcomDev_PHPUnit_Mock_Proxy|Aoe_AttributeConfigurator_Model_Sync_Import_Attribute $mock */
        $mock = $this->getModelMock(
            'aoe_attributeconfigurator/sync_import_attribute',
            ['_createAttribute']
        );

        $mock->expects($this->exactly(2))
            ->method('_createAttribute');

        $this->_mockConfigHelperLoadingXml();
        $config = $this->_getConfigModel();

        $mock->run($config);
    }

    /**
     * @test
     * @loadFixture
     * @return void
     */
    public function checkCreateAttributeCreationInDb()
    {
        $attributeCodes = $this->expected('data')->getAttributes();

        // cleanup before this test
        $this->_removeAttributes($attributeCodes);

        // mock test xml loading
        $this->_mockConfigHelperLoadingXml(__FUNCTION__ . '.xml');

        // run the attribute import on the model
        /** @var Aoe_AttributeConfigurator_Model_Sync_Import_Attribute $importModel */
        $importModel = Mage::getModel('aoe_attributeconfigurator/sync_import_attribute');

        /** @var Aoe_AttributeConfigurator_Model_Config $configModel */
        $configModel = Mage::getModel('aoe_attributeconfigurator/config');

        $importModel->run($configModel);

        // check expectations
        $createdAttributes = $this->_fetchAttributes($attributeCodes);

        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        foreach ($createdAttributes as $attribute) {
            $this->assertContains(
                $attribute->getAttributeCode(),
                $this->expected('data')->getCreated(),
                $attribute->getAttributeCode() . ' was not expected to be created.'
            );
        }

        $this->assertCount(
            count($this->expected('data')->getCreated()),
            $createdAttributes,
            'Not all expected attributes have been created.'
        );

        // cleanup possible post test attributes
        $this->_removeAttributes($attributeCodes);
    }

    /**
     * Fetch a collection of attributes filtered by codes
     *
     * @param string[] $attributeCodes Array of attribute codes
     * @return Mage_Eav_Model_Resource_Attribute_Collection
     * @throws Mage_Core_Exception
     */
    protected function _fetchAttributes($attributeCodes)
    {
        // cleanup possible pre test attributes
        /** @var Mage_Catalog_Model_Product $productModel */
        $productModel = Mage::getModel('catalog/product');

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $collection */
        $collection = Mage::getModel('catalog/entity_attribute')
            ->getCollection();
        $collection->setEntityTypeFilter($productModel->getResource()->getEntityType()->getEntityTypeId());
        $collection->setCodeFilter($attributeCodes);

        return $collection;
    }

    /**
     * Remove attributes from the database
     *
     * @param string[] $attributeCodes List of attribute codes to be removed
     * @return void
     */
    protected function _removeAttributes($attributeCodes)
    {
        if (count($attributeCodes) == 0) {
            // Don´t delete attributes if no attributes are passed
            return;
        }
        $collection = $this->_fetchAttributes($attributeCodes);
        foreach ($collection as $_attribute) {
            /** @var Mage_Eav_Model_Attribute $_attribute */
            // @codingStandardsIgnoreStart
            $_attribute->delete();
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function _loadAttribute()
    {
        /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $attributes */
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection');

        $this->assertGreaterThan(0, $attributes->getSize(), 'attributes configured in the system');

        return $attributes->getFirstItem();
    }
}
