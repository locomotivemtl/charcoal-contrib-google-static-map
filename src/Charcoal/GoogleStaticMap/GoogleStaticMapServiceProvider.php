<?php

namespace Charcoal\GoogleStaticMap;

// from 'pimple'
use Charcoal\GoogleStaticMap\Service\PolylineOptimizerService;
use Charcoal\GoogleStaticMap\Service\StaticMapGeneratorService;
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
         * @return StaticMapGeneratorService
         */
        $container['google/static/map/generator'] = function (Container $container) {
            return new StaticMapGeneratorService([
                'polyline/encoder' => $container['google/static/map/polyline-encoder'],
                'polyline/optimizer' => $container['google/static/map/polyline-optimizer'],
            ]);
        };

        $container['google/static/map/generator']->process();
    }
}
