<?php
namespace Opencart\Admin\Controller\Extension\Webocreationtheme\Module;
/**
 * Webocreation Category Showcase: a category heading with its brands, a promo banner and the category's products.
 */
class Webocreationshowcase extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/webocreationtheme/module/webocreationshowcase';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];
		$module_id = (int)($this->request->get['module_id'] ?? 0);

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token . ($module_id ? '&module_id=' . $module_id : ''))]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('setting/module');

		$module = $module_id ? $this->model_setting_module->getModule($module_id) : [];

		$defaults = ['name' => '', 'category_id' => 0, 'title' => [], 'brands' => 1, 'image' => '', 'link' => '', 'limit' => 8, 'sort' => 'p.date_added', 'status' => 1];

		foreach ($defaults as $key => $value) {
			$data[$key] = $module[$key] ?? $value;
		}

		$data['module_id'] = $module_id;

		// Every category with its full path, for the dropdown.
		$data['categories'] = $this->db->query("SELECT cp.`category_id`, GROUP_CONCAT(cd.`name` ORDER BY cp.`level` SEPARATOR ' &gt; ') AS name FROM `" . DB_PREFIX . "category_path` cp LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.`category_id` = cp.`path_id` AND cd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') GROUP BY cp.`category_id` ORDER BY name")->rows;

		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		$data['thumb'] = $data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) ? $this->model_tool_image->resize(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8'), 100, 100) : $data['placeholder'];

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
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post + ['module_id' => 0, 'name' => '', 'category_id' => 0];

		if (!oc_validate_length((string)$post['name'], 3, 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!(int)$post['category_id']) {
			$json['error']['category'] = $this->language->get('error_category');
		}

		$link = trim(html_entity_decode((string)($post['link'] ?? ''), ENT_QUOTES, 'UTF-8'));

		if ($link !== '' && !preg_match('#^(https?://|/|index\.php\?)#', $link)) {
			$json['error']['link'] = $this->language->get('error_link');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$setting = [
				'name'        => (string)$post['name'],
				'category_id' => (int)$post['category_id'],
				'title'       => array_map(fn($title) => oc_substr(trim(html_entity_decode((string)$title, ENT_QUOTES, 'UTF-8')), 0, 64), (array)($post['title'] ?? [])),
				'brands'      => (int)!empty($post['brands']),
				'image'       => (string)($post['image'] ?? ''),
				'link'        => htmlspecialchars($link),
				'limit'       => max(1, min(12, (int)($post['limit'] ?? 8))),
				'sort'        => in_array($post['sort'] ?? '', ['p.date_added', 'p.sort_order', 'p.viewed', 'rating']) ? $post['sort'] : 'p.date_added',
				'status'      => (int)!empty($post['status'])
			];

			$this->load->model('setting/module');

			if (!(int)$post['module_id']) {
				$json['module_id'] = $this->model_setting_module->addModule('webocreationtheme.webocreationshowcase', $setting);
			} else {
				$this->model_setting_module->editModule((int)$post['module_id'], $setting);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
