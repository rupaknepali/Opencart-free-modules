<?php
namespace Opencart\Admin\Controller\Extension\Sociallogin\Module;
/**
 * Sign in with Google: one-click customer login and registration with Google (OpenID Connect).
 */
class Sociallogin extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/sociallogin/module/sociallogin';

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

		// The exact URL to paste into Google Cloud Console > Credentials > Authorized redirect URIs.
		$data['redirect_uri'] = HTTP_CATALOG . 'index.php?route=extension/sociallogin/module/sociallogin.callback';
		$data['javascript_origin'] = rtrim(preg_replace('#^(https?://[^/]+).*$#', '$1', HTTP_CATALOG), '/');

		$defaults = [
			'status'        => 0,
			'client_id'     => '',
			'client_secret' => '',
			'login'         => 1,
			'register'      => 1,
			'checkout'      => 1,
			'link_existing' => 1,
			'create'        => 1
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_sociallogin');

		foreach ($defaults as $key => $value) {
			$data['module_sociallogin_' . $key] = array_key_exists('module_sociallogin_' . $key, $saved) ? $saved['module_sociallogin_' . $key] : $value;
		}

		$data['https'] = str_starts_with(HTTP_CATALOG, 'https://');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post['module_sociallogin_client_id'] = trim((string)($post['module_sociallogin_client_id'] ?? ''));
		$post['module_sociallogin_client_secret'] = trim((string)($post['module_sociallogin_client_secret'] ?? ''));

		if (!empty($post['module_sociallogin_status'])) {
			if (!preg_match('/^[0-9]+-[a-z0-9]+\.apps\.googleusercontent\.com$/', $post['module_sociallogin_client_id'])) {
				$json['error']['client-id'] = $this->language->get('error_client_id');
			}

			if ($post['module_sociallogin_client_secret'] === '') {
				$json['error']['client-secret'] = $this->language->get('error_client_secret');
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_sociallogin', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model($this->route);

		$this->model_extension_sociallogin_module_sociallogin->install();

		$this->load->model('setting/event');

		$events = [
			'sociallogin_login'    => ['catalog/view/account/login/after', 'login'],
			'sociallogin_register' => ['catalog/view/account/register/after', 'register'],
			'sociallogin_checkout' => ['catalog/view/checkout/register/after', 'checkout']
		];

		foreach ($events as $code => [$trigger, $method]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Sign in with Google: button on the ' . $method . ' page',
				'trigger'     => $trigger,
				'action'      => 'extension/sociallogin/event/sociallogin.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['sociallogin_login', 'sociallogin_register', 'sociallogin_checkout'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		// The customer_social table is kept so customers can still sign in with Google after a reinstall.
	}
}
