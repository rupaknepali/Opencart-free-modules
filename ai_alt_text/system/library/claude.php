<?php
namespace Opencart\System\Library\Extension\AiAltText;
/**
 * Class Claude
 *
 * Minimal client for the Claude Messages API (POST /v1/messages) over cURL.
 * Uses raw HTTP because an OpenCart extension zip can't install Composer packages such as the PHP SDK.
 *
 * @package Opencart\System\Library\Extension\AiAltText
 */
class Claude {
	/**
	 * Models offered in the settings. The first one is the default.
	 *
	 * @var array<int, string>
	 */
	public const MODELS = ['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-haiku-5-5'];

	/**
	 * @var string
	 */
	private string $api_key;

	/**
	 * @var string
	 */
	private string $model;

	/**
	 * Constructor
	 *
	 * @param string $api_key
	 * @param string $model
	 */
	public function __construct(string $api_key, string $model) {
		$this->api_key = $api_key;
		$this->model = in_array($model, self::MODELS) ? $model : self::MODELS[0];
	}

	/**
	 * Message
	 *
	 * @param array<string, mixed> $body request without model
	 * @param int                  $timeout seconds
	 *
	 * @throws \Exception on HTTP or API errors
	 *
	 * @return array<string, mixed> decoded response
	 */
	public function message(array $body, int $timeout = 90): array {
		$body = ['model' => $this->model] + $body;

		$headers = [
			'Content-Type: application/json',
			'x-api-key: ' . $this->api_key,
			'anthropic-version: 2026-10-21'
		];

		// If a safety classifier declines the request, the API retries it on a suitable model in the same call.
		// Claude Haiku 5.5 has no server-side fallback, so it is not sent there.
		if ($this->model != 'claude-haiku-5-5') {
			$body['fallbacks'] = 'default';

			$headers[] = 'anthropic-beta: server-side-fallback-2026-07-01';
		}

		$payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		for ($attempt = 1; ; $attempt++) {
			$curl = curl_init('https://api.anthropic.com/v1/messages');

			curl_setopt_array($curl, [
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $payload,
				CURLOPT_HTTPHEADER     => $headers,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => true,
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT        => $timeout
			]);

			$raw = curl_exec($curl);
			$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
			$header_size = (int)curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$error = curl_error($curl);

			if ($raw === false) {
				throw new \Exception('Could not reach the Claude API: ' . $error);
			}

			$response_headers = substr($raw, 0, $header_size);
			$response = json_decode(substr($raw, $header_size), true);

			// Rate limits (429), overload (529) and server errors are worth one or two retries.
			if (($status == 429 || $status >= 500) && $attempt < 3) {
				$wait = preg_match('/^retry-after:\s*(\d+)/mi', $response_headers, $match) ? (int)$match[1] : $attempt * 2;

				sleep(min(10, max(1, $wait)));

				continue;
			}

			if ($status != 200 || !is_array($response)) {
				$message = is_array($response) && isset($response['error']['message']) ? $response['error']['message'] : 'HTTP ' . $status;

				throw new \Exception('Claude API error: ' . $message);
			}

			return $response;
		}
	}

	/**
	 * Text
	 *
	 * Joins the text blocks of a response. Check stop_reason before calling.
	 *
	 * @param array<string, mixed> $response
	 *
	 * @return string
	 */
	public static function text(array $response): string {
		$text = '';

		foreach ((array)($response['content'] ?? []) as $block) {
			if (($block['type'] ?? '') == 'text') {
				$text .= $block['text'];
			}
		}

		return trim($text);
	}
}
