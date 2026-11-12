<?php
namespace Opencart\Catalog\Model\Extension\Socialshare\Module;

class Socialshare extends \Opencart\System\Engine\Model {
	/**
	 * What is being shared on the current page: URL, title, description, image and type.
	 * Used by the share buttons (layout module) and by the Open Graph tags (header event).
	 *
	 * All values are plain text (HTML entities decoded); escape them when you output them.
	 */
	public function getPage(): array {
		$route = (string)($this->request->get['route'] ?? 'common/home');

		$title = $this->document->getTitle();
		$description = $this->document->getDescription();
		$image = '';
		$type = 'website';

		if ($route == 'product/product' && !empty($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct((int)$this->request->get['product_id']);

			if ($product_info) {
				$title = $product_info['name'];
				$image = (string)$product_info['image'];
				$type = 'product';

				// No meta description: use the start of the product description.
				if (!trim($description)) {
					$description = $this->excerpt((string)$product_info['description']);
				}
			}
		} elseif ($route == 'product/category' && !empty($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);

			$this->load->model('catalog/category');

			$category_info = $this->model_catalog_category->getCategory((int)end($parts));

			if ($category_info) {
				$title = $category_info['name'];
				$image = (string)$category_info['image'];

				if (!trim($description)) {
					$description = $this->excerpt((string)$category_info['description']);
				}
			}
		}

		// No product or category image: fall back to the store logo.
		if (!$image || !is_file(DIR_IMAGE . html_entity_decode($image, ENT_QUOTES, 'UTF-8'))) {
			$image = (string)$this->config->get('config_logo');
		}

		$image_url = '';

		if ($image && is_file(DIR_IMAGE . html_entity_decode($image, ENT_QUOTES, 'UTF-8'))) {
			$path = implode('/', array_map('rawurlencode', explode('/', html_entity_decode($image, ENT_QUOTES, 'UTF-8'))));

			$image_url = $this->config->get('config_url') . 'image/' . $path;
		}

		return [
			'url'         => $this->getUrl(),
			'title'       => $this->clean($title),
			'description' => $this->clean($description),
			'image'       => $image_url,
			'type'        => $type,
			'site_name'   => $this->clean((string)$this->config->get('config_name'))
		];
	}

	/**
	 * The canonical URL if the page set one (product, category), otherwise the current URL.
	 */
	private function getUrl(): string {
		foreach ($this->document->getLinks() as $link) {
			if ($link['rel'] == 'canonical') {
				return html_entity_decode($link['href'], ENT_QUOTES, 'UTF-8');
			}
		}

		// Build from the store URL's scheme and host, not from the Host header.
		$base = parse_url((string)$this->config->get('config_url'));

		$scheme = $base['scheme'] ?? 'https';
		$host = ($base['host'] ?? '') . (isset($base['port']) ? ':' . $base['port'] : '');

		// OpenCart's Request class HTML-encodes $_SERVER values, so decode REQUEST_URI first.
		$uri = html_entity_decode((string)($this->request->server['REQUEST_URI'] ?? '/'), ENT_QUOTES, 'UTF-8');

		return $scheme . '://' . $host . $uri;
	}

	private function excerpt(string $html, int $length = 200): string {
		$text = $this->clean($html);

		if (mb_strlen($text) > $length) {
			$text = rtrim(mb_substr($text, 0, $length - 1)) . '…';
		}

		return $text;
	}

	private function clean(string $text): string {
		$text = trim(strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8')));

		return preg_replace('/\s+/', ' ', $text);
	}
}
