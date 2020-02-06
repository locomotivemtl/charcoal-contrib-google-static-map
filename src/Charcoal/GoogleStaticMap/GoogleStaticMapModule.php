<?php

namespace Charcoal\GoogleStaticMap;

use Charcoal\App\Module\AbstractModule;

/**
 * Class GoogleStaticMapModule
 */
class GoogleStaticMapModule extends AbstractModule
{
    /**
     * Setup the module's dependencies.
     *
     * @return AbstractModule
     */
    public function setup()
    {
        $container = $this->app()->getContainer();

        // Define ServiceProviders and Config if needed.
        $moduleServiceProvider = new GoogleStaticMapServiceProvider();
        $container->register($moduleServiceProvider);

        return $this;
    }
}
