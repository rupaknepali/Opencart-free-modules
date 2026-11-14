<?php
namespace Opencart\Admin\Controller\Extension\Turnstile\Captcha;
/**
 * Cloudflare Turnstile captcha: settings page.
 */
class Turnstile extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/turnstile/captcha/turnstile');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $token)
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', $token . '&type=captcha')
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/turnstile/captcha/turnstile', $token)
		];

		$data['save'] = $this->url->link('extension/turnstile/captcha/turnstile.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=captcha');

		$data['captcha_turnstile_site_key'] = $this->config->get('captcha_turnstile_site_key');
		$data['captcha_turnstile_secret_key'] = $this->config->get('captcha_turnstile_secret_key');
		$data['captcha_turnstile_theme'] = $this->config->get('captcha_turnstile_theme') ?: 'auto';
		$data['captcha_turnstile_size'] = $this->config->get('captcha_turnstile_size') ?: 'flexible';
		$data['captcha_turnstile_fail_open'] = $this->config->get('captcha_turnstile_fail_open');
		$data['captcha_turnstile_status'] = $this->config->get('captcha_turnstile_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/turnstile/captcha/turnstile', $data));
	}

	public function save(): void {
		$this->load->language('extension/turnstile/captcha/turnstile');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/turnstile/captcha/turnstile')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		foreach (['site_key', 'secret_key'] as $key) {
			$post['captcha_turnstile_' . $key] = trim((string)($post['captcha_turnstile_' . $key] ?? ''));

			if ($post['captcha_turnstile_' . $key] === '') {
				$json['error'][str_replace('_', '-', $key)] = $this->language->get('error_' . $key);
			}
		}

		if (!in_array($post['captcha_turnstile_theme'] ?? '', ['auto', 'light', 'dark'])) {
			$post['captcha_turnstile_theme'] = 'auto';
		}

		if (!in_array($post['captcha_turnstile_size'] ?? '', ['flexible', 'normal', 'compact'])) {
			$post['captcha_turnstile_size'] = 'flexible';
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('captcha_turnstile', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
