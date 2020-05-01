<?php

namespace Hellonico\Fixtures\Provider;

use Faker\Provider\Base;

class Picsum extends Base {
	/**
	 * @param int $width
	 * @param int $height
	 * @param bool $randomize
	 * @param bool $gray
	 *
	 * @return string
	 */
	public static function imageUrl($width = 640, $height = 480, $randomize = true, $gray = false)
	{
		$baseUrl = "https://picsum.photos/";
		$url = "{$width}/{$height}";

		if ($gray) {
			$url .= '?grayscale';
		}

		if ($randomize) {
			$url .= '?' . static::randomNumber(5, true);
		}

		return $baseUrl . $url;
	}

	/**
	 * Download a remote placeholder image from picsum. This is an alternative to the image provider which uses
	 * LoremPixel which appears to be really slow and unreliable.
	 *
	 * @param null $dir
	 * @param int $width
	 * @param int $height
	 * @param bool $fullPath
	 * @param bool $randomize
	 * @param bool $gray
	 *
	 * @return bool|\RuntimeException|string
	 */
	public static function picsum($dir = null, $width = 640, $height = 480, $fullPath = true, $randomize = true, $gray = false)
	{
		$dir = is_null($dir) ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
		// Validate directory path
		if (!is_dir($dir) || !is_writable($dir)) {
			throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
		}

		// Generate a random filename. Use the server address so that a file
		// generated at the same time on a different server won't have a collision.
		$name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
		$filename = $name .'.jpg';
		$filepath = $dir . DIRECTORY_SEPARATOR . $filename;

		$url = static::imageUrl($width, $height, $randomize, $gray);

		// save file
		if (function_exists('curl_exec')) {
			// use cURL
			$fp = fopen($filepath, 'w');
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			$success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
			fclose($fp);
			curl_close($ch);

			if (!$success) {
				unlink($filepath);

				// could not contact the distant URL or HTTP error - fail silently.
				return false;
			}
		} elseif (ini_get('allow_url_fopen')) {
			// use remote fopen() via copy()
			$success = copy($url, $filepath);
		} else {
			return new \RuntimeException('The picsum formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
		}

		return $fullPath ? $filepath : $filename;
	}
}
