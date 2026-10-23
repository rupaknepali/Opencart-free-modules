<?php
namespace Opencart\Catalog\Controller\Extension\WebpImages\Module;
/**
 * Class WebpImages
 *
 * WebP Images for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/model/tool/image.resize/after -> resize
 *
 * For every resized image in image/cache/ (e.g. product-500x500.jpg) a copy is saved next to it
 * (product-500x500.jpg.webp) and its URL is returned instead, as long as it is smaller.
 *
 * @package Opencart\Catalog\Controller\Extension\WebpImages\Module
 */
class WebpImages extends \Opencart\System\Engine\Controller {
	/**
	 * Resize
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args   [filename, width, height, default]
	 * @param mixed             $output URL of the resized image
	 *
	 * @return void
	 */
	public function resize(string &$route, array &$args, &$output): void {
		if (!$this->config->get('module_webp_images_status') || !is_string($output) || $output === '' || !$this->isWebPage()) {
			return;
		}

		try {
			$position = strpos($output, 'image/cache/');

			if ($position === false) {
				return;
			}

			// The model encodes spaces as %20.
			$path = rawurldecode(substr($output, $position + strlen('image/')));

			$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

			// GIFs are skipped so animations keep working.
			if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
				return;
			}

			$source = DIR_IMAGE . $path;

			if (!is_file($source) || strpos(str_replace('\\', '/', (string)realpath($source)), DIR_IMAGE . 'cache/') !== 0) {
				return;
			}

			$format = $this->config->get('module_webp_images_format') == 'avif' && function_exists('imageavif') ? 'avif' : 'webp';

			$target = $source . '.' . $format;

			if (!is_file($target) || filemtime($target) < filemtime($source)) {
				if (!$this->convert($source, $target, $extension, $format)) {
					return;
				}
			}

			// Small PNG icons are sometimes bigger as WebP; keep the original then.
			clearstatcache(true, $target);

			if (filesize($target) < filesize($source)) {
				$output .= '.' . $format;
			}
		} catch (\Throwable $e) {
			$this->log->write('WebP Images: ' . $e->getMessage());
		}
	}

	/**
	 * Is Web Page
	 *
	 * Images in emails must stay JPEG/PNG because Outlook for Windows can't show WebP.
	 * Emails from extensions are sent by cron.php or from the admin "run" button, never by the storefront.
	 *
	 * @return bool
	 */
	private function isWebPage(): bool {
		if (PHP_SAPI == 'cli' || defined('DIR_CATALOG') || $this->registry->has('webp_images_skip')) {
			return false;
		}

		$script = basename((string)($this->request->server['SCRIPT_NAME'] ?? ''));

		return $script != 'cron.php';
	}

	/**
	 * Convert
	 *
	 * @param string $source
	 * @param string $target
	 * @param string $extension
	 * @param string $format webp or avif
	 *
	 * @return bool
	 */
	private function convert(string $source, string $target, string $extension, string $format): bool {
		if ($format == 'webp' && !function_exists('imagewebp')) {
			return false;
		}

		if ($extension == 'png') {
			$image = @imagecreatefrompng($source);

			if ($image) {
				imagepalettetotruecolor($image);
				imagealphablending($image, false);
				imagesavealpha($image, true);
			}
		} else {
			$image = @imagecreatefromjpeg($source);
		}

		if (!$image) {
			return false;
		}

		$quality = (int)$this->config->get('module_webp_images_quality') ?: 80;
		$quality = max(1, min(100, $quality));

		// Write to a temporary file first, so a visitor never gets a half-written image.
		$temporary = $target . '.' . oc_token(8) . '.tmp';

		if ($format == 'avif') {
			$result = imageavif($image, $temporary, $quality, 6);
		} else {
			$result = imagewebp($image, $temporary, $quality);
		}

		unset($image);

		if (!$result || !is_file($temporary) || !filesize($temporary)) {
			@unlink($temporary);

			return false;
		}

		return rename($temporary, $target);
	}
}
