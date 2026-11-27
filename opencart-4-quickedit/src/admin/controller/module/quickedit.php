<?php
namespace Opencart\Admin\Controller\Extension\Quickedit\Module;
/**
 * Product Quick Edit: inline price, quantity and status editing in Catalog > Products.
 */
class Quickedit extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/quickedit/module/quickedit';

	private function defaults(): array {
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_quickedit');
		$out = [];

		foreach (['status' => 0, 'price' => 1, 'quantity' => 1, 'pstatus' => 1] as $key => $value) {
			$out[$key] = array_key_exists('module_quickedit_' . $key, $saved) ? (int)$saved['module_quickedit_' . $key] : $value;
		}

		return $out;
	}

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

		foreach ($this->defaults() as $key => $value) {
			$data['module_quickedit_' . $key] = $value;
		}

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

			$this->model_setting_setting->editSetting('module_quickedit', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: admin/view/catalog/product_list/after. Adds the editors and the raw values they need.
	 */
	public function productList(string &$route, array &$args, string &$output): void {
		$settings = $this->defaults();

		if (!$settings['status'] || empty($args['products']) || !$this->user->hasPermission('modify', 'catalog/product')) {
			return;
		}

		$ids = array_map(fn($p) => (int)$p['product_id'], $args['products']);

		// Raw values (the list only has formatted prices). Variants and products with variants are left out.
		$query = $this->db->query("SELECT p.`product_id`, p.`price`, p.`quantity`, p.`status`, p.`master_id`, (SELECT COUNT(*) FROM `" . DB_PREFIX . "product` v WHERE v.`master_id` = p.`product_id`) AS `variants` FROM `" . DB_PREFIX . "product` p WHERE p.`product_id` IN (" . implode(',', $ids) . ")");

		$rows = [];

		foreach ($query->rows as $row) {
			$rows[(int)$row['product_id']] = [
				'price'    => (float)$row['price'],
				'quantity' => (int)$row['quantity'],
				'status'   => (int)$row['status'],
				'editable' => !(int)$row['master_id'] && !(int)$row['variants']
			];
		}

		$text = $this->load->language($this->route, 'qe');

		$data = [
			'rows'     => json_encode($rows),
			'fields'   => json_encode(['price' => (bool)$settings['price'], 'quantity' => (bool)$settings['quantity'], 'status' => (bool)$settings['pstatus']]),
			'url'      => html_entity_decode($this->url->link($this->route . '.update', 'user_token=' . $this->session->data['user_token']), ENT_QUOTES, 'UTF-8'),
			'saved'    => $text['qe_text_saved'],
			'click'    => $text['qe_text_click'],
			'variant'  => $text['qe_text_variant']
		];

		$output .= $this->load->view('extension/quickedit/module/quickedit_list', $data);
	}

	/**
	 * POST product_id, field (price, quantity or status) and value. Returns JSON.
	 */
	public function update(): void {
		$this->load->language($this->route);

		$json = [];

		$product_id = (int)($this->request->post['product_id'] ?? 0);
		$field = (string)($this->request->post['field'] ?? '');
		$value = trim(str_replace(',', '.', (string)($this->request->post['value'] ?? '')));

		if (!$this->user->hasPermission('modify', 'catalog/product') || !$this->defaults()['status'] || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$product = $this->db->query("SELECT p.`product_id`, p.`master_id`, (SELECT COUNT(*) FROM `" . DB_PREFIX . "product` v WHERE v.`master_id` = p.`product_id`) AS `variants` FROM `" . DB_PREFIX . "product` p WHERE p.`product_id` = '" . $product_id . "'")->row;

			if (!$product || (int)$product['master_id'] || (int)$product['variants']) {
				$json['error'] = $this->language->get('error_product');
			}
		}

		if (!$json) {
			if ($field == 'price' && is_numeric($value) && (float)$value >= 0) {
				$sql = "`price` = '" . (float)$value . "'";
			} elseif ($field == 'quantity' && preg_match('/^-?\d+$/', $value)) {
				$sql = "`quantity` = '" . (int)$value . "'";
			} elseif ($field == 'status' && in_array($value, ['0', '1'], true)) {
				$sql = "`status` = '" . (int)$value . "'";
			} else {
				$json['error'] = $this->language->get('error_value');
			}
		}

		if (!$json) {
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET " . $sql . ", `date_modified` = NOW() WHERE `product_id` = '" . $product_id . "'");

			// The storefront caches product lists; clear them so the change shows right away.
			$this->cache->delete('product');

			$row = $this->db->query("SELECT `price`, `quantity`, `status` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . $product_id . "'")->row;

			$json['success'] = $this->language->get('text_saved');
			$json['price'] = (float)$row['price'];
			$json['price_text'] = $this->currency->format((float)$row['price'], $this->config->get('config_currency'));
			$json['quantity'] = (int)$row['quantity'];
			$json['status'] = (int)$row['status'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'quickedit_product_list',
			'description' => 'Product Quick Edit: inline editors in the product list',
			'trigger'     => 'admin/view/catalog/product_list/after',
			'action'      => 'extension/quickedit/module/quickedit.productList',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('quickedit_product_list');
	}
}
