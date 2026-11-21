<?php
namespace Opencart\Catalog\Controller\Extension\Sociallogin\Module;
/**
 * Sign in with Google (OpenID Connect, authorization code flow with PKCE).
 *
 *   start    -> redirects to Google with state, nonce and a PKCE challenge
 *   callback -> Google sends the visitor back here with ?code=&state=
 *               1. a tiny "bounce" page re-opens the callback from your own site, so the
 *                  SameSite=Strict session cookie (OpenCart's default) is sent;
 *               2. state is checked, the code is exchanged server-to-server for an ID token,
 *                  the token's issuer, audience, expiry and nonce are checked;
 *               3. the customer is found (or linked, or created) and logged in.
 */
class Sociallogin extends \Opencart\System\Engine\Controller {
	private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
	private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
	private const ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

	private string $route = 'extension/sociallogin/module/sociallogin';

	public function start(): void {
		if (!$this->config->get('module_sociallogin_status') || !$this->config->get('module_sociallogin_client_id')) {
			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
		}

		$redirect = '';

		if (isset($this->request->get['redirect'])) {
			$candidate = html_entity_decode((string)$this->request->get['redirect'], ENT_QUOTES, 'UTF-8');

			// Only ever send people back to this store.
			if (str_starts_with($candidate, $this->config->get('config_url'))) {
				$redirect = $candidate;
			}
		}

		$verifier = $this->base64url(random_bytes(48));

		$this->session->data['sociallogin'] = [
			'state'    => $this->base64url(random_bytes(24)),
			'nonce'    => $this->base64url(random_bytes(24)),
			'verifier' => $verifier,
			'redirect' => $redirect,
			'time'     => time()
		];

		$query = http_build_query([
			'client_id'             => $this->config->get('module_sociallogin_client_id'),
			'redirect_uri'          => $this->redirectUri(),
			'response_type'         => 'code',
			'scope'                 => 'openid email profile',
			'state'                 => $this->session->data['sociallogin']['state'],
			'nonce'                 => $this->session->data['sociallogin']['nonce'],
			'code_challenge'        => $this->base64url(hash('sha256', $verifier, true)),
			'code_challenge_method' => 'S256',
			'prompt'                => 'select_account'
		], '', '&', PHP_QUERY_RFC3986);

		$this->response->redirect(self::AUTH_URL . '?' . $query);
	}

	public function callback(): void {
		// Step 1: Google's redirect is a cross-site navigation, so a SameSite=Strict session cookie
		// isn't sent with it. Don't let this request replace the visitor's session cookie (and empty
		// their cart): drop the new cookie and re-open this URL from a same-site page.
		if (!isset($this->request->get['bounce'])) {
			header_remove('Set-Cookie');

			$params = [];

			foreach (['code', 'state', 'error'] as $key) {
				if (isset($this->request->get[$key]) && is_string($this->request->get[$key])) {
					$params[$key] = html_entity_decode($this->request->get[$key], ENT_QUOTES, 'UTF-8');
				}
			}

			$params['bounce'] = 1;

			$url = htmlspecialchars($this->redirectUri() . '&' . http_build_query($params, '', '&', PHP_QUERY_RFC3986), ENT_QUOTES, 'UTF-8');

			$this->response->addHeader('Cache-Control: no-store');
			$this->response->addHeader('Referrer-Policy: no-referrer');
			$this->response->setOutput('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="robots" content="noindex"><meta http-equiv="refresh" content="0;url=' . $url . '"><title>…</title></head><body><p><a href="' . $url . '">Continue</a></p></body></html>');

			return;
		}

		$this->load->language($this->route);

		// Step 2: check state (one use only), then exchange the code.
		$saved = $this->session->data['sociallogin'] ?? [];

		unset($this->session->data['sociallogin']);

		$state = (string)($this->request->get['state'] ?? '');

		if (!$saved || $state === '' || !hash_equals($saved['state'], html_entity_decode($state, ENT_QUOTES, 'UTF-8')) || time() - $saved['time'] > 600) {
			$this->fail($this->language->get('sl_error_state'));
		}

		if (isset($this->request->get['error'])) {
			// access_denied: the visitor closed or cancelled Google's screen.
			$this->fail($this->language->get('sl_error_cancelled'));
		}

		$claims = $this->exchange(html_entity_decode((string)($this->request->get['code'] ?? ''), ENT_QUOTES, 'UTF-8'), $saved);

		// Step 3: find, link or create the customer, then log them in.
		$this->load->model($this->route);
		$this->load->model('account/customer');

		$email = oc_strtolower((string)($claims['email'] ?? ''));
		$verified = ($claims['email_verified'] ?? false) === true || ($claims['email_verified'] ?? '') === 'true';

		$customer_info = [];
		$link = $this->model_extension_sociallogin_module_sociallogin->getLink('google', (string)$claims['sub']);

		if ($link) {
			$customer_info = $this->model_account_customer->getCustomer((int)$link['customer_id']);
		}

		if (!$customer_info) {
			if ($email === '' || !$verified || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->fail($this->language->get('sl_error_email'));
			}

			$customer_info = $this->model_account_customer->getCustomerByEmail($email);

			if ($customer_info && !$this->config->get('module_sociallogin_link_existing')) {
				$this->fail(sprintf($this->language->get('sl_error_exists'), htmlspecialchars($email, ENT_QUOTES, 'UTF-8')));
			}

			if (!$customer_info) {
				if (!$this->config->get('module_sociallogin_create')) {
					$this->fail($this->language->get('sl_error_create'));
				}

				$customer_id = $this->model_account_customer->addCustomer([
					'customer_group_id' => (int)$this->config->get('config_customer_group_id'),
					'firstname'         => oc_substr(trim((string)($claims['given_name'] ?? '')) ?: strstr($email, '@', true), 0, 32),
					'lastname'          => oc_substr(trim((string)($claims['family_name'] ?? '')), 0, 32),
					'email'             => $email,
					'telephone'         => '',
					// A random password nobody knows. The customer can set one with "Forgotten password".
					'password'          => bin2hex(random_bytes(24)),
					'custom_field'      => []
				]);

				$customer_info = $this->model_account_customer->getCustomer($customer_id);
			}

			$this->model_extension_sociallogin_module_sociallogin->addLink((int)$customer_info['customer_id'], 'google', (string)$claims['sub'], $email);
		}

		if (!$customer_info['status']) {
			$this->fail($this->language->get('sl_error_approval'));
		}

		$this->model_extension_sociallogin_module_sociallogin->updateLogin('google', (string)$claims['sub']);

		$this->login($customer_info, $saved['redirect']);
	}

	/**
	 * Exchanges the authorization code for tokens and returns the verified ID token claims.
	 *
	 * The ID token comes straight from Google's token endpoint over TLS, authenticated with the
	 * client secret, so (per OpenID Connect Core 3.1.3.7) TLS stands in for checking its signature.
	 * The issuer, audience, expiry and nonce are still checked.
	 */
	private function exchange(string $code, array $saved): array {
		if ($code === '') {
			$this->fail($this->language->get('sl_error_token'));
		}

		$curl = curl_init(self::TOKEN_URL);

		curl_setopt_array($curl, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => http_build_query([
				'code'          => $code,
				'client_id'     => $this->config->get('module_sociallogin_client_id'),
				'client_secret' => html_entity_decode((string)$this->config->get('module_sociallogin_client_secret'), ENT_QUOTES, 'UTF-8'),
				'redirect_uri'  => $this->redirectUri(),
				'grant_type'    => 'authorization_code',
				'code_verifier' => $saved['verifier']
			]),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 15,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_HTTPHEADER     => ['Accept: application/json']
		]);

		$body = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		$curl_error = curl_error($curl);

		curl_close($curl);

		$response = is_string($body) ? json_decode($body, true) : null;

		if ($status != 200 || empty($response['id_token'])) {
			// Log what Google said (never the code or secret) to help with setup mistakes.
			$this->log->write('Sign in with Google: token request failed (HTTP ' . $status . ') ' . ($curl_error ?: (is_array($response) ? ($response['error'] ?? '') . ' ' . ($response['error_description'] ?? '') : '')));

			$this->fail($this->language->get('sl_error_token'));
		}

		$parts = explode('.', $response['id_token']);
		$claims = count($parts) == 3 ? json_decode($this->base64url_decode($parts[1]), true) : null;

		$audience = (array)($claims['aud'] ?? []);

		if (!is_array($claims)
			|| !in_array($claims['iss'] ?? '', self::ISSUERS, true)
			|| !in_array($this->config->get('module_sociallogin_client_id'), $audience, true)
			|| (int)($claims['exp'] ?? 0) < time() - 60
			|| !hash_equals($saved['nonce'], (string)($claims['nonce'] ?? ''))
			|| empty($claims['sub'])) {
			$this->log->write('Sign in with Google: ID token rejected (issuer, audience, expiry or nonce mismatch).');

			$this->fail($this->language->get('sl_error_token'));
		}

		return $claims;
	}

	/**
	 * Logs the customer in the same way account/login does.
	 */
	private function login(array $customer_info, string $redirect): void {
		$this->customer->logout();

		if (!$this->customer->login($customer_info['email'], '', true)) {
			$this->fail($this->language->get('sl_error_approval'));
		}

		$this->session->data['customer'] = [
			'customer_id'       => $customer_info['customer_id'],
			'customer_group_id' => $customer_info['customer_group_id'],
			'firstname'         => $customer_info['firstname'],
			'lastname'          => $customer_info['lastname'],
			'email'             => $customer_info['email'],
			'telephone'         => $customer_info['telephone'],
			'custom_field'      => $customer_info['custom_field']
		];

		unset($this->session->data['guest']);
		unset($this->session->data['order_id']);
		unset($this->session->data['shipping_method']);
		unset($this->session->data['shipping_methods']);
		unset($this->session->data['payment_method']);
		unset($this->session->data['payment_methods']);

		if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
			$this->load->model('account/wishlist');

			foreach ($this->session->data['wishlist'] as $key => $product_id) {
				$this->model_account_wishlist->addWishlist($this->customer->getId(), $product_id);

				unset($this->session->data['wishlist'][$key]);
			}
		}

		$this->model_account_customer->addLogin($this->customer->getId(), oc_get_ip());

		$this->session->data['customer_token'] = oc_token(26);

		if ($redirect && str_starts_with($redirect, $this->config->get('config_url'))) {
			$this->response->redirect($redirect . (str_contains($redirect, '?') ? '&' : '?') . 'customer_token=' . $this->session->data['customer_token']);
		}

		$this->response->redirect($this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token'], true));
	}

	private function fail(string $message): void {
		$this->session->data['sociallogin_error'] = $message;

		$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language'), true));
	}

	private function redirectUri(): string {
		return $this->config->get('config_url') . 'index.php?route=extension/sociallogin/module/sociallogin.callback';
	}

	private function base64url(string $data): string {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	private function base64url_decode(string $data): string {
		return (string)base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
	}
}
