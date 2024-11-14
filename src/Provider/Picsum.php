<?php

namespace Hellonico\Fixtures\Provider;

use Faker\Provider\Base;
use Faker\Provider\Image;

class Picsum extends Image {


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
		$unused_ = false,
		$unused3 = false
	) {
		$format = strtolower( $format );
		$url    = sprintf(
			'https://picsum.photos/%d/%d',
			$width,
			$height
		);

		if ( ! empty( $filters ) ) {
			$url .= '?' . http_build_query( (array) $filters );
		}

		return $url;
	}

	/**
	 * We need to be able to follow redirects for picsum to work, therefore we provide a custom version of parent::image()
	 * which sets CURLOPT_FOLLOWLOCATION to true.
	 *
	 * @param null $dir
	 * @param int $width
	 * @param int $height
	 * @param array $filters
	 * @param string $format
	 * @param bool $fullPath
	 * @param bool $unused
	 *
	 * @return false|\RuntimeException|string
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
		$dir = is_null( $dir ) ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
		// Validate directory path
		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			throw new \InvalidArgumentException( sprintf( 'Cannot write to directory "%s"', $dir ) );
		}

		// Generate a random filename. Use the server address so that a file
		// generated at the same time on a different server won't have a collision.
		$name     = md5( uniqid( empty( $_SERVER['SERVER_ADDR'] ) ? '' : $_SERVER['SERVER_ADDR'], true ) );
		$filename = $name . '.jpg';
		$filepath = $dir . DIRECTORY_SEPARATOR . $filename;

		$url = static::imageUrl( $width, $height, $filters, $format, false, false );

		// save file
		if ( function_exists( 'curl_exec' ) ) {
			// use cURL
			$fp = fopen( $filepath, 'wb' );
			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_FILE, $fp );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // Required for picsum to follow redirect.
			curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:104.0) Gecko/20100101 Firefox/104.0' ); // required as otherwise 403 error from picsum
			$success = curl_exec( $ch ) && curl_getinfo( $ch, CURLINFO_HTTP_CODE ) === 200;
			fclose( $fp );
			curl_close( $ch );

			if ( ! $success ) {
				unlink( $filepath );

				// could not contact the distant URL or HTTP error - fail silently.
				return false;
			}
		} elseif ( ini_get( 'allow_url_fopen' ) ) {
			// use remote fopen() via copy()
			$success = copy( $url, $filepath );
		} else {
			return new \RuntimeException( 'The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()' );
		}

		return $fullPath ? $filepath : $filename;
	}
}
