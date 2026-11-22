<?php
namespace Opencart\Catalog\Controller\Extension\Seotoolkit\Module;
/**
 * SEO Toolkit (storefront): meta title and description templates, robots noindex for thin pages,
 * Organization, WebSite and BreadcrumbList structured data.
 */
class Seotoolkit extends \Opencart\System\Engine\Controller {
	public function index(): string {
		return '';
	}

	// ---------------------------------------------------------------- meta templates

	/**
	 * catalog/controller/common/header/before: the page controller has set the title and description by now,
	 * and the header hasn't printed them yet.
	 */
	public function meta(string &$route, array &$args): void {
		if (!$this->config->get('module_seotoolkit_status')) {
			return;
		}

		$page = (string)($this->request->get['route'] ?? '');
		$always = $this->config->get('module_seotoolkit_meta_mode') == 'always';
		$vars = [];
		$type = '';

		if ($page == 'product/product' && !empty($this->request->get['product_id'])) {
			$type = 'product';
			$vars = $this->productVars((int)$this->request->get['product_id']);
		} elseif ($page == 'product/category' && !empty($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);

			$type = 'category';
			$vars = $this->categoryVars((int)end($parts));
		} elseif ($page == 'information/information' && !empty($this->request->get['information_id'])) {
			$type = 'information';
			$vars = $this->informationVars((int)$this->request->get['information_id']);
		} elseif (($page == 'product/manufacturer.info' || $page == 'product/manufacturer|info') && !empty($this->request->get['manufacturer_id'])) {
			$type = 'manufacturer';
			$vars = $this->manufacturerVars((int)$this->request->get['manufacturer_id']);
		}

		if (!$vars) {
			return;
		}

		$title_template = (string)$this->config->get('module_seotoolkit_' . $type . '_title');
		$description_template = (string)$this->config->get('module_seotoolkit_' . $type . '_description');

		// A brand page's title is always its name, so "only when empty" means: when it's just the name.
		$title = $this->document->getTitle();
		$title_empty = trim($title) === '' || ($type == 'manufacturer' && $title == $vars['name']);

		if ($title_template && ($always || $title_empty)) {
			// OpenCart prints these unescaped and stores text HTML-encoded, so encode like the database would.
			$this->document->setTitle(htmlspecialchars($this->render($title_template, $vars, 70), ENT_QUOTES, 'UTF-8'));
		}

		if ($description_template && ($always || trim($this->document->getDescription()) === '')) {
			$this->document->setDescription(htmlspecialchars($this->render($description_template, $vars, 155), ENT_QUOTES, 'UTF-8'));
		}
	}

	private function productVars(int $product_id): array {
		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct($product_id);

		if (!$product) {
			return [];
		}

		$price = '';

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$amount = (float)(!empty($product['special']) ? $product['special'] : $product['price']);

			$price = $this->currency->format($this->tax->calculate($amount, $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
		}

		$category = $this->db->query("SELECT cd.`name` FROM `" . DB_PREFIX . "product_to_category` p2c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.`category_id` = p2c.`category_id` AND cd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "category` c ON (c.`category_id` = p2c.`category_id`) WHERE p2c.`product_id` = '" . $product_id . "' AND c.`status` = '1' ORDER BY c.`parent_id` DESC, c.`sort_order` ASC LIMIT 1")->row['name'] ?? '';

		// OpenCart 4.1's getProduct() has the brand ID but not its name.
		$brand = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = '" . (int)$product['manufacturer_id'] . "'")->row['name'] ?? '';

		return [
			'name'        => $product['name'],
			'brand'       => (string)$brand,
			'model'       => (string)$product['model'],
			'price'       => $price,
			'category'    => (string)$category,
			'store'       => (string)$this->config->get('config_name'),
			'description' => (string)$product['description']
		];
	}

	private function categoryVars(int $category_id): array {
		$this->load->model('catalog/category');

		$category = $this->model_catalog_category->getCategory($category_id);

		if (!$category) {
			return [];
		}

		return [
			'name'        => $category['name'],
			'store'       => (string)$this->config->get('config_name'),
			'description' => (string)$category['description']
		];
	}

	private function informationVars(int $information_id): array {
		$this->load->model('catalog/information');

		$information = $this->model_catalog_information->getInformation($information_id);

		if (!$information) {
			return [];
		}

		return [
			'name'        => $information['title'],
			'store'       => (string)$this->config->get('config_name'),
			'description' => (string)$information['description']
		];
	}

	private function manufacturerVars(int $manufacturer_id): array {
		$this->load->model('catalog/manufacturer');

		$manufacturer = $this->model_catalog_manufacturer->getManufacturer($manufacturer_id);

		if (!$manufacturer) {
			return [];
		}

		return [
			'name'        => $manufacturer['name'],
			'store'       => (string)$this->config->get('config_name'),
			'description' => ''
		];
	}

	/**
	 * Fill {placeholders}; {description} gets whatever room is left up to $max characters.
	 * Separators next to an empty value ("Name |  | Store") are tidied away.
	 */
	private function render(string $template, array $vars, int $max): string {
		$replace = [];

		foreach ($vars as $key => $value) {
			if ($key != 'description') {
				$replace['{' . $key . '}'] = trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8'));
			}
		}

		// [optional text]: dropped when a placeholder inside it is empty, as in "{name}[ by {brand}]".
		$template = preg_replace_callback('/\[([^\[\]]*)\]/u', function(array $match) use ($replace, $vars): string {
			preg_match_all('/\{([a-z]+)\}/', $match[1], $names);

			foreach ($names[1] as $name) {
				$value = $name == 'description' ? trim(strip_tags(html_entity_decode((string)($vars['description'] ?? ''), ENT_QUOTES, 'UTF-8'))) : ($replace['{' . $name . '}'] ?? '');

				if ($value === '') {
					return '';
				}
			}

			return $match[1];
		}, $template) ?? $template;

		$text = $this->tidy(strtr($template, $replace + ['{description}' => '']));

		if (str_contains($template, '{description}')) {
			$room = $max - oc_strlen($text) - 1;
			$description = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['<br', '</p>', '</li>'], [' <br', '</p> ', '</li> '], html_entity_decode((string)($vars['description'] ?? ''), ENT_QUOTES, 'UTF-8'))), ENT_QUOTES, 'UTF-8')));

			if ($room > 20 && $description !== '') {
				if (oc_strlen($description) > $room) {
					$cut = oc_substr($description, 0, $room - 1);
					$space = oc_strrpos($cut, ' ');

					$description = rtrim(oc_substr($cut, 0, $space !== false && $space > $room / 2 ? $space : $room - 1), " ,.;:-") . '…';
				}

				$replace['{description}'] = $description;

				$text = $this->tidy(strtr($template, $replace));
			}
		}

		return $text;
	}

	private function tidy(string $text): string {
		$text = trim(preg_replace('/\s+/u', ' ', $text));

		do {
			$before = $text;

			$text = preg_replace(['/^[\s|\-–—:·,.]+/u', '/[\s|\-–—:·,]+$/u', '/\s+([|–—·])(\s+[|\-–—·:,])+\s+/u', '/\s+([.,])/u', '/\.{2,}/u'], ['', '', ' $1 ', '$1', '.'], $text) ?? $text;
		} while ($text !== $before);

		return $text;
	}

	// ---------------------------------------------------------------- head: robots and site schema

	/**
	 * catalog/view/common/header/after: robots meta for pages that shouldn't be indexed, and Organization and
	 * WebSite structured data on the home page.
	 */
	public function head(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_seotoolkit_status') || ($pos = stripos($output, '</head>')) === false) {
			return;
		}

		$add = '';

		if ($robots = $this->robots()) {
			$add .= '<meta name="robots" content="' . $robots . '">' . "\n";
		}

		$page = (string)($this->request->get['route'] ?? 'common/home');

		if ($page == 'common/home' || $page == '') {
			$schema = (array)$this->config->get('module_seotoolkit_schema');

			if (in_array('organization', $schema)) {
				$add .= $this->jsonld($this->organization());
			}

			if (in_array('website', $schema)) {
				$add .= $this->jsonld($this->website());
			}
		}

		if ($add) {
			$output = substr_replace($output, $add, $pos, 0);
		}
	}

	private function robots(): string {
		$page = (string)($this->request->get['route'] ?? 'common/home');
		$rules = (array)$this->config->get('module_seotoolkit_noindex');
		$get = $this->request->get;

		$listing = in_array($page, ['product/category', 'product/manufacturer.info', 'product/manufacturer|info', 'product/special', 'product/search']);

		if (in_array('search', $rules) && $page == 'product/search') {
			return 'noindex, follow';
		}

		if (in_array('params', $rules) && $listing && array_intersect(['sort', 'order', 'limit', 'filter'], array_keys($get))) {
			return 'noindex, follow';
		}

		if (in_array('page', $rules) && $listing && (int)($get['page'] ?? 1) > 1) {
			return 'noindex, follow';
		}

		if (in_array('private', $rules) && preg_match('#^(account|checkout|affiliate)/|^product/compare$|^tool/#', $page)) {
			return 'noindex, nofollow';
		}

		return '';
	}

	private function organization(): array {
		$url = $this->config->get('config_url');

		$organization = [
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'@id'      => $url . '#organization',
			'name'     => html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8'),
			'url'      => $url
		];

		if ($this->config->get('config_logo') && is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$organization['logo'] = $url . 'image/' . $this->config->get('config_logo');
		}

		if ($this->config->get('config_telephone')) {
			$organization['contactPoint'] = [
				'@type'       => 'ContactPoint',
				'telephone'   => (string)$this->config->get('config_telephone'),
				'contactType' => 'customer service'
			];
		}

		if ($this->config->get('config_address')) {
			$organization['address'] = trim(preg_replace('/\s+/', ' ', html_entity_decode((string)$this->config->get('config_address'), ENT_QUOTES, 'UTF-8')));
		}

		$same_as = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$this->config->get('module_seotoolkit_same_as'))), fn($link) => (bool)filter_var($link, FILTER_VALIDATE_URL)));

		if ($same_as) {
			$organization['sameAs'] = $same_as;
		}

		return $organization;
	}

	private function website(): array {
		$url = $this->config->get('config_url');

		// Built by the storefront so it matches its own search links (SEO URLs or not).
		$search = html_entity_decode($this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&search=SEOTOOLKITTERM'), ENT_QUOTES, 'UTF-8');

		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'@id'             => $url . '#website',
			'name'            => html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8'),
			'url'             => $url,
			'publisher'       => ['@id' => $url . '#organization'],
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => str_replace('SEOTOOLKITTERM', '{search_term_string}', $search)],
				'query-input' => 'required name=search_term_string'
			]
		];
	}

	// ---------------------------------------------------------------- breadcrumbs

	/**
	 * catalog/view/product/product/after (and category, information, brand, search, special):
	 * BreadcrumbList from the breadcrumbs the page already shows.
	 */
	public function breadcrumbs(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_seotoolkit_status') || !in_array('breadcrumb', (array)$this->config->get('module_seotoolkit_schema')) || empty($args['breadcrumbs'])) {
			return;
		}

		$items = [];

		foreach (array_values($args['breadcrumbs']) as $i => $breadcrumb) {
			$name = trim(html_entity_decode(strip_tags((string)($breadcrumb['text'] ?? '')), ENT_QUOTES, 'UTF-8'));

			// The first crumb is a home icon without text.
			if ($name === '' && $i == 0) {
				$name = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
			}

			if ($name === '' || empty($breadcrumb['href'])) {
				continue;
			}

			$items[] = [
				'@type'    => 'ListItem',
				'position' => count($items) + 1,
				'name'     => $name,
				'item'     => html_entity_decode((string)$breadcrumb['href'], ENT_QUOTES, 'UTF-8')
			];
		}

		if (count($items) < 2) {
			return;
		}

		$script = $this->jsonld(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items]);

		$pos = strripos($output, '</body>');

		$output = $pos === false ? $output . $script : substr_replace($output, $script, $pos, 0);
	}

	private function jsonld(array $data): string {
		return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>' . "\n";
	}
}
