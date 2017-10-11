<?php

namespace rollun\test;

use rollun\test\Helper;
use Zend\ServiceManager\Factory\InvokableFactory;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'templates' => $this->getTemplates(),
            'view_helpers' => $this->getViewHelpers(),
        ];
    }

    /**
     * Returns the container dependencies
     *
     * @return array
     */
    public function getViewHelpers()
    {
        return [
            'aliases' => [
                'redText' => Helper\RetTextHelper::class,
                'redtext' => Helper\RetTextHelper::class,
            ],
            'invokables' => [],
            'factories' => [
                Helper\RetTextHelper::class => InvokableFactory::class
            ],
            'abstract_factories' => [],
        ];
    }

    /**
     * Returns the templates configuration
     *
     * @return array
     */
    public function getTemplates()
    {
        return [
            'paths' => [
                'test-app' => [__DIR__ . '/../templates/test-app'],
                'test-error' => [__DIR__ . '/../templates/test-error'],
                'test-layout' => [__DIR__ . '/../templates/test-layout'],
                'test-helper' => [__DIR__ . '/../templates/test-helper'],
            ],
            'layout' => 'test-layout::default',
        ];
    }
}
