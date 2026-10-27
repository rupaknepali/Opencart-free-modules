<?php
namespace Opencart\System\Library\Extension\OrderWebhooks;
/**
 * Class Sender
 *
 * Signs and posts one webhook using the Standard Webhooks headers
 * (webhook-id, webhook-timestamp, webhook-signature). See https://www.standardwebhooks.com/
 *
 * @package Opencart\System\Library\Extension\OrderWebhooks
 */
class Sender {
	/**
	 * Generate Secret
	 *
	 * @return string whsec_ followed by 32 random bytes in base64
	 */
	public static function generateSecret(): string {
		return 'whsec_' . base64_encode(random_bytes(32));
	}

	/**
	 * Sign
	 *
	 * @param string $secret    whsec_ secret
	 * @param string $id        message ID
	 * @param int    $timestamp unix time
	 * @param string $body      raw JSON body
	 *
	 * @return string value for the webhook-signature header
	 */
	public static function sign(string $secret, string $id, int $timestamp, string $body): string {
		$key = base64_decode(preg_replace('/^whsec_/', '', $secret), true);

		if ($key === false) {
			$key = $secret;
		}

		return 'v1,' . base64_encode(hash_hmac('sha256', $id . '.' . $timestamp . '.' . $body, $key, true));
	}

	/**
	 * Send
	 *
	 * @param string $url
	 * @param string $secret
	 * @param string $id
	 * @param string $body
	 * @param int    $timeout seconds
	 *
	 * @return array{code: int, body: string, error: string}
	 */
	public static function send(string $url, string $secret, string $id, string $body, int $timeout = 10): array {
		$timestamp = time();

		$curl = curl_init($url);

		curl_setopt_array($curl, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
			CURLOPT_TIMEOUT        => $timeout,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS | CURLPROTO_HTTP,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'User-Agent: OpenCart-Order-Webhooks/1.0',
				'webhook-id: ' . $id,
				'webhook-timestamp: ' . $timestamp,
				'webhook-signature: ' . self::sign($secret, $id, $timestamp, $body)
			]
		]);

		$response = curl_exec($curl);

		$result = [
			'code'  => (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
			'body'  => is_string($response) ? substr($response, 0, 1000) : '',
			'error' => curl_error($curl)
		];

		return $result;
	}
}
