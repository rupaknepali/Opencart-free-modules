<?php
namespace Opencart\Catalog\Controller\Extension\Csp;
/**
 * Content Security Policy: receives violation reports from browsers.
 *
 * URL: index.php?route=extension/csp/report&policy=enforce|report
 * Accepts the old report-uri format (application/csp-report) and the Reporting API format (application/reports+json).
 */
class Report extends \Opencart\System\Engine\Controller {
	public function index(): void {
		// Browsers ignore the response, so always answer 204. (OpenCart only sends its headers with a body.)
		http_response_code(204);

		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST' || !$this->config->get('module_csp_status') || $this->config->get('module_csp_reporting') !== 'local') {
			return;
		}

		// Reports are small. Anything bigger is not a browser report.
		$body = (string)file_get_contents('php://input', false, null, 0, 65536);
		$json = json_decode($body, true);

		if (!is_array($json)) {
			return;
		}

		$policy = (($this->request->get['policy'] ?? '') === 'enforce') ? 'enforce' : 'report';

		$reports = [];

		if (isset($json['csp-report']) && is_array($json['csp-report'])) {
			$report = $json['csp-report'];

			$reports[] = [
				'directive' => $report['effective-directive'] ?? strtok((string)($report['violated-directive'] ?? ''), ' '),
				'blocked'   => $report['blocked-uri'] ?? '',
				'document'  => $report['document-uri'] ?? '',
				'source'    => $report['source-file'] ?? '',
				'line'      => $report['line-number'] ?? 0,
				'sample'    => $report['script-sample'] ?? ''
			];
		} elseif (array_is_list($json)) {
			foreach (array_slice($json, 0, 50) as $item) {
				if (is_array($item) && ($item['type'] ?? '') === 'csp-violation' && is_array($item['body'] ?? null)) {
					$report = $item['body'];

					$reports[] = [
						'directive' => $report['effectiveDirective'] ?? '',
						'blocked'   => $report['blockedURL'] ?? '',
						'document'  => $report['documentURL'] ?? ($item['url'] ?? ''),
						'source'    => $report['sourceFile'] ?? '',
						'line'      => $report['lineNumber'] ?? 0,
						'sample'    => $report['sample'] ?? ''
					];
				}
			}
		}

		if (!$reports) {
			return;
		}

		// Only keep reports about this store's own pages; anyone can POST to this URL.
		$hosts = [];

		foreach ([$this->config->get('config_url'), defined('HTTP_SERVER') ? HTTP_SERVER : ''] as $url) {
			if ($host = parse_url((string)$url, PHP_URL_HOST)) {
				$hosts[strtolower($host)] = true;
			}
		}

		$this->load->model('extension/csp/module/csp');

		foreach ($reports as $report) {
			$directive = strtolower(trim((string)$report['directive']));
			$blocked = trim((string)$report['blocked']);
			$source = trim((string)$report['source']);

			if (!preg_match('/^[a-z-]{3,40}$/', $directive)) {
				continue;
			}

			$host = strtolower((string)parse_url((string)$report['document'], PHP_URL_HOST));

			if (!$host || !isset($hosts[$host])) {
				continue;
			}

			// Browser extensions inject their own scripts and styles; that noise is not the store's to fix.
			$extension = '/^(chrome|moz|safari|safari-web|ms-browser)-extension:/i';

			if (preg_match($extension, $blocked) || preg_match($extension, $source)) {
				continue;
			}

			// Keep the page path only: query strings can hold tokens or search terms.
			$path = (string)parse_url((string)$report['document'], PHP_URL_PATH);
			$query = (string)parse_url((string)$report['document'], PHP_URL_QUERY);

			parse_str(html_entity_decode($query, ENT_QUOTES, 'UTF-8'), $params);

			$document = ($path ?: '/') . (!empty($params['route']) && is_string($params['route']) ? '?route=' . $params['route'] : '');

			// Cross-origin URLs: the origin is all that's needed to allow them.
			if (preg_match('#^(https?|wss?)://#i', $blocked)) {
				$parts = parse_url($blocked);

				$blocked = strtolower($parts['scheme'] ?? '') . '://' . strtolower($parts['host'] ?? '') . (isset($parts['port']) ? ':' . (int)$parts['port'] : '') . ($parts['path'] ?? '');
			}

			$this->model_extension_csp_module_csp->addReport([
				'policy'    => $policy,
				'directive' => $directive,
				'blocked'   => mb_substr($blocked, 0, 255),
				'document'  => mb_substr($document, 0, 255),
				'source'    => mb_substr(strtok($source, '?') ?: '', 0, 255),
				'line'      => max(0, (int)$report['line']),
				'sample'    => mb_substr(trim((string)$report['sample']), 0, 255)
			]);
		}
	}
}
