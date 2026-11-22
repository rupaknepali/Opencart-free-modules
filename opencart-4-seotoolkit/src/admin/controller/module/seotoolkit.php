<?php
namespace Opencart\Admin\Controller\Extension\Seotoolkit\Module;
/**
 * SEO Toolkit: an SEO audit of the catalog, bulk SEO keywords and meta tags, meta templates, robots noindex
 * rules, Organization / WebSite / BreadcrumbList structured data, a robots.txt editor, and SEO keywords
 * typed for you in the product, category, information and brand forms.
 */
class Seotoolkit extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/seotoolkit/module/seotoolkit';

	private array $defaults = [
		'module_seotoolkit_status'                  => 0,
		'module_seotoolkit_meta_mode'               => 'empty',
		'module_seotoolkit_product_title'           => '{name} | {store}',
		'module_seotoolkit_product_description'     => 'Buy {name}[ for {price}] at {store}. {description}',
		'module_seotoolkit_category_title'          => '{name} | {store}',
		'module_seotoolkit_category_description'    => 'Shop {name} at {store}. {description}',
		'module_seotoolkit_information_title'       => '{name} | {store}',
		'module_seotoolkit_information_description' => '{description}',
		'module_seotoolkit_manufacturer_title'      => '{name} | {store}',
		'module_seotoolkit_manufacturer_description'=> 'Shop all {name} products at {store}: compare prices and order online.',
		'module_seotoolkit_noindex'                 => ['search', 'params', 'private'],
		'module_seotoolkit_schema'                  => ['organization', 'website', 'breadcrumb'],
		'module_seotoolkit_same_as'                 => '',
		'module_seotoolkit_auto_keyword'            => 1,
		'module_seotoolkit_transliterate'           => 1
	];

	/**
	 * How each catalog type is stored. Brands have no description table and no meta tags in OpenCart 4.
	 */
	private array $types = [
		'product'      => ['key' => 'product_id', 'name' => 'name', 'description' => true, 'status' => true, 'form' => 'catalog/product.form'],
		'category'     => ['key' => 'path', 'name' => 'name', 'description' => true, 'status' => true, 'form' => 'catalog/category.form'],
		'information'  => ['key' => 'information_id', 'name' => 'title', 'description' => true, 'status' => true, 'form' => 'catalog/information.form'],
		'manufacturer' => ['key' => 'manufacturer_id', 'name' => 'name', 'description' => false, 'status' => false, 'form' => 'catalog/manufacturer.form']
	];

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['audit'] = $this->url->link($this->route . '.audit', $token);
		$data['generate'] = $this->url->link($this->route . '.generate', $token);
		$data['fill'] = $this->url->link($this->route . '.fill', $token);
		$data['robots_save'] = $this->url->link($this->route . '.robots', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_seotoolkit');

		foreach ($this->defaults as $key => $value) {
			$data[$key] = array_key_exists($key, $saved) ? $saved[$key] : $value;
		}

		$data['types'] = array_keys($this->types);

		// robots.txt
		$file = DIR_OPENCART . 'robots.txt';

		$data['robots'] = is_file($file) ? (string)file_get_contents($file) : '';
		$data['robots_exists'] = is_file($file);
		$data['robots_writable'] = is_file($file) ? is_writable($file) : is_writable(DIR_OPENCART);
		$data['robots_url'] = HTTP_CATALOG . 'robots.txt';
		$data['robots_recommended'] = $this->recommendedRobots();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!$json) {
			$settings = [
				'module_seotoolkit_status'        => (int)!empty($post['module_seotoolkit_status']),
				'module_seotoolkit_meta_mode'     => ($post['module_seotoolkit_meta_mode'] ?? '') == 'always' ? 'always' : 'empty',
				'module_seotoolkit_noindex'       => array_values(array_intersect((array)($post['module_seotoolkit_noindex'] ?? []), ['search', 'params', 'page', 'private'])),
				'module_seotoolkit_schema'        => array_values(array_intersect((array)($post['module_seotoolkit_schema'] ?? []), ['organization', 'website', 'breadcrumb'])),
				'module_seotoolkit_same_as'       => trim(html_entity_decode((string)($post['module_seotoolkit_same_as'] ?? ''), ENT_QUOTES, 'UTF-8')),
				'module_seotoolkit_auto_keyword'  => (int)!empty($post['module_seotoolkit_auto_keyword']),
				'module_seotoolkit_transliterate' => (int)!empty($post['module_seotoolkit_transliterate'])
			];

			foreach (array_keys($this->types) as $type) {
				foreach (['title', 'description'] as $field) {
					$settings['module_seotoolkit_' . $type . '_' . $field] = trim(html_entity_decode((string)($post['module_seotoolkit_' . $type . '_' . $field] ?? ''), ENT_QUOTES, 'UTF-8'));
				}
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_seotoolkit', $settings);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function robots(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		$content = str_replace("\r\n", "\n", html_entity_decode((string)($this->request->post['robots'] ?? ''), ENT_QUOTES, 'UTF-8'));

		if (!$json && strlen($content) > 512000) {
			$json['error'] = $this->language->get('error_robots_size');
		}

		if (!$json) {
			if (@file_put_contents(DIR_OPENCART . 'robots.txt', rtrim($content) . "\n") === false) {
				$json['error'] = $this->language->get('error_robots_write');
			} else {
				$json['success'] = $this->language->get('text_robots_saved');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function recommendedRobots(): string {
		return "User-agent: *\n"
			. "Disallow: /*route=account/\n"
			. "Disallow: /*route=checkout/\n"
			. "Disallow: /*route=affiliate/\n"
			. "Disallow: /*route=product/compare\n"
			. "Disallow: /*route=product/search\n"
			. "Disallow: /*route=tool/\n"
			. "Disallow: /system/\n"
			. "Disallow: /extension/\n"
			. "\n"
			. "# Your sitemap (change it to the address your sitemap extension gives you):\n"
			. "# Sitemap: " . HTTP_CATALOG . "sitemap.xml\n";
	}

	// ---------------------------------------------------------------- audit

	/**
	 * The SEO audit: missing SEO keywords, empty, long and duplicate meta tags, and thin descriptions.
	 */
	public function audit(): void {
		$this->load->language($this->route);

		$language_id = (int)$this->config->get('config_language_id');
		$token = 'user_token=' . $this->session->data['user_token'];

		$data['sections'] = [];
		$data['total_issues'] = 0;

		foreach ($this->types as $type => $info) {
			$issues = [];

			$total = (int)$this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . $type . "`" . ($info['status'] ? " WHERE `status` = '1'" : ""))->row['total'];

			// Missing SEO keywords, per store and language the item is in.
			$missing = $this->missingKeywords($type);

			$issues[] = $this->issue('keyword', count($missing), array_slice(array_values(array_reduce($missing, function(array $carry, array $row): array {
				$carry[$row['id']] = ['id' => $row['id'], 'name' => $row['name']];

				return $carry;
			}, [])), 0, 10), $type, $info, $token, 'generate');

			if ($info['description']) {
				$description = DB_PREFIX . $type . '_description';
				$status = " AND t.`status` = '1'";

				$checks = [
					'meta_title'       => "TRIM(d.`meta_title`) = ''",
					'meta_description' => "TRIM(d.`meta_description`) = ''",
					'long_title'       => "CHAR_LENGTH(d.`meta_title`) > 60",
					'long_description' => "CHAR_LENGTH(d.`meta_description`) > 160"
				];

				foreach ($checks as $code => $where) {
					$query = $this->db->query("SELECT d.`" . $type . "_id` AS id, d.`" . $info['name'] . "` AS name FROM `" . $description . "` d LEFT JOIN `" . DB_PREFIX . $type . "` t ON (t.`" . $type . "_id` = d.`" . $type . "_id`) WHERE d.`language_id` = '" . $language_id . "' AND " . $where . $status . " ORDER BY d.`" . $info['name'] . "`");

					$issues[] = $this->issue($code, $query->num_rows, array_slice($query->rows, 0, 10), $type, $info, $token, in_array($code, ['meta_title', 'meta_description']) ? 'fill' : '');
				}

				// Duplicate meta titles: Google may treat the pages as the same.
				$query = $this->db->query("SELECT d.`" . $type . "_id` AS id, d.`" . $info['name'] . "` AS name FROM `" . $description . "` d LEFT JOIN `" . DB_PREFIX . $type . "` t ON (t.`" . $type . "_id` = d.`" . $type . "_id`) WHERE d.`language_id` = '" . $language_id . "' AND TRIM(d.`meta_title`) != ''" . $status . " AND d.`meta_title` IN (SELECT `meta_title` FROM (SELECT d2.`meta_title` FROM `" . $description . "` d2 LEFT JOIN `" . DB_PREFIX . $type . "` t2 ON (t2.`" . $type . "_id` = d2.`" . $type . "_id`) WHERE d2.`language_id` = '" . $language_id . "' AND t2.`status` = '1' GROUP BY d2.`meta_title` HAVING COUNT(*) > 1) AS dup) ORDER BY d.`meta_title`");

				$issues[] = $this->issue('duplicate_title', $query->num_rows, array_slice($query->rows, 0, 10), $type, $info, $token);

				// Thin content: product and category descriptions under ~50 words.
				if ($type != 'information') {
					$query = $this->db->query("SELECT d.`" . $type . "_id` AS id, d.`" . $info['name'] . "` AS name, d.`description` FROM `" . $description . "` d LEFT JOIN `" . DB_PREFIX . $type . "` t ON (t.`" . $type . "_id` = d.`" . $type . "_id`) WHERE d.`language_id` = '" . $language_id . "'" . $status . " ORDER BY d.`" . $info['name'] . "`");

					$thin = array_values(array_filter($query->rows, fn($row) => str_word_count(strip_tags(html_entity_decode((string)$row['description'], ENT_QUOTES, 'UTF-8'))) < 50));

					$issues[] = $this->issue('thin', count($thin), array_slice($thin, 0, 10), $type, $info, $token);
				}
			}

			// Products without an image show no picture in search results or shopping ads.
			if ($type == 'product') {
				$query = $this->db->query("SELECT p.`product_id` AS id, pd.`name` FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = p.`product_id` AND pd.`language_id` = '" . $language_id . "') WHERE p.`status` = '1' AND (p.`image` = '' OR p.`image` IS NULL) ORDER BY pd.`name`");

				$issues[] = $this->issue('image', $query->num_rows, array_slice($query->rows, 0, 10), $type, $info, $token);
			}

			$count = array_sum(array_column($issues, 'count'));

			$data['total_issues'] += $count;

			$data['sections'][] = [
				'type'   => $type,
				'name'   => $this->language->get('text_type_' . $type),
				'total'  => $total,
				'count'  => $count,
				'issues' => $issues
			];
		}

		$this->response->setOutput($this->load->view('extension/seotoolkit/module/seotoolkit_audit', $data));
	}

	private function issue(string $code, int $count, array $rows, string $type, array $info, string $token, string $fix = ''): array {
		$items = [];

		foreach ($rows as $row) {
			$items[] = [
				'name' => html_entity_decode((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '#' . $row['id'],
				'edit' => $this->url->link($info['form'], $token . '&' . $type . '_id=' . (int)$row['id'])
			];
		}

		return [
			'code'  => $code,
			'text'  => $this->language->get('issue_' . $code),
			'help'  => $this->language->get('help_issue_' . $code),
			'count' => $count,
			'items' => $items,
			'more'  => max(0, $count - count($items)),
			'fix'   => $count ? $fix : ''
		];
	}

	/**
	 * Every (item, store, language) without an SEO keyword, for the stores the item is assigned to.
	 */
	private function missingKeywords(string $type): array {
		$info = $this->types[$type];

		$store_table = DB_PREFIX . $type . '_to_store';

		if ($type == 'manufacturer') {
			$name = "t.`name`";
			$join = "";
		} else {
			$name = "d.`" . $info['name'] . "`";
			$join = " LEFT JOIN `" . DB_PREFIX . $type . "_description` d ON (d.`" . $type . "_id` = t.`" . $type . "_id` AND d.`language_id` = l.`language_id`)";
		}

		if ($type == 'category') {
			$value = "cp.`path`";
			$join .= " LEFT JOIN (SELECT `category_id`, GROUP_CONCAT(`path_id` ORDER BY `level` SEPARATOR '_') AS path, MAX(`level`) AS level FROM `" . DB_PREFIX . "category_path` GROUP BY `category_id`) cp ON (cp.`category_id` = t.`category_id`)";
			$order = " ORDER BY cp.`level` ASC";
			$extra = ", cp.`path`";
		} else {
			$value = "CAST(t.`" . $type . "_id` AS CHAR)";
			$order = "";
			$extra = "";
		}

		$sql = "SELECT t.`" . $type . "_id` AS id, " . $name . " AS name, s.`store_id`, l.`language_id`" . $extra
			. " FROM `" . DB_PREFIX . $type . "` t"
			. " INNER JOIN `" . $store_table . "` s ON (s.`" . $type . "_id` = t.`" . $type . "_id`)"
			. " INNER JOIN `" . DB_PREFIX . "language` l ON (l.`status` = '1')"
			. $join
			. " LEFT JOIN `" . DB_PREFIX . "seo_url` su ON (su.`key` = '" . $info['key'] . "' AND su.`value` = " . $value . " AND su.`store_id` = s.`store_id` AND su.`language_id` = l.`language_id` AND su.`keyword` != '')"
			. " WHERE su.`seo_url_id` IS NULL" . ($info['status'] ? " AND t.`status` = '1'" : "")
			. $order;

		return $this->db->query($sql)->rows;
	}

	// ---------------------------------------------------------------- bulk fixes

	/**
	 * Create SEO keywords for everything that has none, in every store and language it's in.
	 */
	public function generate(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$created = 0;

			$type = (string)($this->request->get['type'] ?? '');
			$types = isset($this->types[$type]) ? [$type] : array_keys($this->types);

			foreach ($types as $type) {
				$info = $this->types[$type];

				foreach ($this->missingKeywords($type) as $row) {
					$name = (string)$row['name'];

					// No name in this language: use the default language's.
					if ($name === '' && $type != 'manufacturer') {
						$name = (string)($this->db->query("SELECT `" . $info['name'] . "` AS name FROM `" . DB_PREFIX . $type . "_description` WHERE `" . $type . "_id` = '" . (int)$row['id'] . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'")->row['name'] ?? '');
					}

					$slug = $this->slug($name);

					if ($slug === '') {
						$slug = $type . '-' . (int)$row['id'];
					}

					$value = $type == 'category' ? (string)$row['path'] : (string)$row['id'];
					$prefix = '';

					// Subcategory keywords include their parent's ("laptops/macs"), like OpenCart's own form saves them.
					if ($type == 'category' && str_contains($value, '_')) {
						$parent = substr($value, 0, strrpos($value, '_'));

						$prefix = (string)($this->db->query("SELECT `keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `key` = 'path' AND `value` = '" . $this->db->escape($parent) . "' AND `store_id` = '" . (int)$row['store_id'] . "' AND `language_id` = '" . (int)$row['language_id'] . "'")->row['keyword'] ?? '');
					}

					$keyword = $this->unique($slug, (int)$row['store_id']);

					$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `key` = '" . $info['key'] . "' AND `value` = '" . $this->db->escape($value) . "' AND `store_id` = '" . (int)$row['store_id'] . "' AND `language_id` = '" . (int)$row['language_id'] . "'");

					$this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET `store_id` = '" . (int)$row['store_id'] . "', `language_id` = '" . (int)$row['language_id'] . "', `key` = '" . $info['key'] . "', `value` = '" . $this->db->escape($value) . "', `keyword` = '" . $this->db->escape(($prefix !== '' ? $prefix . '/' : '') . $keyword) . "', `sort_order` = '0'");

					$created++;
				}
			}

			$json['success'] = sprintf($this->language->get('text_generated'), $created);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * A keyword nobody else in this store uses. Same rule as OpenCart's forms: "macs" also clashes with "laptops/macs".
	 */
	private function unique(string $slug, int $store_id): string {
		$keyword = $slug;
		$i = 2;

		while ($this->db->query("SELECT `seo_url_id` FROM `" . DB_PREFIX . "seo_url` WHERE (`keyword` = '" . $this->db->escape($keyword) . "' OR LCASE(`keyword`) LIKE '" . $this->db->escape('%/' . oc_strtolower($keyword)) . "') AND `store_id` = '" . $store_id . "' LIMIT 1")->num_rows) {
			$keyword = oc_substr($slug, 0, 60) . '-' . $i++;
		}

		return $keyword;
	}

	/**
	 * Write meta titles and descriptions from the templates into every empty field, so they also show in
	 * the admin and in feeds that read them.
	 */
	public function fill(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$filled = 0;

			$this->load->model('setting/setting');

			$config = $this->model_setting_setting->getSetting('module_seotoolkit') + $this->defaults;
			$store = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			foreach (['product', 'category', 'information'] as $type) {
				$info = $this->types[$type];

				$query = $this->db->query("SELECT d.* FROM `" . DB_PREFIX . $type . "_description` d WHERE TRIM(d.`meta_title`) = '' OR TRIM(d.`meta_description`) = ''");

				foreach ($query->rows as $row) {
					$vars = [
						'name'        => (string)$row[$info['name']],
						'store'       => $store,
						'description' => (string)$row['description']
					];

					if ($type == 'product') {
						$vars += $this->productVars((int)$row['product_id'], (int)$row['language_id']);
					}

					$set = [];

					if (trim($row['meta_title']) === '' && $config['module_seotoolkit_' . $type . '_title']) {
						$set[] = "`meta_title` = '" . $this->db->escape(htmlspecialchars($this->render($config['module_seotoolkit_' . $type . '_title'], $vars, 70), ENT_QUOTES, 'UTF-8')) . "'";
					}

					if (trim($row['meta_description']) === '' && $config['module_seotoolkit_' . $type . '_description']) {
						$set[] = "`meta_description` = '" . $this->db->escape(htmlspecialchars($this->render($config['module_seotoolkit_' . $type . '_description'], $vars, 155), ENT_QUOTES, 'UTF-8')) . "'";
					}

					if ($set) {
						$this->db->query("UPDATE `" . DB_PREFIX . $type . "_description` SET " . implode(', ', $set) . " WHERE `" . $type . "_id` = '" . (int)$row[$type . '_id'] . "' AND `language_id` = '" . (int)$row['language_id'] . "'");

						$filled++;
					}
				}
			}

			$json['success'] = sprintf($this->language->get('text_filled'), $filled);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function productVars(int $product_id, int $language_id): array {
		$product = $this->db->query("SELECT p.`model`, p.`price`, m.`name` AS brand FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (m.`manufacturer_id` = p.`manufacturer_id`) WHERE p.`product_id` = '" . $product_id . "'")->row;

		$category = $this->db->query("SELECT cd.`name` FROM `" . DB_PREFIX . "product_to_category` p2c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.`category_id` = p2c.`category_id` AND cd.`language_id` = '" . $language_id . "') LEFT JOIN `" . DB_PREFIX . "category` c ON (c.`category_id` = p2c.`category_id`) WHERE p2c.`product_id` = '" . $product_id . "' AND c.`status` = '1' ORDER BY c.`parent_id` DESC, c.`sort_order` ASC LIMIT 1")->row['name'] ?? '';

		return [
			'brand'    => (string)($product['brand'] ?? ''),
			'model'    => (string)($product['model'] ?? ''),
			// Stored text can't follow price changes, so a written description leaves the price out.
			'price'    => '',
			'category' => (string)$category
		];
	}

	// Same rendering as the storefront (catalog/controller/module/seotoolkit.php).
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

	// ---------------------------------------------------------------- slugs

	/**
	 * Characters swapped for plain letters when transliteration is on.
	 */
	private function map(): array {
		$map = [
			'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae',
			'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ė' => 'e',
			'ğ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'į' => 'i', 'ı' => 'i',
			'ĵ' => 'j', 'ķ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n',
			'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'œ' => 'oe',
			'ŕ' => 'r', 'ř' => 'r', 'ś' => 's', 'š' => 's', 'ş' => 's', 'ș' => 's', 'ß' => 'ss', 'ť' => 't', 'ţ' => 't', 'ț' => 't', 'þ' => 'th',
			'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ū' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u',
			'ý' => 'y', 'ÿ' => 'y', 'ź' => 'z', 'ž' => 'z', 'ż' => 'z',
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'ґ' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'є' => 'ye', 'ж' => 'zh', 'з' => 'z',
			'и' => 'i', 'і' => 'i', 'ї' => 'yi', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
			'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y',
			'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
			'α' => 'a', 'ά' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'έ' => 'e', 'ζ' => 'z', 'η' => 'i', 'ή' => 'i', 'θ' => 'th',
			'ι' => 'i', 'ί' => 'i', 'ϊ' => 'i', 'ΐ' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'ό' => 'o',
			'π' => 'p', 'ρ' => 'r', 'σ' => 's', 'ς' => 's', 'τ' => 't', 'υ' => 'y', 'ύ' => 'y', 'ϋ' => 'y', 'ΰ' => 'y', 'φ' => 'f', 'χ' => 'ch',
			'ψ' => 'ps', 'ω' => 'o', 'ώ' => 'o', '&' => '-and-'
		];

		return $map;
	}

	private function slug(string $text): string {
		$text = oc_strtolower(trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));

		if ($this->config->get('module_seotoolkit_transliterate') || $this->config->get('module_seotoolkit_transliterate') === '') {
			$text = strtr($text, $this->map());
			$text = preg_replace('/[^a-z0-9]+/', '-', $text);
		} else {
			// OpenCart accepts Latin, Cyrillic and Greek letters in keywords.
			$text = preg_replace('/[^\p{Latin}\p{Cyrillic}\p{Greek}0-9]+/u', '-', $text);
		}

		$text = trim((string)$text, '-');

		if (oc_strlen($text) > 64) {
			$text = rtrim(oc_substr($text, 0, 64), '-');
		}

		return $text;
	}

	// ---------------------------------------------------------------- admin form helper

	/**
	 * admin/view/catalog/product_form/after (and category, information, brand): while you type the name, the
	 * empty SEO keyword and meta title fields fill themselves in. Edit a field and it stops following.
	 */
	public function form(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_seotoolkit_status') || !$this->config->get('module_seotoolkit_auto_keyword')) {
			return;
		}

		$type = '';

		foreach (array_keys($this->types) as $name) {
			if (str_contains($route, 'catalog/' . $name . '_form')) {
				$type = $name;
			}
		}

		if (!$type) {
			return;
		}

		$config = [
			'type'          => $type,
			'name'          => $type == 'manufacturer' ? 'name' : $type . '_description[{lang}][' . $this->types[$type]['name'] . ']',
			'meta'          => $type == 'manufacturer' ? '' : $type . '_description[{lang}][meta_title]',
			'seo'           => $type . '_seo_url',
			'transliterate' => (bool)$this->config->get('module_seotoolkit_transliterate'),
			'map'           => $this->map()
		];

		$script = '<script type="text/javascript">' . "\n" . 'window.seotoolkit = ' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ";\n" . <<<'JS'
(function(c) {
    var map = c.map;

    function slug(text) {
        text = String(text).toLowerCase().trim();

        if (c.transliterate) {
            text = text.replace(/[^\u0000-\u007f]|&/g, function(ch) { return map[ch] !== undefined ? map[ch] : ch; });
            text = text.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-');
        } else {
            text = text.replace(/[^\p{Script=Latin}\p{Script=Cyrillic}\p{Script=Greek}0-9]+/gu, '-');
        }

        return text.replace(/^-+|-+$/g, '').substring(0, 64).replace(/-+$/, '');
    }

    function follow(field, value) {
        var $field = $(field);

        // Fill it if it's empty, or still holds what we filled in last time.
        if ($field.val() === '' || $field.val() === $field.data('seotoolkit')) {
            $field.val(value).data('seotoolkit', value);
        }
    }

    $(document).on('input', 'input[name]', function() {
        var name = this.name, lang = null;

        if (c.type === 'manufacturer') {
            if (name !== 'name') return;
        } else {
            var match = name.match(/^[a-z]+_description\[(\d+)\]\[(name|title)\]$/);

            if (!match || name !== c.name.replace('{lang}', match[1])) return;

            lang = match[1];
        }

        var value = this.value;

        $('input[name^="' + c.seo + '["]').each(function() {
            var parts = this.name.match(/\[(\d+)\]\[(\d+)\]$/);

            if (parts && (lang === null || parts[2] === lang)) {
                follow(this, slug(value));
            }
        });

        if (c.meta && lang !== null) {
            follow($('input[name="' + c.meta.replace('{lang}', lang) + '"]'), value);
        }
    });

    // Fields that are already filled on load belong to the user.
    $(function() {
        $('input[name^="' + c.seo + '["]').each(function() { $(this).data('seotoolkit', this.value === '' ? '' : null); });
    });
})(window.seotoolkit);
</script>

JS;

		$pos = strripos($output, '</body>');

		$output = $pos === false ? $output . $script : substr_replace($output, $script, $pos, 0);
	}

	// ---------------------------------------------------------------- install

	private function events(): array {
		return [
			'seotoolkit_meta'        => ['catalog/controller/common/header/before', 'meta'],
			'seotoolkit_head'        => ['catalog/view/common/header/after', 'head'],
			'seotoolkit_crumb'       => ['catalog/view/product/product/after', 'breadcrumbs'],
			'seotoolkit_crumb_cat'   => ['catalog/view/product/category/after', 'breadcrumbs'],
			'seotoolkit_crumb_info'  => ['catalog/view/information/information/after', 'breadcrumbs'],
			'seotoolkit_crumb_brand' => ['catalog/view/product/manufacturer_info/after', 'breadcrumbs'],
			'seotoolkit_crumb_spec'  => ['catalog/view/product/special/after', 'breadcrumbs'],
			'seotoolkit_form_prod'   => ['admin/view/catalog/product_form/after', 'form'],
			'seotoolkit_form_cat'    => ['admin/view/catalog/category_form/after', 'form'],
			'seotoolkit_form_info'   => ['admin/view/catalog/information_form/after', 'form'],
			'seotoolkit_form_brand'  => ['admin/view/catalog/manufacturer_form/after', 'form']
		];
	}

	public function install(): void {
		$this->load->model('setting/event');

		foreach ($this->events() as $code => [$trigger, $method]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'SEO Toolkit',
				'trigger'     => $trigger,
				'action'      => $this->route . '.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_seotoolkit', $this->defaults);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (array_keys($this->events()) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		// SEO keywords, meta tags and robots.txt written by the toolkit are your store's data now: they stay.
	}
}
