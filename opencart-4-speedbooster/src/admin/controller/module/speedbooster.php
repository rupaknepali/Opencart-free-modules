<?php
namespace Opencart\Admin\Controller\Extension\Speedbooster\Module;
/**
 * Speed Booster: full-page cache for guests, lazy loading, HTML minify, a speed check of your settings and
 * server, and the database indexes OpenCart is missing.
 */
class Speedbooster extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/speedbooster/module/speedbooster';

	/**
	 * Columns that OpenCart looks up by but doesn't index (table => columns).
	 */
	private array $indexes = [
		'order_history'        => ['order_id'],
		'order_option'         => ['order_id'],
		'order_subscription'   => ['order_id'],
		'product'              => ['manufacturer_id', 'master_id'],
		'product_option'       => ['product_id'],
		'product_option_value' => ['product_option_id', 'product_id'],
		'product_reward'       => ['product_id'],
		'product_related'      => ['related_id'],
		'product_report'       => ['product_id'],
		'category_path'        => ['path_id'],
		'coupon_product'       => ['coupon_id'],
		'customer_activity'    => ['customer_id'],
		'customer_reward'      => ['customer_id'],
		'customer_transaction' => ['customer_id'],
		'subscription'         => ['customer_id']
	];

	/**
	 * Admin changes that make cached pages out of date. Each one empties the page cache.
	 */
	private array $purge_triggers = [
		'admin/model/catalog/*.add*/after',
		'admin/model/catalog/*.edit*/after',
		'admin/model/catalog/*.copy*/after',
		'admin/model/catalog/*.delete*/after',
		'admin/model/design/*.add*/after',
		'admin/model/design/*.edit*/after',
		'admin/model/design/*.delete*/after',
		'admin/model/setting/setting.editSetting/after',
		'admin/model/setting/module.*Module/after',
		'admin/model/localisation/*.edit*/after',
		'admin/model/marketing/*.edit*/after',
		'admin/model/cms/*.edit*/after'
	];

	private array $defaults = [
		'module_speedbooster_status'         => 0,
		'module_speedbooster_cache_status'   => 1,
		'module_speedbooster_cache_ttl'      => 60,
		'module_speedbooster_cache_exclude'  => "account/*\ncheckout/*\naffiliate/*\nproduct/compare\nproduct/search\ninformation/contact\ninformation/gdpr*\ncms/*",
		'module_speedbooster_vary_cookies'   => 'policy',
		'module_speedbooster_lazy_status'    => 1,
		'module_speedbooster_lazy_skip'      => 3,
		'module_speedbooster_minify_status'  => 1,
		'module_speedbooster_preconnect'     => ''
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
		$data['purge'] = $this->url->link($this->route . '.purge', $token);
		$data['report'] = $this->url->link($this->route . '.report', $token);
		$data['fix'] = $this->url->link($this->route . '.fix', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('setting/setting');

		// Defaults for keys never saved: Config::get() would give '' on a fresh install.
		$saved = $this->model_setting_setting->getSetting('module_speedbooster');

		foreach ($this->defaults as $key => $value) {
			$data[$key] = array_key_exists($key, $saved) ? $saved[$key] : $value;
		}

		$data['catalog'] = HTTP_CATALOG;

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

		$ttl = (int)($post['module_speedbooster_cache_ttl'] ?? 0);

		if ($ttl < 1 || $ttl > 10080) {
			$json['error']['ttl'] = $this->language->get('error_ttl');
		}

		foreach (preg_split('/\R/', (string)($post['module_speedbooster_preconnect'] ?? '')) as $origin) {
			$origin = trim(html_entity_decode($origin, ENT_QUOTES, 'UTF-8'));

			if ($origin !== '' && !preg_match('#^https?://[a-z0-9.-]+(:\d+)?$#i', $origin)) {
				$json['error']['preconnect'] = sprintf($this->language->get('error_preconnect'), htmlspecialchars($origin));

				break;
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_speedbooster', [
				'module_speedbooster_status'        => (int)!empty($post['module_speedbooster_status']),
				'module_speedbooster_cache_status'  => (int)!empty($post['module_speedbooster_cache_status']),
				'module_speedbooster_cache_ttl'     => $ttl,
				'module_speedbooster_cache_exclude' => trim(html_entity_decode((string)($post['module_speedbooster_cache_exclude'] ?? ''), ENT_QUOTES, 'UTF-8')),
				'module_speedbooster_vary_cookies'  => trim(preg_replace('/[^a-zA-Z0-9_,\s-]/', '', html_entity_decode((string)($post['module_speedbooster_vary_cookies'] ?? ''), ENT_QUOTES, 'UTF-8'))),
				'module_speedbooster_lazy_status'   => (int)!empty($post['module_speedbooster_lazy_status']),
				'module_speedbooster_lazy_skip'     => max(0, min(20, (int)($post['module_speedbooster_lazy_skip'] ?? 3))),
				'module_speedbooster_minify_status' => (int)!empty($post['module_speedbooster_minify_status']),
				'module_speedbooster_preconnect'    => trim(html_entity_decode((string)($post['module_speedbooster_preconnect'] ?? ''), ENT_QUOTES, 'UTF-8'))
			]);

			// editSetting fires the purge event too, but make sure: new settings mean new HTML.
			$this->purgeFiles();

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function purge(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$count = $this->purgeFiles();

			$json['success'] = sprintf($this->language->get('text_purged'), $count);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// ---------------------------------------------------------------- speed report

	/**
	 * The speed check: cache size, server, settings that slow OpenCart down, and missing database indexes.
	 */
	public function report(): void {
		$this->load->language($this->route);

		$data['checks'] = [];

		// Page cache
		$files = glob(DIR_CACHE . 'speedbooster/*/*.html') ?: [];
		$size = 0;

		foreach ($files as $file) {
			$size += (int)@filesize($file);
		}

		$data['cache_pages'] = count($files);
		$data['cache_size'] = $this->bytes($size);

		$status = (bool)$this->config->get('module_speedbooster_status') && (bool)$this->config->get('module_speedbooster_cache_status');

		$data['checks'][] = [
			'name'   => $this->language->get('check_page_cache'),
			'value'  => $status ? sprintf($this->language->get('text_cache_pages'), count($files), $this->bytes($size)) : $this->language->get('text_off'),
			'state'  => $status ? 'ok' : 'warn',
			'advice' => $status ? '' : $this->language->get('advice_page_cache')
		];

		// PHP version
		$data['checks'][] = [
			'name'   => $this->language->get('check_php'),
			'value'  => PHP_VERSION,
			'state'  => version_compare(PHP_VERSION, '8.2', '>=') ? 'ok' : 'warn',
			'advice' => version_compare(PHP_VERSION, '8.2', '>=') ? '' : $this->language->get('advice_php')
		];

		// OPcache
		$opcache = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
		$on = is_array($opcache) && !empty($opcache['opcache_enabled']);

		$data['checks'][] = [
			'name'   => $this->language->get('check_opcache'),
			'value'  => $on ? sprintf($this->language->get('text_opcache'), round(($opcache['memory_usage']['used_memory'] ?? 0) / 1048576), (int)($opcache['opcache_statistics']['num_cached_scripts'] ?? 0)) : $this->language->get('text_off'),
			'state'  => $on ? 'ok' : 'bad',
			'advice' => $on ? '' : $this->language->get('advice_opcache')
		];

		// Cache engine (system/config/default.php or CACHE_ENGINE in config.php)
		$engine = (string)$this->config->get('cache_engine');

		$data['checks'][] = [
			'name'   => $this->language->get('check_cache_engine'),
			'value'  => $engine,
			'state'  => $engine == 'file' ? 'info' : 'ok',
			'advice' => $engine == 'file' ? $this->language->get('advice_cache_engine') : ''
		];

		$this->load->model('setting/setting');

		// Category product count: a COUNT query per category on every page that shows the menu.
		$count = $this->countSetting('config_product_count');

		$data['checks'][] = [
			'name'   => $this->language->get('check_product_count'),
			'value'  => $count ? $this->language->get('text_on') : $this->language->get('text_off'),
			'state'  => $count ? 'warn' : 'ok',
			'advice' => $count ? $this->language->get('advice_product_count') : '',
			'fix'    => $count ? 'product_count' : ''
		];

		// Customers online / activity: a database write on every page view.
		$online = $this->countSetting('config_customer_online') + $this->countSetting('config_customer_activity');

		$data['checks'][] = [
			'name'   => $this->language->get('check_customer_online'),
			'value'  => $online ? $this->language->get('text_on') : $this->language->get('text_off'),
			'state'  => $online ? 'warn' : 'ok',
			'advice' => $online ? $this->language->get('advice_customer_online') : '',
			'fix'    => $online ? 'customer_online' : ''
		];

		// Output compression
		$level = (int)$this->config->get('config_compression');
		$server = $this->serverCompresses();

		if ($level) {
			$state = 'ok';
			$value = sprintf($this->language->get('text_level'), $level);
			$advice = '';
		} elseif ($server) {
			$state = 'ok';
			$value = sprintf($this->language->get('text_server_compresses'), $server);
			$advice = '';
		} elseif ($server === null) {
			// The server couldn't load its own storefront (firewall, Docker, basic auth): don't guess.
			$state = 'info';
			$value = $this->language->get('text_unknown');
			$advice = $this->language->get('advice_compression_unknown');
		} else {
			$state = 'warn';
			$value = $this->language->get('text_off');
			$advice = $this->language->get('advice_compression');
		}

		$data['checks'][] = [
			'name'   => $this->language->get('check_compression'),
			'value'  => $value,
			'state'  => $state,
			'advice' => $advice,
			'fix'    => $state == 'warn' && extension_loaded('zlib') ? 'compression' : ''
		];

		// Expired sessions
		$sessions = $this->db->query("SELECT COUNT(*) AS total, SUM(`expire` < NOW()) AS expired FROM `" . DB_PREFIX . "session`")->row;

		$data['checks'][] = [
			'name'   => $this->language->get('check_sessions'),
			'value'  => sprintf($this->language->get('text_sessions'), (int)$sessions['total'], (int)$sessions['expired']),
			'state'  => (int)$sessions['expired'] > 1000 ? 'warn' : 'ok',
			'advice' => (int)$sessions['expired'] > 1000 ? $this->language->get('advice_sessions') : '',
			'fix'    => (int)$sessions['expired'] ? 'sessions' : ''
		];

		// Error log size
		$log = DIR_LOGS . $this->config->get('config_error_filename');
		$log_size = is_file($log) ? (int)filesize($log) : 0;

		$data['checks'][] = [
			'name'   => $this->language->get('check_error_log'),
			'value'  => $this->bytes($log_size),
			'state'  => $log_size > 10485760 ? 'warn' : 'ok',
			'advice' => $log_size > 10485760 ? $this->language->get('advice_error_log') : ''
		];

		// Image cache
		$images = 0;

		if (is_dir(DIR_IMAGE . 'cache')) {
			foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(DIR_IMAGE . 'cache', \FilesystemIterator::SKIP_DOTS)) as $file) {
				$images++;

				if ($images > 50000) {
					break;
				}
			}
		}

		$data['checks'][] = [
			'name'   => $this->language->get('check_image_cache'),
			'value'  => sprintf($this->language->get('text_images'), $images > 50000 ? '50,000+' : number_format($images)),
			'state'  => 'info',
			'advice' => $this->language->get('advice_image_cache')
		];

		// Database indexes
		$data['indexes'] = $this->missingIndexes();

		$this->response->setOutput($this->load->view('extension/speedbooster/module/speedbooster_report', $data));
	}

	/**
	 * One-click fixes from the speed report.
	 */
	public function fix(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		$type = (string)($this->request->get['type'] ?? '');

		if (!$json) {
			$this->load->model('setting/setting');

			switch ($type) {
				case 'product_count':
					$this->editAllStores('config_product_count', 0);
					break;
				case 'customer_online':
					$this->editAllStores('config_customer_online', 0);
					$this->editAllStores('config_customer_activity', 0);
					break;
				case 'compression':
					$this->model_setting_setting->editValue('config', 'config_compression', 5, 0);
					break;
				case 'sessions':
					$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `expire` < NOW()");
					break;
				case 'indexes':
					foreach ($this->missingIndexes() as $index) {
						$this->db->query("ALTER TABLE `" . DB_PREFIX . $index['table'] . "` ADD INDEX `speedbooster_" . $index['column'] . "` (`" . $index['column'] . "`)");
					}
					break;
				default:
					$json['error'] = $this->language->get('error_fix');
			}

			if (!$json) {
				$this->purgeFiles();

				$json['success'] = $this->language->get('text_fixed_' . $type);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function countSetting(string $key): int {
		return (int)$this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "setting` WHERE `code` = 'config' AND `key` = '" . $this->db->escape($key) . "' AND `value` = '1'")->row['total'];
	}

	private function editAllStores(string $key, $value): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $this->db->escape((string)$value) . "' WHERE `code` = 'config' AND `key` = '" . $this->db->escape($key) . "'");
	}

	private function missingIndexes(): array {
		$missing = [];

		foreach ($this->indexes as $table => $columns) {
			foreach ($columns as $column) {
				$exists = $this->db->query("SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = '" . $this->db->escape(DB_PREFIX . $table) . "' AND `COLUMN_NAME` = '" . $this->db->escape($column) . "'")->row['total'];

				if (!$exists) {
					continue;
				}

				// Indexed already if the column leads any index.
				$indexed = $this->db->query("SELECT COUNT(*) AS total FROM information_schema.STATISTICS WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = '" . $this->db->escape(DB_PREFIX . $table) . "' AND `COLUMN_NAME` = '" . $this->db->escape($column) . "' AND `SEQ_IN_INDEX` = 1")->row['total'];

				if (!$indexed) {
					$rows = (int)$this->db->query("SELECT `TABLE_ROWS` FROM information_schema.TABLES WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = '" . $this->db->escape(DB_PREFIX . $table) . "'")->row['TABLE_ROWS'];

					$missing[] = ['table' => $table, 'column' => $column, 'rows' => $rows];
				}
			}
		}

		return $missing;
	}

	/**
	 * Ask the storefront for its home page with gzip allowed, and see if the web server compresses it.
	 */
	private function serverCompresses(): ?string {
		if (!function_exists('curl_init')) {
			return null;
		}

		$curl = curl_init(HTTP_CATALOG . 'index.php?route=common/home&nocache=1');

		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_NOBODY         => false,
			CURLOPT_ENCODING       => '',
			CURLOPT_HTTPHEADER     => ['Accept-Encoding: br, gzip'],
			CURLOPT_CONNECTTIMEOUT => 2,
			CURLOPT_TIMEOUT        => 5,
			CURLOPT_SSL_VERIFYPEER => false
		]);

		$response = (string)curl_exec($curl);
		$header_size = (int)curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($code != 200) {
			return null;
		}

		if (preg_match('/^Content-Encoding:\s*(\S+)/im', substr($response, 0, $header_size), $match)) {
			return $match[1];
		}

		return '';
	}

	private function purgeFiles(): int {
		$count = 0;
		$dir = DIR_CACHE . 'speedbooster/';

		if (!is_dir($dir)) {
			return 0;
		}

		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
			if ($file->isDir()) {
				@rmdir($file->getPathname());
			} else {
				if (str_ends_with($file->getFilename(), '.html')) {
					$count++;
				}

				@unlink($file->getPathname());
			}
		}

		return $count;
	}

	private function bytes(int $bytes): string {
		foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
			if ($bytes < 1024 || $unit == 'GB') {
				return round($bytes, $unit == 'B' ? 0 : 1) . ' ' . $unit;
			}

			$bytes /= 1024;
		}

		return '';
	}

	// ---------------------------------------------------------------- events

	/**
	 * Admin saves (products, categories, layouts, settings, modules...): empty the page cache.
	 */
	public function adminChange(string &$route, array &$args, mixed &$output): void {
		$this->purgeFiles();
	}

	// ---------------------------------------------------------------- install

	public function install(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('speedbooster');

		$this->model_setting_startup->addStartup([
			'code'        => 'speedbooster',
			'description' => 'Speed Booster: serve cached pages to guests',
			'action'      => 'catalog/' . $this->route . '.startup',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('setting/event');

		foreach ($this->purge_triggers as $i => $trigger) {
			$this->model_setting_event->addEvent([
				'code'        => 'speedbooster_purge_' . $i,
				'description' => 'Speed Booster: empty the page cache after admin changes',
				'trigger'     => $trigger,
				'action'      => $this->route . '.adminChange',
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->model_setting_event->addEvent([
			'code'        => 'speedbooster_order',
			'description' => 'Speed Booster: refresh the pages of ordered products (stock, price)',
			'trigger'     => 'catalog/model/checkout/order.addHistory/after',
			'action'      => $this->route . '.order',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_speedbooster', $this->defaults);
	}

	public function uninstall(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('speedbooster');

		$this->load->model('setting/event');

		foreach (array_keys($this->purge_triggers) as $i) {
			$this->model_setting_event->deleteEventByCode('speedbooster_purge_' . $i);
		}

		$this->model_setting_event->deleteEventByCode('speedbooster_order');

		$this->purgeFiles();

		@rmdir(DIR_CACHE . 'speedbooster');

		// The database indexes stay: they only make OpenCart faster and don't depend on this extension.
	}
}
