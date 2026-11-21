<?php
namespace Opencart\Admin\Controller\Extension\Indexnow\Module;
/**
 * IndexNow: tell Bing, Yandex, Seznam, Naver and Yep about new and changed products, categories,
 * information pages and brands the moment you save them.
 */
class Indexnow extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/indexnow/module/indexnow';

	private array $endpoints = [
		'https://api.indexnow.org/indexnow'           => 'IndexNow.org (shared with all participating search engines)',
		'https://www.bing.com/indexnow'               => 'Microsoft Bing',
		'https://yandex.com/indexnow'                 => 'Yandex',
		'https://search.seznam.cz/indexnow'           => 'Seznam.cz',
		'https://searchadvisor.naver.com/indexnow'    => 'Naver',
		'https://indexnow.yep.com/indexnow'           => 'Yep'
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
		$data['submit_all'] = $this->url->link($this->route . '.submitAll', $token);
		$data['regenerate'] = $this->url->link($this->route . '.regenerate', $token);
		$data['log'] = $this->url->link($this->route . '.log', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_indexnow');

		$data['module_indexnow_status'] = $saved['module_indexnow_status'] ?? 0;
		$data['module_indexnow_endpoint'] = $saved['module_indexnow_endpoint'] ?? 'https://api.indexnow.org/indexnow';
		$data['module_indexnow_types'] = $saved['module_indexnow_types'] ?? ['product', 'category', 'information', 'manufacturer'];
		$data['key'] = $this->getKey();

		$data['endpoints'] = $this->endpoints;

		// A custom endpoint saved in the database (for example a test server) stays selectable.
		if (!isset($data['endpoints'][$data['module_indexnow_endpoint']])) {
			$data['endpoints'][$data['module_indexnow_endpoint']] = $data['module_indexnow_endpoint'];
		}

		$location = $this->keyLocation();

		$data['key_url'] = $location['url'];
		$data['key_root'] = $location['root'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$saved = $this->model_setting_setting->getSetting('module_indexnow');

			$endpoint = (string)($this->request->post['module_indexnow_endpoint'] ?? '');

			if (!isset($this->endpoints[$endpoint]) && $endpoint !== ($saved['module_indexnow_endpoint'] ?? '')) {
				$endpoint = 'https://api.indexnow.org/indexnow';
			}

			$this->model_setting_setting->editSetting('module_indexnow', [
				'module_indexnow_status'   => (int)!empty($this->request->post['module_indexnow_status']),
				'module_indexnow_endpoint' => $endpoint,
				'module_indexnow_types'    => array_values(array_intersect((array)($this->request->post['module_indexnow_types'] ?? []), ['product', 'category', 'information', 'manufacturer'])),
				'module_indexnow_key'      => $this->getKey()
			]);

			$this->writeKeyFile($this->getKey());

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * A new key, for example if the old one was published somewhere it shouldn't be.
	 */
	public function regenerate(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$old = $this->getKey();
			$key = bin2hex(random_bytes(16));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editValue('module_indexnow', 'module_indexnow_key', $key);
			$this->config->set('module_indexnow_key', $key);

			if (is_file(DIR_OPENCART . $old . '.txt')) {
				@unlink(DIR_OPENCART . $old . '.txt');
			}

			$this->writeKeyFile($key);

			$location = $this->keyLocation();

			$json['key'] = $key;
			$json['key_url'] = $location['url'];
			$json['success'] = $this->language->get('text_regenerated');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Submit every enabled product, category, information page and brand, in batches of up to 10,000 URLs.
	 */
	public function submitAll(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (!$this->config->get('module_indexnow_status')) {
			$json['error'] = $this->language->get('error_status');
		}

		if (!$json) {
			$items = [];
			$types = (array)$this->config->get('module_indexnow_types');

			$tables = [
				'product'      => "SELECT p.`product_id` AS id, ps.`store_id` FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_to_store` ps ON (ps.`product_id` = p.`product_id`) WHERE p.`status` = '1' AND p.`date_available` <= NOW()",
				'category'     => "SELECT c.`category_id` AS id, cs.`store_id` FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_to_store` cs ON (cs.`category_id` = c.`category_id`) WHERE c.`status` = '1'",
				'information'  => "SELECT i.`information_id` AS id, istore.`store_id` FROM `" . DB_PREFIX . "information` i LEFT JOIN `" . DB_PREFIX . "information_to_store` istore ON (istore.`information_id` = i.`information_id`) WHERE i.`status` = '1'",
				'manufacturer' => "SELECT m.`manufacturer_id` AS id, ms.`store_id` FROM `" . DB_PREFIX . "manufacturer` m LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (ms.`manufacturer_id` = m.`manufacturer_id`)"
			];

			foreach ($tables as $type => $sql) {
				if (in_array($type, $types)) {
					foreach ($this->db->query($sql)->rows as $row) {
						if ($row['store_id'] !== null) {
							$items[(int)$row['store_id']][$type][] = (int)$row['id'];
						}
					}
				}
			}

			$results = [];

			foreach ($items as $store_id => $by_type) {
				$results = array_merge($results, $this->submit($this->buildUrls($store_id, $by_type), 'all'));
			}

			$sent = array_sum(array_column($results, 'count'));
			$ok = array_filter($results, fn($result) => in_array($result['code'], [200, 202]));

			if ($results && count($ok) == count($results)) {
				$json['success'] = sprintf($this->language->get('text_submitted'), $sent);
			} elseif ($results) {
				$last = end($results);
				$json['error'] = sprintf($this->language->get('error_submit'), $last['code'], $last['message']);
			} else {
				$json['error'] = $this->language->get('error_nothing');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * The submissions log (last 20), loaded into the settings page.
	 */
	public function log(): void {
		$this->load->language($this->route);

		$data['logs'] = [];

		foreach ($this->db->query("SELECT * FROM `" . DB_PREFIX . "indexnow_log` ORDER BY `log_id` DESC LIMIT 20")->rows as $row) {
			$urls = json_decode($row['urls'], true) ?: [];

			$data['logs'][] = [
				'date'    => date($this->language->get('datetime_format'), strtotime($row['date_added'])),
				'trigger' => $row['trigger'],
				'count'   => count($urls),
				'urls'    => array_slice($urls, 0, 3),
				'more'    => max(0, count($urls) - 3),
				'code'    => (int)$row['code'],
				'ok'      => in_array((int)$row['code'], [200, 202]),
				'message' => $row['message']
			];
		}

		$this->response->setOutput($this->load->view('extension/indexnow/module/indexnow_log', $data));
	}

	// ---------------------------------------------------------------- events

	/**
	 * admin/model/catalog/product.addProduct/after and the other add methods: $output is the new ID.
	 */
	public function added(string &$route, array &$args, mixed &$output): void {
		$type = $this->typeFromRoute($route);

		if ($type && (int)$output) {
			$this->changed($type, (int)$output, (array)($args[0] ?? []), 'add');
		}
	}

	/**
	 * admin/model/catalog/product.editProduct/after and the other edit methods: $args = [id, data].
	 */
	public function edited(string &$route, array &$args, mixed &$output): void {
		$type = $this->typeFromRoute($route);

		if ($type && (int)($args[0] ?? 0)) {
			$this->changed($type, (int)$args[0], (array)($args[1] ?? []), 'edit');
		}
	}

	/**
	 * admin/model/catalog/product.deleteProduct/before: the URL is built while its SEO keyword still exists.
	 * Search engines then find a 404 and drop the page sooner.
	 */
	public function deleted(string &$route, array &$args): void {
		$type = $this->typeFromRoute($route);
		$id = (int)($args[0] ?? 0);

		if (!$type || !$id || !$this->config->get('module_indexnow_status') || !in_array($type, (array)$this->config->get('module_indexnow_types'))) {
			return;
		}

		// A disabled item was never public, so there's nothing for search engines to drop.
		if ($type != 'manufacturer') {
			$status = $this->db->query("SELECT `status` FROM `" . DB_PREFIX . $type . "` WHERE `" . $type . "_id` = '" . $id . "'")->row['status'] ?? 0;

			if (!$status) {
				return;
			}
		}

		$table = $type == 'information' ? 'information_to_store' : $type . '_to_store';

		foreach ($this->db->query("SELECT `store_id` FROM `" . DB_PREFIX . $table . "` WHERE `" . $type . "_id` = '" . $id . "'")->rows as $row) {
			$this->submit($this->buildUrls((int)$row['store_id'], [$type => [$id]]), 'delete');
		}
	}

	private function changed(string $type, int $id, array $data, string $trigger): void {
		if (!$this->config->get('module_indexnow_status') || !in_array($type, (array)$this->config->get('module_indexnow_types'))) {
			return;
		}

		// Disabled items aren't public, so there's nothing to tell search engines yet.
		if (in_array($type, ['product', 'category', 'information']) && empty($data['status'])) {
			return;
		}

		$stores = (array)($data[$type . '_store'] ?? [0]);

		foreach ($stores as $store_id) {
			$this->submit($this->buildUrls((int)$store_id, [$type => [$id]]), $trigger);
		}
	}

	private function typeFromRoute(string $route): string {
		foreach (['product', 'category', 'information', 'manufacturer'] as $type) {
			if (str_contains($route, 'catalog/' . $type . '.')) {
				return $type;
			}
		}

		return '';
	}

	// ---------------------------------------------------------------- URLs and sending

	/**
	 * Storefront URLs, built by the storefront itself (a store instance), so SEO URLs come out exactly
	 * as customers see them, in every language.
	 */
	private function buildUrls(int $store_id, array $by_type): array {
		$this->load->model('setting/store');
		$this->load->model('localisation/language');

		$routes = [
			'product'      => ['product/product', 'product_id'],
			'category'     => ['product/category', 'path'],
			'information'  => ['information/information', 'information_id'],
			'manufacturer' => ['product/manufacturer.info', 'manufacturer_id']
		];

		$urls = [];

		// Subcategories are linked by their full path (25_28), like the storefront menu does.
		$paths = [];

		if (!empty($by_type['category'])) {
			$query = $this->db->query("SELECT `category_id`, GROUP_CONCAT(`path_id` ORDER BY `level` SEPARATOR '_') AS path FROM `" . DB_PREFIX . "category_path` WHERE `category_id` IN (" . implode(',', array_map('intval', $by_type['category'])) . ") GROUP BY `category_id`");

			foreach ($query->rows as $row) {
				$paths[(int)$row['category_id']] = $row['path'];
			}
		}

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			if (!$language['status']) {
				continue;
			}

			$store = $this->model_setting_store->createStoreInstance($store_id, $language['code']);

			// Store instances don't run the SEO URL startup, so add it to get the same links as the storefront.
			$store->get('load')->controller('startup/seo_url');

			foreach ($by_type as $type => $ids) {
				foreach ($ids as $id) {
					$value = $type == 'category' ? ($paths[$id] ?? $id) : $id;

					$urls[] = html_entity_decode($store->get('url')->link($routes[$type][0], 'language=' . $language['code'] . '&' . $routes[$type][1] . '=' . $value), ENT_QUOTES, 'UTF-8');
				}
			}

			$store->get('session')->destroy();
		}

		return array_values(array_unique($urls));
	}

	/**
	 * POST the URLs to the IndexNow endpoint, one request per host and up to 10,000 URLs each, and log the answer.
	 */
	private function submit(array $urls, string $trigger): array {
		$results = [];

		if (!$urls) {
			return $results;
		}

		$by_host = [];

		foreach ($urls as $url) {
			$by_host[(string)parse_url($url, PHP_URL_HOST) . (parse_url($url, PHP_URL_PORT) ? ':' . parse_url($url, PHP_URL_PORT) : '')][] = $url;
		}

		$key = $this->getKey();
		$location = $this->keyLocation();

		foreach ($by_host as $host => $list) {
			foreach (array_chunk($list, 10000) as $chunk) {
				$body = ['host' => $host, 'key' => $key, 'urlList' => $chunk];

				// The key file only proves ownership of URLs in its own folder, so name it when it isn't at the root.
				if (!$location['root']) {
					$body['keyLocation'] = $location['url'];
				}

				$curl = curl_init((string)$this->config->get('module_indexnow_endpoint') ?: 'https://api.indexnow.org/indexnow');

				curl_setopt_array($curl, [
					CURLOPT_POST           => true,
					CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_SLASHES),
					CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CONNECTTIMEOUT => 3,
					CURLOPT_TIMEOUT        => 5
				]);

				$response = curl_exec($curl);
				$code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$error = curl_error($curl);

				curl_close($curl);

				$message = $code ? $this->describe($code) : $error;

				$this->db->query("INSERT INTO `" . DB_PREFIX . "indexnow_log` SET `trigger` = '" . $this->db->escape($trigger) . "', `urls` = '" . $this->db->escape(json_encode($chunk, JSON_UNESCAPED_SLASHES)) . "', `code` = '" . $code . "', `message` = '" . $this->db->escape(oc_substr($message . ($code >= 400 && $response ? ' ' . strip_tags((string)$response) : ''), 0, 255)) . "', `date_added` = NOW()");

				$results[] = ['code' => $code, 'message' => $message, 'count' => count($chunk)];
			}
		}

		// Keep the log short.
		$this->db->query("DELETE FROM `" . DB_PREFIX . "indexnow_log` WHERE `log_id` < (SELECT * FROM (SELECT MAX(`log_id`) - 200 FROM `" . DB_PREFIX . "indexnow_log`) AS t)");

		return $results;
	}

	private function describe(int $code): string {
		// Loaded with a prefix: this also runs inside the product, category and brand forms, and loading it
		// plainly would replace their own texts (their "Success: You have modified products!").
		$this->load->language($this->route, 'indexnow');

		$text = $this->language->get('indexnow_code_' . $code);

		return $text != 'indexnow_code_' . $code ? $text : 'HTTP ' . $code;
	}

	// ---------------------------------------------------------------- key

	private function getKey(): string {
		$key = (string)$this->config->get('module_indexnow_key');

		if (!preg_match('/^[a-zA-Z0-9-]{8,128}$/', $key)) {
			$key = bin2hex(random_bytes(16));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editValue('module_indexnow', 'module_indexnow_key', $key);
			$this->config->set('module_indexnow_key', $key);
		}

		return $key;
	}

	/**
	 * The key file goes in the store root ({key}.txt) when it can be written. If not, the storefront serves
	 * the key from a route, and each submission says where it is (keyLocation).
	 */
	private function writeKeyFile(string $key): bool {
		return @file_put_contents(DIR_OPENCART . $key . '.txt', $key) !== false;
	}

	private function keyLocation(): array {
		$key = $this->getKey();

		if (is_file(DIR_OPENCART . $key . '.txt') && trim((string)file_get_contents(DIR_OPENCART . $key . '.txt')) === $key) {
			return ['root' => true, 'url' => HTTP_CATALOG . $key . '.txt'];
		}

		return ['root' => false, 'url' => HTTP_CATALOG . 'index.php?route=extension/indexnow/module/indexnow.key'];
	}

	// ---------------------------------------------------------------- install

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "indexnow_log` (
			`log_id` INT(11) NOT NULL AUTO_INCREMENT,
			`trigger` VARCHAR(16) NOT NULL,
			`urls` MEDIUMTEXT NOT NULL,
			`code` INT(3) NOT NULL,
			`message` VARCHAR(255) NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`log_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach (['product' => 'Product', 'category' => 'Category', 'information' => 'Information', 'manufacturer' => 'Manufacturer'] as $type => $name) {
			foreach (['added' => ['add', 'after'], 'edited' => ['edit', 'after'], 'deleted' => ['delete', 'before']] as $method => [$verb, $when]) {
				$this->model_setting_event->addEvent([
					'code'        => 'indexnow_' . $type . '_' . $verb,
					'description' => 'IndexNow: submit ' . $type . ' URLs on ' . $verb,
					'trigger'     => 'admin/model/catalog/' . $type . '.' . $verb . $name . '/' . $when,
					'action'      => $this->route . '.' . $method,
					'status'      => 1,
					'sort_order'  => 0
				]);
			}
		}

		$key = bin2hex(random_bytes(16));

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_indexnow', [
			'module_indexnow_status'   => 0,
			'module_indexnow_endpoint' => 'https://api.indexnow.org/indexnow',
			'module_indexnow_types'    => ['product', 'category', 'information', 'manufacturer'],
			'module_indexnow_key'      => $key
		]);

		$this->writeKeyFile($key);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['product', 'category', 'information', 'manufacturer'] as $type) {
			foreach (['add', 'edit', 'delete'] as $verb) {
				$this->model_setting_event->deleteEventByCode('indexnow_' . $type . '_' . $verb);
			}
		}

		$key = (string)$this->config->get('module_indexnow_key');

		if ($key && preg_match('/^[a-zA-Z0-9-]+$/', $key) && is_file(DIR_OPENCART . $key . '.txt')) {
			@unlink(DIR_OPENCART . $key . '.txt');
		}

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "indexnow_log`");
	}
}
