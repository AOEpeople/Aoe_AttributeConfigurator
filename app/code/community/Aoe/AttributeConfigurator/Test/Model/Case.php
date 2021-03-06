<?php

/**
 * Class Aoe_AttributeConfigurator_Test_Model_Case
 *
 * Abstract class for model test cases with some helper functionality
 *
 * @category Test
 * @package  Aoe_AttributeConfigurator
 * @author   FireGento Team <team@firegento.com>
 * @author   AOE Magento Team <team-magento@aoe.com>
 * @license  Open Software License v. 3.0 (OSL-3.0)
 * @link     https://github.com/AOEpeople/AttributeConfigurator
 * @see      https://github.com/magento-hackathon/AttributeConfigurator
 */
abstract class Aoe_AttributeConfigurator_Test_Model_Case extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Get a path to a common model fixture file
     *
     * @param string $fixture Fixture filename
     * @return string
     */
    protected function _getCommonFixturePath($fixture = 'test_attributes.xml')
    {
        $path = implode(
            DS,
            [
                Mage::getModuleDir('', 'Aoe_AttributeConfigurator'),
                'Test',
                'Model',
                'fixtures',
                $fixture
            ]
        );

        return $path;
    }

    /**
     * Get a path to a common model fixture file
     *
     * @param string $fixture Fixture filename
     * @return string
     */
    protected function _getSpecificFixturePath($fixture = 'test_attributes.xml')
    {
        $class = str_replace('Aoe_AttributeConfigurator_', '', get_class($this));
        $path = explode('_', $class);
        array_unshift($path, Mage::getModuleDir('', 'Aoe_AttributeConfigurator'));
        array_push($path, 'fixtures', $fixture);

        return implode(DS, $path);
    }

    /**
     * Mock the modules config helper and use a fixture xml for testing.
     * The mocked helper is also replaced using replaceByMock
     *
     * @param string $fixture Fixture file name
     * @return EcomDev_PHPUnit_Mock_Proxy|Aoe_AttributeConfigurator_Model_Config
     */
    protected function _mockConfigHelperLoadingXml($fixture = 'test_attributes.xml')
    {
        $mockedHelper = $this->getHelperMock(
            'aoe_attributeconfigurator/config',
            ['getImportFilePath']
        );

        $filePath = $this->_getCommonFixturePath($fixture);
        if (!file_exists($filePath)) {
            $filePath = $this->_getSpecificFixturePath($fixture);
        }

        $mockedHelper->expects($this->any())
            ->method('getImportFilePath')
            ->will($this->returnValue($filePath));

        $this->replaceByMock(
            'helper',
            'aoe_attributeconfigurator/config',
            $mockedHelper
        );

        return $mockedHelper;
    }

    /**
     * Get the config model - useful if the config helper is mocked to load fixture xml files
     *
     * @return Aoe_AttributeConfigurator_Model_Config
     */
    protected function _getConfigModel()
    {
        return Mage::getModel('aoe_attributeconfigurator/config');
    }
}
