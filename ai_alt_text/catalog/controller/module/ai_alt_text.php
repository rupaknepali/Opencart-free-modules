<?php
namespace Opencart\Catalog\Controller\Extension\AiAltText\Module;
/**
 * Class AiAltText
 *
 * AI Image Alt Text for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/view/product/product/after -> output
 *   catalog/view/product/thumb/after   -> output
 *
 * Replaces the alt attribute of product images with the saved alt text. Resized images such as
 * image/cache/catalog/demo/canon-eos-5d-1-500x500.webp are matched to catalog/demo/canon-eos-5d-1.
 *
 * @package Opencart\Catalog\Controller\Extension\AiAltText\Module
 */
class AiAltText extends \Opencart\System\Engine\Controller {
	/**
	 * Alt texts already looked up in this request. image_key => alt ('' if none)
	 *
	 * @var array<string, string>
	 */
	private static array $cache = [];

	/**
	 * Output
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function output(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_ai_alt_text_status') || !str_contains($output, '<img')) {
			return;
		}

		try {
			preg_match_all('/<img\b[^>]*>/i', $output, $matches);

			$tags = [];

			foreach ($matches[0] as $tag) {
				$key = $this->getKey($tag);

				if ($key !== '') {
					$tags[$tag] = $key;
				}
			}

			if (!$tags) {
				return;
			}

			$this->loadAlts(array_unique(array_values($tags)));

			$replace = [];

			foreach ($tags as $tag => $key) {
				if (self::$cache[$key] === '') {
					continue;
				}

				$alt = 'alt="' . htmlspecialchars(self::$cache[$key], ENT_QUOTES, 'UTF-8') . '"';

				if (preg_match('/\balt\s*=\s*("[^"]*"|\'[^\']*\')/i', $tag)) {
					$replace[$tag] = preg_replace('/\balt\s*=\s*("[^"]*"|\'[^\']*\')/i', $alt, $tag, 1);
				} else {
					$replace[$tag] = preg_replace('/^<img\b/i', '<img ' . $alt, $tag, 1);
				}
			}

			if ($replace) {
				$output = strtr($output, $replace);
			}
		} catch (\Throwable $e) {
			$this->log->write('AI Image Alt Text: ' . $e->getMessage());
		}
	}

	/**
	 * Load Alts
	 *
	 * @param array<int, string> $keys
	 *
	 * @return void
	 */
	private function loadAlts(array $keys): void {
		$missing = array_values(array_filter($keys, fn ($key) => !isset(self::$cache[$key])));

		if (!$missing) {
			return;
		}

		foreach ($missing as $key) {
			self::$cache[$key] = '';
		}

		$escaped = array_map(fn ($key) => "'" . $this->db->escape($key) . "'", $missing);

		$query = $this->db->query("SELECT `image_key`, `alt` FROM `" . DB_PREFIX . "image_alt` WHERE `image_key` IN (" . implode(',', $escaped) . ") AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		foreach ($query->rows as $row) {
			self::$cache[$row['image_key']] = (string)$row['alt'];
		}
	}

	/**
	 * Get Key
	 *
	 * @param string $tag <img> tag
	 *
	 * @return string image path without extension, or '' if the image isn't from the image folder
	 */
	private function getKey(string $tag): string {
		if (!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $match)) {
			return '';
		}

		$src = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');

		$path = (string)parse_url($src, PHP_URL_PATH);

		if (preg_match('#/image/cache/(.+)-\d+x\d+\.[a-z0-9]+$#i', $path, $parts)) {
			return rawurldecode($parts[1]);
		}

		if (preg_match('#/image/(catalog/.+)\.[a-z0-9]+$#i', $path, $parts)) {
			return rawurldecode($parts[1]);
		}

		return '';
	}
}
