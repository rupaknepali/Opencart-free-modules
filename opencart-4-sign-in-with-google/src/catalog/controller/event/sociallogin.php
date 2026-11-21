<?php
namespace Opencart\Catalog\Controller\Extension\Sociallogin\Event;
/**
 * Sign in with Google: adds the button to the login, register and checkout pages.
 */
class Sociallogin extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/account/login/after (also shows sign-in errors)
	 */
	public function login(string &$route, array &$args, string &$output): void {
		$error = $this->session->data['sociallogin_error'] ?? '';

		unset($this->session->data['sociallogin_error']);

		if (!$this->enabled('login') && !$error) {
			return;
		}

		$html = $this->button($args['redirect'] ?? '', $error, $this->enabled('login'));

		// Below the Returning Customer form.
		if (preg_match('#<form id="form-login".*?</form>#s', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1] + strlen($match[0][0]), 0);
		}
	}

	/**
	 * Trigger: catalog/view/account/register/after
	 */
	public function register(string &$route, array &$args, string &$output): void {
		if (!$this->enabled('register')) {
			return;
		}

		if (($position = strpos($output, '<form id="form-register"')) !== false) {
			$output = substr_replace($output, $this->button('', '', true), $position, 0);
		}
	}

	/**
	 * Trigger: catalog/view/checkout/register/after (the account form guests see at checkout)
	 */
	public function checkout(string &$route, array &$args, string &$output): void {
		if (!$this->enabled('checkout') || $this->customer->isLogged()) {
			return;
		}

		$html = $this->button($this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language')), '', true);

		if (preg_match('#<form id="form-register">\s*<p>.*?</p>#s', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1] + strlen($match[0][0]), 0);
		} elseif (($position = strpos($output, '<form id="form-register"')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	private function enabled(string $page): bool {
		return $this->config->get('module_sociallogin_status') && $this->config->get('module_sociallogin_client_id') && $this->config->get('module_sociallogin_' . $page);
	}

	private function button(string $redirect, string $error, bool $show_button): string {
		$this->load->language('extension/sociallogin/module/sociallogin');

		$query = 'language=' . $this->config->get('config_language');

		if ($redirect) {
			$query .= '&redirect=' . urlencode(html_entity_decode($redirect, ENT_QUOTES, 'UTF-8'));
		}

		$data['href'] = $this->url->link('extension/sociallogin/module/sociallogin.start', $query);
		$data['error'] = $error;
		$data['show_button'] = $show_button;

		// Same agreement text as the register form, so new accounts made with Google agree to it too.
		$data['agree'] = '';

		if ($this->config->get('config_account_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation((int)$this->config->get('config_account_id'));

			if ($information_info) {
				$data['agree'] = sprintf($this->language->get('sl_agree'), $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . $this->config->get('config_account_id')), $information_info['title']);
			}
		}

		return $this->load->view('extension/sociallogin/module/sociallogin', $data);
	}
}
