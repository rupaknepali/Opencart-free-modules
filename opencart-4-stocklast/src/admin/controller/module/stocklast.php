<?php
namespace Opencart\Admin\Controller\Extension\Stocklast\Module;
/**
 * Out of Stock Last: settings page and the startup action registration.
 */
class Stocklast extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/stocklast/module/stocklast';

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
			'status'       => 0,
			'mode'         => 'last',
			'category'     => 1,
			'search'       => 1,
			'manufacturer' => 1,
			'modules'      => 0
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_stocklast');

		foreach ($defaults as $key => $value) {
			$data['module_stocklast_' . $key] = array_key_exists('module_stocklast_' . $key, $saved) ? $saved['module_stocklast_' . $key] : $value;
		}

		$count = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product` WHERE `status` = '1' AND `quantity` <= '0'")->row;

		$data['text_count'] = sprintf($this->language->get('text_count'), (int)$count['total']);

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
			$post['module_stocklast_mode'] = ($post['module_stocklast_mode'] ?? '') == 'hide' ? 'hide' : 'last';

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_stocklast', $post);

			// OpenCart caches product lists for an hour; clear them so the change shows right away.
			$this->cache->delete('product');

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->addStartup([
			'code'        => 'stocklast',
			'description' => 'Out of Stock Last: sort or hide out-of-stock products in product lists',
			'action'      => 'catalog/extension/stocklast/module/stocklast',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('stocklast');

		$this->cache->delete('product');
	}
}
