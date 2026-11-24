<?php
namespace Opencart\Admin\Controller\Extension\Preorder\Module;
/**
 * Pre-order: settings page, a Pre-order tab on the product form, and the pre-order card on the order page.
 */
class Preorder extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/preorder/module/preorder';

	/**
	 * The extension's texts, loaded with a prefix so they never overwrite the texts of the page they're added to.
	 */
	private function lang(): array {
		$text = [];

		foreach ($this->load->language($this->route, 'preorder') as $key => $value) {
			$text[str_starts_with($key, 'preorder_') ? substr($key, 9) : $key] = $value;
		}

		return $text;
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
		$data['stock_checkout'] = (bool)$this->config->get('config_stock_checkout');

		$defaults = [
			'status'       => 0,
			'button'       => $this->language->get('text_default_button'),
			'ship_text'    => $this->language->get('text_default_ship'),
			'no_date_text' => $this->language->get('text_default_no_date')
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_preorder');

		foreach ($defaults as $key => $value) {
			$data['module_preorder_' . $key] = array_key_exists('module_preorder_' . $key, $saved) ? $saved['module_preorder_' . $key] : $value;
		}

		// Products on pre-order now, for the overview table.
		$data['products'] = [];

		$query = $this->db->query("SELECT po.*, p.`quantity`, pd.`name`, (SELECT COALESCE(SUM(oo.`quantity`), 0) FROM `" . DB_PREFIX . "preorder_order` oo LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = oo.`order_id`) WHERE oo.`product_id` = po.`product_id` AND o.`order_status_id` > '0') AS `ordered` FROM `" . DB_PREFIX . "preorder` po LEFT JOIN `" . DB_PREFIX . "product` p ON (p.`product_id` = po.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = po.`product_id` AND pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE po.`status` = '1' ORDER BY pd.`name`");

		foreach ($query->rows as $row) {
			$data['products'][] = [
				'name'      => $row['name'],
				'quantity'  => (int)$row['quantity'],
				'ship_date' => ($row['ship_date'] && $row['ship_date'] != '0000-00-00') ? date($this->language->get('date_format_short'), strtotime($row['ship_date'])) : '',
				'ordered'   => (int)$row['ordered'],
				'active'    => (int)$row['quantity'] <= 0,
				'edit'      => $this->url->link('catalog/product.form', $token . '&product_id=' . $row['product_id'])
			];
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

		$post = $this->request->post;

		// The ship text must contain one %s for the date.
		if (!$json && substr_count((string)($post['module_preorder_ship_text'] ?? ''), '%s') != 1) {
			$json['error'] = $this->language->get('error_ship_text');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_preorder', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: admin/view/catalog/product_form/after. Adds a Pre-order tab.
	 */
	public function productForm(string &$route, array &$args, string &$output): void {
		$product_id = (int)($this->request->get['product_id'] ?? 0);

		$row = $product_id ? $this->db->query("SELECT * FROM `" . DB_PREFIX . "preorder` WHERE `product_id` = '" . $product_id . "'")->row : [];

		$data = $this->lang();
		$data['preorder_status'] = (int)($row['status'] ?? 0);
		$data['preorder_ship_date'] = (!empty($row['ship_date']) && $row['ship_date'] != '0000-00-00') ? $row['ship_date'] : '';
		$data['preorder_note'] = $row['note'] ?? '';
		$data['module_status'] = (bool)$this->config->get('module_preorder_status');

		$tab = '<li class="nav-item"><a href="#tab-preorder" data-bs-toggle="tab" class="nav-link">' . htmlspecialchars($data['tab_preorder'], ENT_QUOTES, 'UTF-8') . '</a></li>';
		$pane = $this->load->view('extension/preorder/module/preorder_product_form', $data);

		if (($position = strpos($output, '<li class="nav-item"><a href="#tab-design"')) !== false) {
			$output = substr_replace($output, $tab, $position, 0);
		}

		if (($position = strpos($output, '<div id="tab-design" class="tab-pane">')) !== false) {
			$output = substr_replace($output, $pane, $position, 0);
		}
	}

	/**
	 * Events: admin/model/catalog/product.addProduct/after and product.editProduct/after.
	 */
	public function productSave(string &$route, array &$args, mixed &$output): void {
		if (str_ends_with($route, 'addProduct')) {
			$product_id = (int)$output;
			$data = $args[0] ?? [];
		} else {
			$product_id = (int)($args[0] ?? 0);
			$data = $args[1] ?? [];
		}

		if (!$product_id || !isset($data['preorder'])) {
			return;
		}

		$preorder = (array)$data['preorder'];
		$ship_date = (string)($preorder['ship_date'] ?? '');

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ship_date) || !checkdate((int)substr($ship_date, 5, 2), (int)substr($ship_date, 8, 2), (int)substr($ship_date, 0, 4))) {
			$ship_date = '';
		}

		$this->db->query("REPLACE INTO `" . DB_PREFIX . "preorder` SET `product_id` = '" . $product_id . "', `status` = '" . (!empty($preorder['status']) ? 1 : 0) . "', `ship_date` = " . ($ship_date ? "'" . $ship_date . "'" : "NULL") . ", `note` = '" . $this->db->escape(oc_substr(trim(html_entity_decode((string)($preorder['note'] ?? ''), ENT_QUOTES, 'UTF-8')), 0, 255)) . "'");
	}

	/**
	 * Event: admin/model/catalog/product.deleteProduct/after
	 */
	public function productDelete(string &$route, array &$args, mixed &$output): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "preorder` WHERE `product_id` = '" . (int)($args[0] ?? 0) . "'");
	}

	/**
	 * Event: admin/view/sale/order_info/after. Lists the pre-ordered lines with their promised ship dates.
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		$order_id = (int)($this->request->get['order_id'] ?? 0);

		if (!$order_id) {
			return;
		}

		$lines = $this->db->query("SELECT * FROM `" . DB_PREFIX . "preorder_order` WHERE `order_id` = '" . $order_id . "' ORDER BY `preorder_order_id`")->rows;

		if (!$lines) {
			return;
		}

		$data = $this->lang();
		$data['lines'] = [];

		foreach ($lines as $line) {
			$data['lines'][] = [
				'name'      => $line['name'],
				'quantity'  => $line['quantity'],
				'ship_date' => $line['ship_date'] ? date($this->language->get('date_format_short'), strtotime($line['ship_date'])) : $data['text_no_date']
			];
		}

		$html = $this->load->view('extension/preorder/module/preorder_order', $data);

		// Above the order history card (the one with the comment icon).
		if (preg_match('#<div class="card mb-3">\s*<div class="card-header"><i class="fa-solid fa-comment"></i>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1], 0);
		}
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "preorder` (
			`product_id` INT(11) NOT NULL,
			`status` TINYINT(1) NOT NULL DEFAULT '0',
			`ship_date` DATE NULL,
			`note` VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (`product_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "preorder_order` (
			`preorder_order_id` INT(11) NOT NULL AUTO_INCREMENT,
			`order_id` INT(11) NOT NULL,
			`product_id` INT(11) NOT NULL,
			`name` VARCHAR(255) NOT NULL,
			`quantity` INT(4) NOT NULL,
			`ship_date` DATE NULL,
			PRIMARY KEY (`preorder_order_id`),
			KEY `order_id` (`order_id`),
			KEY `product_id` (`product_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		$events = [
			['preorder_product_form', 'admin/view/catalog/product_form/after', 'extension/preorder/module/preorder.productForm', 'Pre-order tab on the product form'],
			['preorder_product_add', 'admin/model/catalog/product.addProduct/after', 'extension/preorder/module/preorder.productSave', 'Save pre-order settings of a new product'],
			['preorder_product_edit', 'admin/model/catalog/product.editProduct/after', 'extension/preorder/module/preorder.productSave', 'Save pre-order settings of a product'],
			['preorder_product_delete', 'admin/model/catalog/product.deleteProduct/after', 'extension/preorder/module/preorder.productDelete', 'Remove pre-order settings of a deleted product'],
			['preorder_order_info', 'admin/view/sale/order_info/after', 'extension/preorder/module/preorder.orderInfo', 'Pre-order card on the order page'],
			['preorder_product_before', 'catalog/view/product/product/before', 'extension/preorder/module/preorder.productBefore', 'Pre-order button label and ship date'],
			['preorder_product_after', 'catalog/view/product/product/after', 'extension/preorder/module/preorder.productAfter', 'Pre-order notice under the button'],
			['preorder_thumb', 'catalog/view/product/thumb/after', 'extension/preorder/module/preorder.thumb', 'Pre-order badge on product cards'],
			['preorder_cart', 'catalog/view/checkout/cart_list/after', 'extension/preorder/module/preorder.cartList', 'Ship date on pre-order cart lines'],
			['preorder_order_add', 'catalog/model/checkout/order.addOrder/after', 'extension/preorder/module/preorder.saveOrder', 'Save pre-order lines with a new order'],
			['preorder_order_edit', 'catalog/model/checkout/order.editOrder/after', 'extension/preorder/module/preorder.saveOrder', 'Save pre-order lines with an edited order'],
			['preorder_mail', 'catalog/view/mail/order_add/after', 'extension/preorder/module/preorder.mail', 'Pre-order notice in the order email']
		];

		foreach ($events as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Pre-order: ' . $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/startup');

		$this->model_setting_startup->addStartup([
			'code'        => 'preorder',
			'description' => 'Pre-order: allow checkout when every out-of-stock cart line is a pre-order product',
			'action'      => 'catalog/extension/preorder/module/preorder.startup',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['preorder_product_form', 'preorder_product_add', 'preorder_product_edit', 'preorder_product_delete', 'preorder_order_info', 'preorder_product_before', 'preorder_product_after', 'preorder_thumb', 'preorder_cart', 'preorder_order_add', 'preorder_order_edit', 'preorder_mail'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('preorder');

		// The tables are kept, so a reinstall keeps product settings and the pre-order history of orders.
	}
}
