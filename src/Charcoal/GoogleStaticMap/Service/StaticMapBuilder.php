<?php

namespace Charcoal\GoogleStaticMap\Service;

use Charcoal\Config\AbstractEntity;
use Charcoal\Config\ConfigInterface;
use Charcoal\Config\EntityInterface;
use Charcoal\Config\GenericConfig;
use Charcoal\GoogleStaticMap\Object\StaticMap;
use Polyline;

/**
 * Provides tools to generate google static map compatible urls from provided map data.
 * The service is configurable.
 * It can also output a rendered image of the desired map.
 *
 * Static Map Generator Service
 */
class StaticMapBuilder
{
    /**
     * @var array
     */
    private $geometries = [
        'path'   => [],
        'marker' => [],
    ];

    const DEFAULT_POLYGON_PATH_COLOR = 'ff0000';
    const DEFAULT_POLYGON_FILL_COLOR = 'ff6666';
    const MIN_POINTS_TO_OPTIMIZE = 100;
    const API_URL = 'https://maps.googleapis.com/maps/api/staticmap?';

    /**
     * @var PolylineOptimizerService
     */
    private $polylineOptimizer;

    /**
     * @var Polyline
     */
    private $polylineEncoder;

    /**
     * @var StaticMap
     */
    private $model;

    /**
     * @var ConfigInterface
     */
    private $mapConfig;

    /**
     * @var ConfigInterface
     */
    private $apiKey;

    /**
     * StaticMapGeneratorService constructor.
     * @param array $data Init data.
     */
    public function __construct(array $data = [])
    {
        $this->setPolylineOptimizer($data['polyline/optimizer']);
        $this->setPolylineEncoder($data['polyline/encoder']);
        $this->setModel($data['model']);

        $this->apiKey = $data['map/key'];
        $this->mapConfig = new GenericConfig($data['map/config']);
    }

    /**
     * @param array $geometries An array containing the map geometries (optional).
     * @return AbstractEntity|StaticMap
     */
    public function create(array $geometries = [])
    {
        if (!empty($geometries)) {
            $this->setGeometries($geometries);
        }

        $classname = $this->getModel();
        $object = new $classname;
        $staticMap = $object->setData($this->staticMapData());

        return $staticMap;
    }

    /**
     * Reset the builder.
     * @return void
     */
    public function reset()
    {
        $this->geometries = [
            'path'   => [],
            'marker' => [],
        ];
    }

    /**
     * @return array
     */
    private function staticMapData()
    {
        $urlParts = [
            $this->parseStyles(),
            $this->parseGeometries(),
            "key={$this->apiKey}",
        ];

        return [
            'url' => self::API_URL.join('&', $urlParts)
        ];
    }

    /**
     * @return string
     */
    private function parseStyles()
    {
        // Maybe we should give to choice of a style ident ?
        $styles = $this->mapConfig->get('styles');
        $styles = array_map(function ($item) {
            $params = [];
            if (!isset($item['stylers']) || empty($item['stylers'])) {
                return;
            }

            if (isset($item['featureType'])) {
                $params['feature'] = $item['featureType'];
            }
            if (isset($item['elementType'])) {
                $params['element'] = $item['elementType'];
            }


            array_walk_recursive($item['stylers'], function($style, $type) use (&$params) {
                if (preg_match('/#[0-9a-f]{6}/is', $style)) {
                    $style = str_replace('#', '0x', $style);
                }

                $params[$type] = $style;
            });

            return $params;
        }, $styles);

        $styles = array_map(function($style) {
            return 'style='.str_replace('=', ':', http_build_query($style, null, '|'));
        }, $styles);

        return join('&', $styles);
    }

    /**
     * Parse the different geometries into a set of static map compatible query parameter.
     *
     * @return string
     */
    private function parseGeometries()
    {
        $data = [];
        foreach ($this->geometries as $type => $items) {
            // markers are special.
            if ($type === 'markers') {
                $data[] = 'markers='.join('|', $items);

                continue;
            }

            // All items by types should have the type as param key.
            $items = array_map(function ($val) use ($type) {
                return sprintf('%s=%s', $type, $val);
            }, $items);

            $data = array_merge($data, $items);
        }

        return join('&', $data);
    }

    /**
     * Allows to set all geometries at once.
     *
     * @param array $geometries An array of geometries.
     * @return self
     */
    public function setGeometries(array $geometries)
    {
        foreach ($geometries as $type => $items) {
            foreach ($items as $item) {
                switch ($type) {
                    case 'markers':
                    case 'points':
                        $this->addMarker($item);
                        break;
                    case 'polyline':
                    case 'polylines':
                        $this->addPolyline($item);
                        break;
                    case 'polygon':
                    case 'polygons':
                        $this->addPolygon($item);
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * @param array $points Array of points.
     * @return self
     */
    public function addPolygon(array $points)
    {
        $points = (count($points) > self::MIN_POINTS_TO_OPTIMIZE)
            ? $this->getPolylineOptimizer()->optimizePath($points)
            : $points;

        // Make sure the polygon closes on itself.
        $numPoints = count($points);
        if ($points[0][0] !== $points[($numPoints - 1)][0] ||
            $points[0][1] !== $points[($numPoints - 1)][1]
        ) {
            $points[] = $points[0];
        }

        $polygon = $this->getPolylineEncoder()->Encode($points);

        // Should come from config
        $opacity = 0.35;
        $opacity = $this->opacity2hex($opacity);

        $properties = [
            'fillcolor' => '0x'.self::DEFAULT_POLYGON_FILL_COLOR.($opacity ? $opacity : ''),
            'color'     => '0x'.self::DEFAULT_POLYGON_PATH_COLOR,
            'weight'    => 3,
            'enc'       => $polygon,
        ];

        $parsedProperties = str_replace('=', ':', http_build_query($properties, null, '|'));
        $this->geometries['path'][] = $parsedProperties;

        return $this;
    }

    /**
     * @param array $points Array of points.
     * @return self
     */
    public function addPolyline(array $points)
    {
        $points = (count($points) > self::MIN_POINTS_TO_OPTIMIZE)
            ? $this->getPolylineOptimizer()->optimizePath($points)
            : $points;

        $polyline = $this->getPolylineEncoder()->Encode($points);

        $properties = [
            'color'  => '0x'.self::DEFAULT_POLYGON_PATH_COLOR,
            'weight' => 3,
            'enc'    => $polyline
        ];

        $parsedProperties = str_replace('=', ':', http_build_query($properties, null, '|'));
        $this->geometries['path'][] = $parsedProperties;

        return $this;
    }

    /**
     * @param array $points Array of points.
     */
    public function addMarker(array $points)
    {
        $this->geometries['markers'][] = join(',', $points);
    }

    // Utils
    // ==========================================================================

    /**
     * @param float $opacity Unit between 0 and 1.
     * @return string
     */
    private function opacity2hex($opacity)
    {
        $hex = dechex($opacity * 255);

        return str_pad($hex, 2, '0', STR_PAD_LEFT);
    }

    // Dependencies
    // ==========================================================================

    /**
     * @return PolylineOptimizerService
     */
    public function getPolylineOptimizer()
    {
        return $this->polylineOptimizer;
    }

    /**
     * @param mixed $polylineOptimizer PolylineOptimizer for StaticMapGeneratorService.
     * @return self
     */
    public function setPolylineOptimizer($polylineOptimizer)
    {
        $this->polylineOptimizer = $polylineOptimizer;

        return $this;
    }

    /**
     * @return Polyline
     */
    public function getPolylineEncoder()
    {
        return $this->polylineEncoder;
    }

    /**
     * @param mixed $polylineEncoder PolylineEncoder for StaticMapGeneratorService.
     * @return self
     */
    public function setPolylineEncoder($polylineEncoder)
    {
        $this->polylineEncoder = $polylineEncoder;

        return $this;
    }

    /**
     * @return StaticMap
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param StaticMap|EntityInterface $model Model for StaticMapBuilderService.
     * @return self
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }
}
