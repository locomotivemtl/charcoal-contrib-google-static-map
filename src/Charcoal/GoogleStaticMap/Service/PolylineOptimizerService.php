<?php

namespace Charcoal\GoogleStaticMap\Service;

/**
 * Optimize geometrical path by smartly removing excess points.
 *
 * Polyline Optimizer Service
 */
class PolylineOptimizerService
{
    const ANGLE_THRESHOLD = 45;
    const SUBDIVISION_THRESHOLD = 5;
    const PRECISION = 1;
    const MAX_SUBDIVISION = 8;

    /**
     * @var array
     */
    private $options;

    /**
     * PathOptimizerService constructor.
     * @param array $data Constructor data.
     */
    public function __construct(array $data = [])
    {
        if (isset($data['options'])) {
            $this->setOptions($data['options']);
        }
    }

    /**
     * @param array $path A geometrical path.
     * @return array
     */
    public function optimizePath(array $path)
    {
        $options = $this->options();
        $angleThreshold = $options['angleThreshold'];
        $subdivisionThreshold = $options['subdivisionThreshold'];
        $precision = $options['precision'];
        $maxSubdivision = $options['maxSubdivision'];

        $newArray = [];
        $pointAngleMap = array_pad([], count($path), 0);
        $cumulativeAngle = 0;
        $lastIndex = 0;
        foreach ($path as $index => $item) {
            if ($index === 0 || $index === (count($path) - 1)) {
                $newArray[] = $item;
                continue;
            }

            $angle = $this->middlePointAngle($item, $path[($index - 1)], $path[($index + 1)]);
            $pointAngleMap[$index] = abs($angle);
            $cumulativeAngle += $angle;

            // When with hit a definitive angle change
            if (abs($cumulativeAngle) >= $angleThreshold) {
                $angleMap = array_slice(
                    $pointAngleMap,
                    ($lastIndex + 1),
                    ($index - $lastIndex - 1),
                    true
                );
                arsort($angleMap);

                $numPoints = floor(count($angleMap) * $precision / 10);
                $numPoints = $numPoints <= $maxSubdivision ? $numPoints : $maxSubdivision;
                $extraPointsIndexes = array_slice($angleMap, 0, $numPoints, true);

                // Get rid of any insignificant angle.
                $extraPointsIndexes = array_filter($extraPointsIndexes, function ($value) use ($subdivisionThreshold) {
                    return $value > $subdivisionThreshold;
                });
                $extraPointsIndexes = array_keys($extraPointsIndexes);

                sort($extraPointsIndexes);
                foreach ($extraPointsIndexes as $pointIndex) {
                    $newArray[] = $path[$pointIndex];
                }

                $newArray[] = $item;
                $lastIndex = $index;
                $cumulativeAngle = 0;
            }
        }

        return $newArray;
    }

    /**
     * @param array $point    A point (2d coordinates).
     * @param array $previous A point (2d coordinates).
     * @param array $next     A point (2d coordinates).
     * @return float
     */
    private function middlePointAngle(array $point, array $previous, array $next)
    {
        $currentAngle = $this->calculateAngle($previous, $point);
        $nextAngle = $this->calculateAngle($point, $next);

        return ($currentAngle - $nextAngle);
    }

    /**
     * Calculate the angle difference between two points.
     *
     * @param array $point1 A point (2d coordinates).
     * @param array $point2 A point (2d coordinates).
     * @return float
     */
    protected function calculateAngle(array $point1, array $point2)
    {
        $x = ($point2[0] - $point1[0]);
        $y = ($point2[1] - $point1[1]);

        return rad2deg(atan2($x, $y));
    }

    /**
     * @return array
     */
    public function options()
    {
        if (empty($this->options)) {
            return $this->defaultOptions();
        }

        return $this->options;
    }

    /**
     * @param array $options Options for PathOptimizerService.
     * @return self
     */
    public function setOptions(array $options)
    {
        $this->options = array_replace($this->defaultOptions(), $options);

        return $this;
    }

    /**
     * @return array
     */
    private function defaultOptions()
    {
        return [
            'angleThreshold'       => self::ANGLE_THRESHOLD,
            'subdivisionThreshold' => self::SUBDIVISION_THRESHOLD,
            'precision'            => self::PRECISION,
            'maxSubdivision'       => self::MAX_SUBDIVISION,
        ];
    }
}
