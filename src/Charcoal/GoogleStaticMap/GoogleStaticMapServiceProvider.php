<?php

namespace Charcoal\GoogleStaticMap;

// local dependencies
use Charcoal\GoogleStaticMap\Object\StaticMap;
use Charcoal\GoogleStaticMap\Service\PolylineOptimizerService;
use Charcoal\GoogleStaticMap\Service\StaticMapBuilder;

// from 'pimple'
use Pimple\Container;
use Pimple\ServiceProviderInterface;

// from 'emcconville/google-map-polyline-encoding-tool'
use Polyline;

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
        /**
         * @return Polyline
         */
        $container['google/static/map/polyline-encoder'] = function () {
            return new Polyline();
        };

        /**
         * @return PolylineOptimizerService
         */
        $container['google/static/map/polyline-optimizer'] = function () {
            return new PolylineOptimizerService();
        };

        /**
         * @param Container $container Pimple DI container.
         * @return StaticMapBuilder
         */
        $container['google/static/map/builder'] = function (Container $container) {
            return new StaticMapBuilder([
                'polyline/encoder'   => $container['google/static/map/polyline-encoder'],
                'polyline/optimizer' => $container['google/static/map/polyline-optimizer'],
                'model'              => StaticMap::class,
                'map/config'         => $container['config']['map'],
                'map/key'            => $container['config']['apis.google.maps.key'],
            ]);
        };
    }
}
