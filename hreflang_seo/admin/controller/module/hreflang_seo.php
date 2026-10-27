<?php
namespace Opencart\Admin\Controller\Extension\HreflangSeo\Module;
/**
 * Class HreflangSeo
 *
 * Hreflang & Canonical SEO for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\HreflangSeo\Module
 */
class HreflangSeo extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/hreflang_seo/module/hreflang_seo');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/hreflang_seo/module/hreflang_seo', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/hreflang_seo/module/hreflang_seo.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$data['module_hreflang_seo_status'] = $this->config->get('module_hreflang_seo_status');
		$data['module_hreflang_seo_canonical'] = $this->config->get('module_hreflang_seo_canonical');
		$data['module_hreflang_seo_noindex'] = $this->config->get('module_hreflang_seo_noindex');
		$data['module_hreflang_seo_x_default'] = $this->config->get('module_hreflang_seo_x_default') ?: $this->config->get('config_language_catalog');

		$codes = (array)$this->config->get('module_hreflang_seo_code');

		$this->load->model('localisation/language');

		$data['languages'] = [];

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$parts = explode('-', strtolower($language['code']), 2);

			$data['languages'][] = [
				'name'     => $language['name'],
				'code'     => $language['code'],
				'status'   => (bool)$language['status'],
				'default'  => isset($parts[1]) ? $parts[0] . '-' . strtoupper($parts[1]) : $parts[0],
				'hreflang' => $codes[$language['code']] ?? ''
			];
		}

		$data['seo_url'] = (bool)$this->config->get('config_seo_url');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/hreflang_seo/module/hreflang_seo', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/hreflang_seo/module/hreflang_seo');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/hreflang_seo/module/hreflang_seo')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$codes = [];

		foreach ((array)($this->request->post['module_hreflang_seo_code'] ?? []) as $code => $value) {
			$value = trim((string)$value);

			// ISO 639-1 language, optionally followed by an ISO 3166-1 alpha-2 region. Google doesn't accept a region on its own.
			if ($value !== '' && !preg_match('/^[a-z]{2,3}(-[A-Za-z]{4})?(-[A-Za-z]{2}|-[0-9]{3})?$/', $value)) {
				$json['error']['code-' . $code] = $this->language->get('error_code');
			}

			$codes[$code] = $value;
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_hreflang_seo', [
				'module_hreflang_seo_status'    => !empty($this->request->post['module_hreflang_seo_status']) ? 1 : 0,
				'module_hreflang_seo_canonical' => !empty($this->request->post['module_hreflang_seo_canonical']) ? 1 : 0,
				'module_hreflang_seo_noindex'   => !empty($this->request->post['module_hreflang_seo_noindex']) ? 1 : 0,
				'module_hreflang_seo_x_default' => (string)($this->request->post['module_hreflang_seo_x_default'] ?? ''),
				'module_hreflang_seo_code'      => $codes
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('hreflang_seo_header');

		$this->model_setting_event->addEvent([
			'code'        => 'hreflang_seo_header',
			'description' => 'Hreflang & Canonical SEO',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => 'extension/hreflang_seo/module/hreflang_seo.header',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_hreflang_seo', [
			'module_hreflang_seo_status'    => 0,
			'module_hreflang_seo_canonical' => 1,
			'module_hreflang_seo_noindex'   => 1,
			'module_hreflang_seo_x_default' => '',
			'module_hreflang_seo_code'      => []
		]);
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('hreflang_seo_header');
	}
}
