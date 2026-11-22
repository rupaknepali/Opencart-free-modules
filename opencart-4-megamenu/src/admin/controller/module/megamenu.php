<?php
namespace Opencart\Admin\Controller\Extension\Megamenu\Module;
/**
 * Mega Menu: wide category panels with subcategories, a third level, category images and a featured product.
 */
class Megamenu extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/megamenu/module/megamenu';

	private array $defaults = [
		'module_megamenu_status'     => 0,
		'module_megamenu_third'      => 1,
		'module_megamenu_limit'      => 5,
		'module_megamenu_image'      => 1,
		'module_megamenu_categories' => [],
		'module_megamenu_links'      => []
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
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');
		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_megamenu');

		foreach ($this->defaults as $key => $value) {
			$data[$key] = array_key_exists($key, $saved) ? $saved[$key] : $value;
		}

		// Top-level categories: the menu's tabs.
		$language_id = (int)$this->config->get('config_language_id');

		$this->load->model('catalog/product');

		$data['categories'] = [];

		$query = $this->db->query("SELECT c.`category_id`, cd.`name`, c.`status`, (SELECT COUNT(*) FROM `" . DB_PREFIX . "category` c2 WHERE c2.`parent_id` = c.`category_id`) AS children FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.`category_id` = c.`category_id` AND cd.`language_id` = '" . $language_id . "') WHERE c.`parent_id` = '0' ORDER BY c.`sort_order`, LCASE(cd.`name`)");

		foreach ($query->rows as $row) {
			$setting = $data['module_megamenu_categories'][$row['category_id']] ?? [];
			$product_id = (int)($setting['product_id'] ?? 0);
			$product = $product_id ? $this->model_catalog_product->getProduct($product_id) : [];

			$data['categories'][] = [
				'category_id' => (int)$row['category_id'],
				'name'        => $row['name'],
				'enabled'     => (int)$row['status'],
				'children'    => (int)$row['children'],
				'show'        => (int)($setting['show'] ?? 1),
				'columns'     => (int)($setting['columns'] ?? 3),
				'product_id'  => $product ? $product_id : 0,
				'product'     => $product ? $product['name'] : ''
			];
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

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
			$post = $this->request->post;

			$categories = [];

			foreach ((array)($post['module_megamenu_categories'] ?? []) as $category_id => $setting) {
				$categories[(int)$category_id] = [
					'show'       => (int)!empty($setting['show']),
					'columns'    => max(1, min(4, (int)($setting['columns'] ?? 3))),
					'product_id' => (int)($setting['product_id'] ?? 0)
				];
			}

			$links = [];

			foreach ((array)($post['module_megamenu_links'] ?? []) as $link) {
				$titles = array_map(fn($title) => oc_substr(trim(html_entity_decode((string)$title, ENT_QUOTES, 'UTF-8')), 0, 64), (array)($link['title'] ?? []));
				$href = trim(html_entity_decode((string)($link['href'] ?? ''), ENT_QUOTES, 'UTF-8'));

				// No javascript: or other schemes, only routes, paths and http(s) addresses.
				if (array_filter($titles) && $href !== '' && preg_match('#^(https?://|/|[a-z0-9_]+/[a-z0-9_./]+)#i', $href)) {
					$links[] = ['title' => array_map('htmlspecialchars', $titles), 'href' => htmlspecialchars($href)];
				}
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_megamenu', [
				'module_megamenu_status'     => (int)!empty($post['module_megamenu_status']),
				'module_megamenu_third'      => (int)!empty($post['module_megamenu_third']),
				'module_megamenu_limit'      => max(1, min(20, (int)($post['module_megamenu_limit'] ?? 5))),
				'module_megamenu_image'      => (int)!empty($post['module_megamenu_image']),
				'module_megamenu_categories' => $categories,
				'module_megamenu_links'      => $links
			]);

			$this->cache->delete('megamenu');

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Category and product changes: rebuild the cached menu.
	 */
	public function clear(string &$route, array &$args, mixed &$output): void {
		$this->cache->delete('megamenu');
	}

	private function events(): array {
		return [
			'megamenu_menu'          => ['catalog/controller/common/menu/after', 'menu'],
			'megamenu_clear_cat_add' => ['admin/model/catalog/category.add*/after', 'clear'],
			'megamenu_clear_cat_edit'=> ['admin/model/catalog/category.edit*/after', 'clear'],
			'megamenu_clear_cat_del' => ['admin/model/catalog/category.delete*/after', 'clear'],
			'megamenu_clear_product' => ['admin/model/catalog/product.edit*/after', 'clear'],
			'megamenu_clear_prod_del'=> ['admin/model/catalog/product.delete*/after', 'clear']
		];
	}

	public function install(): void {
		$this->load->model('setting/event');

		foreach ($this->events() as $code => [$trigger, $method]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Mega Menu',
				'trigger'     => $trigger,
				'action'      => $this->route . '.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_megamenu', $this->defaults);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (array_keys($this->events()) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		$this->cache->delete('megamenu');
	}
}
