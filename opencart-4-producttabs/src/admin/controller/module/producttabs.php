<?php
namespace Opencart\Admin\Controller\Extension\Producttabs\Module;
/**
 * Product Tabs: extra tabs on product pages, shared by all products, by categories, or set for chosen products.
 */
class Producttabs extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/producttabs/module/producttabs';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = $this->breadcrumbs();

		$data['save'] = $this->url->link($this->route . '.saveSettings', $token);
		$data['add'] = $this->url->link($this->route . '.form', $token);
		$data['delete'] = $this->url->link($this->route . '.delete', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_producttabs');

		$data['module_producttabs_status'] = $saved['module_producttabs_status'] ?? 0;

		$data['tabs'] = [];

		$query = $this->db->query("SELECT t.*, td.`title`, (SELECT COUNT(*) FROM `" . DB_PREFIX . "producttab_to_category` WHERE `tab_id` = t.`tab_id`) AS categories, (SELECT COUNT(*) FROM `" . DB_PREFIX . "producttab_to_product` WHERE `tab_id` = t.`tab_id`) AS products FROM `" . DB_PREFIX . "producttab` t LEFT JOIN `" . DB_PREFIX . "producttab_description` td ON (td.`tab_id` = t.`tab_id` AND td.`language_id` = '" . (int)$this->config->get('config_language_id') . "') ORDER BY t.`sort_order`, td.`title`");

		foreach ($query->rows as $row) {
			$data['tabs'][] = [
				'tab_id'     => (int)$row['tab_id'],
				'title'      => $row['title'],
				'scope'      => $row['scope'],
				'categories' => (int)$row['categories'],
				'products'   => (int)$row['products'],
				'sort_order' => (int)$row['sort_order'],
				'status'     => (int)$row['status'],
				'edit'       => $this->url->link($this->route . '.form', $token . '&tab_id=' . (int)$row['tab_id'])
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	private function breadcrumbs(): array {
		$token = 'user_token=' . $this->session->data['user_token'];

		return [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];
	}

	public function saveSettings(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_producttabs', ['module_producttabs_status' => (int)!empty($this->request->post['module_producttabs_status'])]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function form(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');

		$token = 'user_token=' . $this->session->data['user_token'];
		$tab_id = (int)($this->request->get['tab_id'] ?? 0);

		$data['breadcrumbs'] = $this->breadcrumbs();
		$data['text_form'] = $tab_id ? $this->language->get('text_edit_tab') : $this->language->get('text_add_tab');
		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link($this->route, $token);
		$data['user_token'] = $this->session->data['user_token'];
		$data['ckeditor'] = $this->language->get('ckeditor');

		$tab = $tab_id ? $this->db->query("SELECT * FROM `" . DB_PREFIX . "producttab` WHERE `tab_id` = '" . $tab_id . "'")->row : [];

		$data['tab_id'] = $tab ? $tab_id : 0;
		$data['current_scope'] = $tab['scope'] ?? 'all';
		$data['sort_order'] = $tab['sort_order'] ?? 10;
		$data['status'] = $tab['status'] ?? 1;

		$data['descriptions'] = [];

		if ($tab) {
			foreach ($this->db->query("SELECT * FROM `" . DB_PREFIX . "producttab_description` WHERE `tab_id` = '" . $tab_id . "'")->rows as $row) {
				$data['descriptions'][$row['language_id']] = ['title' => $row['title'], 'content' => $row['content']];
			}
		}

		$language_id = (int)$this->config->get('config_language_id');

		$data['categories'] = $tab ? $this->db->query("SELECT tc.`category_id`, (SELECT GROUP_CONCAT(cd.`name` ORDER BY cp.`level` SEPARATOR ' &gt; ') FROM `" . DB_PREFIX . "category_path` cp LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.`category_id` = cp.`path_id` AND cd.`language_id` = '" . $language_id . "') WHERE cp.`category_id` = tc.`category_id`) AS name FROM `" . DB_PREFIX . "producttab_to_category` tc WHERE tc.`tab_id` = '" . $tab_id . "'")->rows : [];

		$data['products'] = $tab ? $this->db->query("SELECT tp.`product_id`, pd.`name` FROM `" . DB_PREFIX . "producttab_to_product` tp LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = tp.`product_id` AND pd.`language_id` = '" . $language_id . "') WHERE tp.`tab_id` = '" . $tab_id . "' ORDER BY pd.`name`")->rows : [];

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/producttabs/module/producttabs_form', $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		foreach ((array)($post['description'] ?? []) as $language_id => $description) {
			if (!oc_validate_length((string)($description['title'] ?? ''), 1, 64)) {
				$json['error']['title-' . (int)$language_id] = $this->language->get('error_title');
			}
		}

		$scope = in_array($post['scope'] ?? '', ['all', 'category', 'product']) ? $post['scope'] : 'all';
		$categories = array_values(array_unique(array_filter(array_map('intval', (array)($post['category'] ?? [])))));
		$products = array_values(array_unique(array_filter(array_map('intval', (array)($post['product'] ?? [])))));

		if ($scope == 'category' && !$categories) {
			$json['error']['scope'] = $this->language->get('error_categories');
		} elseif ($scope == 'product' && !$products) {
			$json['error']['scope'] = $this->language->get('error_products');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$tab_id = (int)($post['tab_id'] ?? 0);
			$set = "`scope` = '" . $this->db->escape($scope) . "', `sort_order` = '" . (int)($post['sort_order'] ?? 0) . "', `status` = '" . (int)!empty($post['status']) . "'";

			if ($tab_id && $this->db->query("SELECT `tab_id` FROM `" . DB_PREFIX . "producttab` WHERE `tab_id` = '" . $tab_id . "'")->num_rows) {
				$this->db->query("UPDATE `" . DB_PREFIX . "producttab` SET " . $set . " WHERE `tab_id` = '" . $tab_id . "'");
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "producttab` SET " . $set);

				$tab_id = (int)$this->db->getLastId();
			}

			$this->db->query("DELETE FROM `" . DB_PREFIX . "producttab_description` WHERE `tab_id` = '" . $tab_id . "'");

			// Stored HTML-encoded, like OpenCart's own descriptions (the request arrives encoded).
			foreach ((array)($post['description'] ?? []) as $language_id => $description) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "producttab_description` SET `tab_id` = '" . $tab_id . "', `language_id` = '" . (int)$language_id . "', `title` = '" . $this->db->escape((string)$description['title']) . "', `content` = '" . $this->db->escape((string)($description['content'] ?? '')) . "'");
			}

			$this->db->query("DELETE FROM `" . DB_PREFIX . "producttab_to_category` WHERE `tab_id` = '" . $tab_id . "'");
			$this->db->query("DELETE FROM `" . DB_PREFIX . "producttab_to_product` WHERE `tab_id` = '" . $tab_id . "'");

			if ($scope == 'category') {
				foreach ($categories as $category_id) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "producttab_to_category` SET `tab_id` = '" . $tab_id . "', `category_id` = '" . $category_id . "'");
				}
			}

			if ($scope == 'product') {
				foreach ($products as $product_id) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "producttab_to_product` SET `tab_id` = '" . $tab_id . "', `product_id` = '" . $product_id . "'");
				}
			}

			$json['tab_id'] = $tab_id;
			$json['success'] = $this->language->get('text_saved');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			foreach (array_map('intval', (array)($this->request->post['selected'] ?? [])) as $tab_id) {
				foreach (['producttab', 'producttab_description', 'producttab_to_category', 'producttab_to_product'] as $table) {
					$this->db->query("DELETE FROM `" . DB_PREFIX . $table . "` WHERE `tab_id` = '" . $tab_id . "'");
				}
			}

			$json['success'] = $this->language->get('text_deleted');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Deleted products and categories leave no assignments behind.
	 */
	public function productDeleted(string &$route, array &$args, mixed &$output): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "producttab_to_product` WHERE `product_id` = '" . (int)($args[0] ?? 0) . "'");
	}

	public function categoryDeleted(string &$route, array &$args, mixed &$output): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "producttab_to_category` WHERE `category_id` = '" . (int)($args[0] ?? 0) . "'");
	}

	private function events(): array {
		return [
			'producttabs_tabs'     => ['catalog/view/product/product/after', 'tabs'],
			'producttabs_product'  => ['admin/model/catalog/product.deleteProduct/after', 'productDeleted'],
			'producttabs_category' => ['admin/model/catalog/category.deleteCategory/after', 'categoryDeleted']
		];
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "producttab` (`tab_id` INT(11) NOT NULL AUTO_INCREMENT, `scope` VARCHAR(16) NOT NULL DEFAULT 'all', `sort_order` INT(3) NOT NULL DEFAULT 0, `status` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`tab_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "producttab_description` (`tab_id` INT(11) NOT NULL, `language_id` INT(11) NOT NULL, `title` VARCHAR(64) NOT NULL, `content` MEDIUMTEXT NOT NULL, PRIMARY KEY (`tab_id`, `language_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "producttab_to_category` (`tab_id` INT(11) NOT NULL, `category_id` INT(11) NOT NULL, PRIMARY KEY (`tab_id`, `category_id`), KEY `category_id` (`category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "producttab_to_product` (`tab_id` INT(11) NOT NULL, `product_id` INT(11) NOT NULL, PRIMARY KEY (`tab_id`, `product_id`), KEY `product_id` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as $code => [$trigger, $method]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent(['code' => $code, 'description' => 'Product Tabs', 'trigger' => $trigger, 'action' => $this->route . '.' . $method, 'status' => 1, 'sort_order' => 0]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_producttabs', ['module_producttabs_status' => 0]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (array_keys($this->events()) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		// Tabs you wrote stay in the database and come back if you reinstall.
	}
}
