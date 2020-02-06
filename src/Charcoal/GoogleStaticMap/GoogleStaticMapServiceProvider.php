<?php

namespace Charcoal\GoogleStaticMap;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Google Static Map Service Provider
 */
class GoogleStaticMapServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container Pimple DI container.
     * @return void
     */
    public function register(Container $container)
    {
        $test = new \Polyline();
        $test->decode('45.377170941525335 -73.98860107465208,45.3778341345161 -73.97684227033079,45.37171437667629 -73.99375091596067,45.37554307120726 -73.99671207471312,45.371232003074844 -73.9826787571472,45.37135259686089 -73.98709903760374,45.38133084165583 -73.99765621228636,45.377170941525335 -73.98860107465208');

        error_log(var_export($test, true));

        $container['google/static/map/polyline-encoding'] = function () {
            return new \Polyline();
        };
    }
}
