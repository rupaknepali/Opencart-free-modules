<?php
namespace Opencart\Catalog\Controller\Extension\Turnstile\Captcha;
/**
 * Cloudflare Turnstile captcha for OpenCart 4.
 *
 * OpenCart calls index() for the widget HTML on every protected form,
 * and validate() when the form is submitted. validate() returns an error
 * message, or an empty string when the check passes.
 */
class Turnstile extends \Opencart\System\Engine\Controller {
	private const SITEVERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	public function index(): string {
		$this->load->language('extension/turnstile/captcha/turnstile');

		$data['site_key'] = (string)$this->config->get('captcha_turnstile_site_key');
		$data['theme'] = (string)($this->config->get('captcha_turnstile_theme') ?: 'auto');
		$data['size'] = (string)($this->config->get('captcha_turnstile_size') ?: 'flexible');

		if (!$data['site_key']) {
			return '';
		}

		return $this->load->view('extension/turnstile/captcha/turnstile', $data);
	}

	public function validate(): string {
		$this->load->language('extension/turnstile/captcha/turnstile');

		$token = (string)($this->request->post['cf-turnstile-response'] ?? '');

		// No token: a bot posting straight to the form URL, or the widget never ran. No network call needed.
		if ($token === '') {
			return $this->language->get('error_captcha');
		}

		$curl = curl_init(self::SITEVERIFY);

		curl_setopt_array($curl, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => http_build_query([
				'secret'   => $this->config->get('captcha_turnstile_secret_key'),
				'response' => html_entity_decode($token, ENT_QUOTES, 'UTF-8')
			]),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT        => 10
		]);

		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($curl);
		// No curl_close(): it has done nothing since PHP 8.0 and is deprecated in PHP 8.5.

		$result = is_string($response) ? json_decode($response, true) : null;

		// Cloudflare unreachable or no usable answer: not the visitor's fault.
		// Don't test only the HTTP status: a wrong secret key comes back as HTTP 400 with a
		// normal JSON answer, and must fail the check, not count as "unreachable".
		if ($response === false || !is_array($result) || !array_key_exists('success', $result)) {
			$this->log->write('Cloudflare Turnstile: siteverify request failed (' . ($response === false ? $curl_error : 'HTTP ' . $status) . ')');

			return $this->config->get('captcha_turnstile_fail_open') ? '' : $this->language->get('error_unavailable');
		}

		if (!empty($result['success'])) {
			return '';
		}

		$codes = (array)($result['error-codes'] ?? []);

		// A wrong secret key fails every form, so make it easy to find.
		if (array_intersect($codes, ['missing-input-secret', 'invalid-input-secret'])) {
			$this->log->write('Cloudflare Turnstile: the secret key was rejected (' . implode(', ', $codes) . '). Check it in Extensions > Captchas > Cloudflare Turnstile.');
		}

		return $this->language->get('error_captcha');
	}
}
