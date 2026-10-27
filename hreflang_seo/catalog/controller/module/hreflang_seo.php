<?php
namespace Opencart\Catalog\Controller\Extension\HreflangSeo\Module;
/**
 * Class HreflangSeo
 *
 * Hreflang & Canonical SEO for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/view/common/header/after -> header (adds tags before </head>)
 *
 * @package Opencart\Catalog\Controller\Extension\HreflangSeo\Module
 */
class HreflangSeo extends \Opencart\System\Engine\Controller {
	/**
	 * Pages that get hreflang links, with the query parameters that identify the page.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $pages = [
		'common/home'               => [],
		'product/product'           => ['product_id'],
		'product/category'          => ['path', 'page'],
		'product/manufacturer'      => [],
		'product/manufacturer.info' => ['manufacturer_id', 'page'],
		'product/special'           => ['page'],
		'information/information'   => ['information_id'],
		'information/contact'       => [],
		'cms/blog'                  => ['page'],
		'cms/blog.info'             => ['article_id']
	];

	/**
	 * Routes that should not be indexed. Matched by prefix.
	 *
	 * @var array<int, string>
	 */
	private array $noindex = ['product/search', 'product/compare', 'checkout/', 'account/', 'affiliate/', 'information/sitemap'];

	/**
	 * Header
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function header(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_hreflang_seo_status')) {
			return;
		}

		try {
			$page_route = (string)($this->request->get['route'] ?? 'common/home');

			// error/not_found and other routes keep whatever OpenCart printed.
			$html = '';

			if ($this->config->get('module_hreflang_seo_noindex')) {
				foreach ($this->noindex as $prefix) {
					if (str_starts_with($page_route, $prefix) && !str_contains($output, 'name="robots"')) {
						$html .= '<meta name="robots" content="noindex, follow"/>' . "\n";

						break;
					}
				}
			}

			if (isset($this->pages[$page_route]) && !$this->isFiltered()) {
				$args = $this->getArgs($page_route);

				if ($this->config->get('module_hreflang_seo_canonical') && !str_contains($output, 'rel="canonical"')) {
					$html .= '<link href="' . $this->link($page_route, $args, (string)$this->config->get('config_language')) . '" rel="canonical"/>' . "\n";
				}

				$html .= $this->getAlternates($page_route, $args);
			}

			if ($html !== '') {
				$position = stripos($output, '</head>');

				if ($position !== false) {
					$output = substr_replace($output, $html, $position, 0);
				}
			}
		} catch (\Throwable $e) {
			$this->log->write('Hreflang & Canonical SEO: ' . $e->getMessage());
		}
	}

	/**
	 * Get Alternates
	 *
	 * Builds one link per enabled language plus x-default. Each URL is built while that language is active,
	 * so SEO URL keywords come out in the right language.
	 *
	 * @param string                $route
	 * @param array<string, string> $args
	 *
	 * @return string
	 */
	private function getAlternates(string $route, array $args): string {
		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();

		if (count($languages) < 2) {
			return '';
		}

		$codes = (array)$this->config->get('module_hreflang_seo_code');
		$x_default = (string)$this->config->get('module_hreflang_seo_x_default') ?: (string)$this->config->get('config_language_catalog');

		$html = '';
		$default_href = '';

		foreach ($languages as $language) {
			// A product, category or page can be missing a translation. Don't point Google at an empty page.
			if (!$this->hasTranslation($route, $args, (int)$language['language_id'])) {
				continue;
			}

			$href = $this->link($route, $args, $language['code'], (int)$language['language_id']);

			$hreflang = trim((string)($codes[$language['code']] ?? '')) ?: $this->toHreflang($language['code']);

			$html .= '<link rel="alternate" hreflang="' . htmlspecialchars($hreflang, ENT_QUOTES, 'UTF-8') . '" href="' . $href . '"/>' . "\n";

			if ($language['code'] == $x_default) {
				$default_href = $href;
			}
		}

		if ($default_href) {
			$html .= '<link rel="alternate" hreflang="x-default" href="' . $default_href . '"/>' . "\n";
		}

		return $html;
	}

	/**
	 * Link
	 *
	 * @param string                $route
	 * @param array<string, string> $args
	 * @param string                $code
	 * @param int                   $language_id
	 *
	 * @return string escaped for an HTML attribute
	 */
	private function link(string $route, array $args, string $code, int $language_id = 0): string {
		$current_id = $this->config->get('config_language_id');
		$current_code = $this->config->get('config_language');

		if ($language_id) {
			$this->config->set('config_language_id', $language_id);
			$this->config->set('config_language', $code);
		}

		$url = $this->url->link($route, 'language=' . $code . ($args ? '&' . http_build_query($args) : ''));

		$this->config->set('config_language_id', $current_id);
		$this->config->set('config_language', $current_code);

		// url->link() returns &amp; separated URLs, which is what an HTML attribute needs.
		return $url;
	}

	/**
	 * Get Args
	 *
	 * @param string $route
	 *
	 * @return array<string, string>
	 */
	private function getArgs(string $route): array {
		$args = [];

		foreach ($this->pages[$route] as $key) {
			if (isset($this->request->get[$key]) && $this->request->get[$key] !== '') {
				if ($key == 'page' && (int)$this->request->get[$key] <= 1) {
					continue;
				}

				$args[$key] = $key == 'path' ? preg_replace('/[^0-9_]/', '', (string)$this->request->get[$key]) : (string)(int)$this->request->get[$key];
			}
		}

		return $args;
	}

	/**
	 * Is Filtered
	 *
	 * Sorted, filtered and limited listings are duplicates of the main listing. They keep OpenCart's canonical
	 * (which points to the unfiltered page) and get no hreflang links of their own.
	 *
	 * @return bool
	 */
	private function isFiltered(): bool {
		foreach (['sort', 'order', 'limit', 'filter'] as $key) {
			if (!empty($this->request->get[$key])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Has Translation
	 *
	 * @param string                $route
	 * @param array<string, string> $args
	 * @param int                   $language_id
	 *
	 * @return bool
	 */
	private function hasTranslation(string $route, array $args, int $language_id): bool {
		$table = [
			'product/product'         => ['product_description', 'product_id', 'name'],
			'information/information' => ['information_description', 'information_id', 'title'],
			'cms/blog.info'           => ['article_description', 'article_id', 'name']
		];

		if ($route == 'product/category' && !empty($args['path'])) {
			$parts = explode('_', $args['path']);

			$table[$route] = ['category_description', 'category_id', 'name'];
			$args['category_id'] = (string)(int)end($parts);
		}

		if (!isset($table[$route])) {
			return true;
		}

		[$name, $key, $field] = $table[$route];

		$id = (int)($args[$key] ?? 0);

		if (!$id) {
			return true;
		}

		$query = $this->db->query("SELECT `" . $field . "` FROM `" . DB_PREFIX . $name . "` WHERE `" . $key . "` = '" . $id . "' AND `language_id` = '" . (int)$language_id . "'");

		return $query->num_rows && trim((string)$query->row[$field]) !== '';
	}

	/**
	 * To Hreflang
	 *
	 * en-gb -> en-GB, fr-fr -> fr-FR, de -> de
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	private function toHreflang(string $code): string {
		$parts = explode('-', strtolower($code), 2);

		return isset($parts[1]) ? $parts[0] . '-' . strtoupper($parts[1]) : $parts[0];
	}
}
