<?php
namespace Opencart\Admin\Controller\Extension\Unitprice\Module;
/**
 * Unit Price: settings page and a Unit Price tab on the product form.
 */
class Unitprice extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/unitprice/module/unitprice';

	/**
	 * Units a product can contain, grouped for the select box.
	 */
	private array $units = [
		'text_metric' => ['mg', 'g', 'kg', 'ml', 'cl', 'l', 'cm', 'm', 'm2'],
		'text_us'     => ['oz', 'lb', 'floz'],
		'text_other'  => ['piece']
	];

	/**
	 * The extension's texts, loaded with a prefix so they never overwrite the texts of the product form.
	 */
	private function lang(): array {
		$text = [];

		foreach ($this->load->language($this->route, 'unitprice') as $key => $value) {
			$text[str_starts_with($key, 'unitprice_') ? substr($key, 10) : $key] = $value;
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

		$defaults = [
			'status'    => 0,
			'product'   => 1,
			'thumb'     => 1,
			'weight'    => 'kg',
			'volume'    => 'l',
			'us_weight' => 'oz',
			'format'    => $this->language->get('text_default_format'),
			'content'   => $this->language->get('text_default_content')
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_unitprice');

		foreach ($defaults as $key => $value) {
			$data['module_unitprice_' . $key] = array_key_exists('module_unitprice_' . $key, $saved) ? $saved['module_unitprice_' . $key] : $value;
		}

		$data['module_on'] = !empty($saved['module_unitprice_status']);

		// Products that have a unit price, for the overview table.
		$data['products'] = [];

		$query = $this->db->query("SELECT u.*, pd.`name` FROM `" . DB_PREFIX . "unitprice` u LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = u.`product_id` AND pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE u.`amount` > 0 ORDER BY pd.`name`");

		foreach ($query->rows as $row) {
			$data['products'][] = [
				'name'    => $row['name'],
				'content' => rtrim(rtrim(number_format((float)$row['amount'], 3, '.', ''), '0'), '.') . ' ' . $this->language->get('unit_' . $row['unit']),
				'edit'    => $this->url->link('catalog/product.form', $token . '&product_id=' . $row['product_id'])
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
		$format = (string)($post['module_unitprice_format'] ?? '');

		if (!$json && (!str_contains($format, '{price}') || !str_contains($format, '{unit}'))) {
			$json['error'] = $this->language->get('error_format');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_unitprice', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: admin/view/catalog/product_form/after. Adds a Unit Price tab.
	 */
	public function productForm(string &$route, array &$args, string &$output): void {
		$product_id = (int)($this->request->get['product_id'] ?? 0);

		$row = $product_id ? $this->db->query("SELECT * FROM `" . DB_PREFIX . "unitprice` WHERE `product_id` = '" . $product_id . "'")->row : [];

		$data = $this->lang();
		$data['unitprice_amount'] = !empty($row['amount']) ? rtrim(rtrim(number_format((float)$row['amount'], 4, '.', ''), '0'), '.') : '';
		$data['unitprice_unit'] = $row['unit'] ?? '';
		$data['groups'] = [];

		foreach ($this->units as $group => $units) {
			$options = [];

			foreach ($units as $unit) {
				$options[] = ['value' => $unit, 'text' => $data['unit_' . $unit]];
			}

			$data['groups'][] = ['label' => $data[$group], 'units' => $options];
		}

		$data['module_status'] = (bool)$this->config->get('module_unitprice_status');

		$tab = '<li class="nav-item"><a href="#tab-unitprice" data-bs-toggle="tab" class="nav-link">' . htmlspecialchars($data['tab_unitprice'], ENT_QUOTES, 'UTF-8') . '</a></li>';
		$pane = $this->load->view('extension/unitprice/module/unitprice_product_form', $data);

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

		if (!$product_id || !isset($data['unitprice'])) {
			return;
		}

		$amount = (float)str_replace(',', '.', (string)($data['unitprice']['amount'] ?? ''));
		$unit = (string)($data['unitprice']['unit'] ?? '');

		if ($amount <= 0 || !in_array($unit, array_merge(...array_values($this->units)), true)) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "unitprice` WHERE `product_id` = '" . $product_id . "'");

			return;
		}

		$this->db->query("REPLACE INTO `" . DB_PREFIX . "unitprice` SET `product_id` = '" . $product_id . "', `amount` = '" . $amount . "', `unit` = '" . $this->db->escape($unit) . "'");
	}

	/**
	 * Event: admin/model/catalog/product.copyProduct/after. Copies take the unit price along.
	 */
	public function productCopy(string &$route, array &$args, mixed &$output): void {
		$new_id = (int)$output;
		$old_id = (int)($args[0] ?? 0);

		if ($new_id && $old_id) {
			$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "unitprice` (`product_id`, `amount`, `unit`) SELECT '" . $new_id . "', `amount`, `unit` FROM `" . DB_PREFIX . "unitprice` WHERE `product_id` = '" . $old_id . "'");
		}
	}

	/**
	 * Event: admin/model/catalog/product.deleteProduct/after
	 */
	public function productDelete(string &$route, array &$args, mixed &$output): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "unitprice` WHERE `product_id` = '" . (int)($args[0] ?? 0) . "'");
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "unitprice` (
			`product_id` INT(11) NOT NULL,
			`amount` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
			`unit` VARCHAR(8) NOT NULL DEFAULT '',
			PRIMARY KEY (`product_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Unit Price: ' . $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach ($this->events() as $event) {
			$this->model_setting_event->deleteEventByCode($event[0]);
		}

		// The table is kept, so a reinstall keeps every product's content amount.
	}

	private function events(): array {
		return [
			['unitprice_product_form', 'admin/view/catalog/product_form/after', 'extension/unitprice/module/unitprice.productForm', 'Unit Price tab on the product form'],
			['unitprice_product_add', 'admin/model/catalog/product.addProduct/after', 'extension/unitprice/module/unitprice.productSave', 'Save the content of a new product'],
			['unitprice_product_edit', 'admin/model/catalog/product.editProduct/after', 'extension/unitprice/module/unitprice.productSave', 'Save the content of a product'],
			['unitprice_product_copy', 'admin/model/catalog/product.copyProduct/after', 'extension/unitprice/module/unitprice.productCopy', 'Copy the content with a copied product'],
			['unitprice_product_delete', 'admin/model/catalog/product.deleteProduct/after', 'extension/unitprice/module/unitprice.productDelete', 'Remove the content of a deleted product'],
			['unitprice_product', 'catalog/view/product/product/after', 'extension/unitprice/module/unitprice.product', 'Unit price on the product page'],
			['unitprice_thumb', 'catalog/view/product/thumb/after', 'extension/unitprice/module/unitprice.thumb', 'Unit price on product cards']
		];
	}
}
