<?php

namespace Hellonico\Fixtures\Provider;

use Faker\Provider\Base;
use Faker\Provider\Image;

class Picsum extends Image
{

    /**
     * @param int $width
     * @param int $height
     * @param array $filters
     * @param string $format
     *
     * @return string
     */
    public static function imageUrl(
        $width = 640,
        $height = 480,
        $filters = [],
        $format = 'jpg',
        $unused = false,
        $unused_ = false
    ) {
        $format = strtolower($format);
        $url    = sprintf(
            'https://i.picsum.photos/id/%d/%d/%d.%s',
            $width,
            $height,
            Base::numberBetween(0, 1025),
            in_array($format, ['webp', 'jpg']) ? $format : 'jpg'
        );

        if (!empty($filters)) {
            $url .= '?' . http_build_query($filters);
        }

        return $url;
    }

    /**
     * @inheritDoc
     */
    public static function picsum(
        $dir = null,
        $width = 640,
        $height = 480,
        $filters = [],
        $format = 'jpg',
        $fullPath = true,
        $unused = true
    ) {
        return parent::image(
            $dir,
            $width,
            $height,
            $filters,
            $fullPath,
            $format
        );
    }
}
