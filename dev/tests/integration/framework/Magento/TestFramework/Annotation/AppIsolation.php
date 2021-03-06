<?php
/**
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */

/**
 * Implementation of the @magentoAppIsolation DocBlock annotation - isolation of global application objects in memory
 */
namespace Magento\TestFramework\Annotation;

class AppIsolation
{
    /**
     * Flag to prevent an excessive test case isolation if the last test has been just isolated
     *
     * @var bool
     */
    private $_hasNonIsolatedTests = true;

    /**
     * @var \Magento\TestFramework\Application
     */
    private $_application;

    /**
     * Constructor
     *
     * @param \Magento\TestFramework\Application $application
     */
    public function __construct(\Magento\TestFramework\Application $application)
    {
        $this->_application = $application;
    }

    /**
     * Isolate global application objects
     */
    protected function _isolateApp()
    {
        if ($this->_hasNonIsolatedTests) {
            $this->_application->reinitialize();
            $this->_hasNonIsolatedTests = false;
        }
    }

    /**
     * Isolate application before running test case
     */
    public function startTestSuite()
    {
        $this->_isolateApp();
    }

    /**
     * Handler for 'endTest' event
     *
     * @param \PHPUnit_Framework_TestCase $test
     * @throws \Magento\Framework\Exception
     */
    public function endTest(\PHPUnit_Framework_TestCase $test)
    {
        $this->_hasNonIsolatedTests = true;

        /* Determine an isolation from doc comment */
        $annotations = $test->getAnnotations();
        if (isset($annotations['method']['magentoAppIsolation'])) {
            $isolation = $annotations['method']['magentoAppIsolation'];
            if ($isolation !== ['enabled'] && $isolation !== ['disabled']) {
                throw new \Magento\Framework\Exception(
                    'Invalid "@magentoAppIsolation" annotation, can be "enabled" or "disabled" only.'
                );
            }
            $isIsolationEnabled = $isolation === ['enabled'];
        } else {
            /* Controller tests should be isolated by default */
            $isIsolationEnabled = $test instanceof \Magento\TestFramework\TestCase\AbstractController;
        }

        if ($isIsolationEnabled) {
            $this->_isolateApp();
        }
    }
}
