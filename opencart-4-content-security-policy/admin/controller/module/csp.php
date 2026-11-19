<?php
namespace Opencart\Admin\Controller\Extension\Csp\Module;
/**
 * Content Security Policy: enforced and report-only CSP headers for the storefront,
 * a built-in violation log and one-click "allow" for blocked sources.
 */
class Csp extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/csp/module/csp';

	private const DIRECTIVES = ['default-src', 'script-src', 'style-src', 'img-src', 'font-src', 'connect-src', 'media-src', 'object-src', 'frame-src', 'worker-src', 'manifest-src', 'base-uri', 'form-action', 'frame-ancestors'];

	// Keywords that only work in single quotes. "self" without quotes means a host called "self".
	private const KEYWORDS = ['self', 'none', 'unsafe-inline', 'unsafe-eval', 'strict-dynamic', 'unsafe-hashes', 'wasm-unsafe-eval', 'report-sample', 'inline-speculation-rules'];

	/**
	 * Works with OpenCart 4.1's default theme as it ships: everything is served from the store itself.
	 */
	public static function starterPolicy(): array {
		return [
			'default-src'     => "'self'",
			'script-src'      => "'self' 'unsafe-inline'",
			'style-src'       => "'self' 'unsafe-inline'",
			'img-src'         => "'self' data:",
			'font-src'        => "'self' data:",
			'connect-src'     => "'self'",
			'media-src'       => "'self'",
			'object-src'      => "'none'",
			'frame-src'       => "'self'",
			'worker-src'      => "'self'",
			'manifest-src'    => "'self'",
			'base-uri'        => "'self'",
			'form-action'     => "'self'",
			'frame-ancestors' => "'self'"
		];
	}

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
		$data['user_token'] = $this->session->data['user_token'];

		$defaults = [
			'status'          => 0,
			'nonce'           => 0,
			'reporting'       => 'local',
			'report_url'      => '',
			'skip'            => '',
			'enforce_status'  => 0,
			'enforce'         => self::starterPolicy(),
			'enforce_upgrade' => 0,
			'report_status'   => 1,
			'report'          => self::starterPolicy(),
			'report_upgrade'  => 0
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored:
		// defaults on a fresh install, and a field the merchant deliberately left empty stays empty.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_csp');

		foreach ($defaults as $key => $value) {
			$data['module_csp_' . $key] = array_key_exists('module_csp_' . $key, $saved) ? $saved['module_csp_' . $key] : $value;
		}

		$data['directives'] = [];

		foreach (self::DIRECTIVES as $directive) {
			$data['directives'][] = [
				'name' => $directive,
				'help' => $this->language->get('help_' . str_replace('-', '_', $directive))
			];
		}

		$data['starter'] = json_encode(self::starterPolicy());
		$data['strict'] = json_encode(['script-src' => "'self'"] + self::starterPolicy());

		$this->load->model('extension/csp/module/csp');

		$data['report_total'] = $this->model_extension_csp_module_csp->getTotalReports();
		$data['report_list'] = $this->getReportList();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (($post['module_csp_reporting'] ?? '') == 'remote' && !preg_match('#^https?://[^\s;,"]+$#i', html_entity_decode((string)($post['module_csp_report_url'] ?? ''), ENT_QUOTES, 'UTF-8'))) {
			$json['error']['report_url'] = $this->language->get('error_report_url');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$setting = [
				'module_csp_status'          => !empty($post['module_csp_status']) ? 1 : 0,
				'module_csp_nonce'           => !empty($post['module_csp_nonce']) ? 1 : 0,
				'module_csp_reporting'       => in_array($post['module_csp_reporting'] ?? '', ['local', 'remote', 'off']) ? $post['module_csp_reporting'] : 'local',
				'module_csp_report_url'      => html_entity_decode(trim((string)($post['module_csp_report_url'] ?? '')), ENT_QUOTES, 'UTF-8'),
				'module_csp_skip'            => trim((string)($post['module_csp_skip'] ?? '')),
				'module_csp_enforce_status'  => !empty($post['module_csp_enforce_status']) ? 1 : 0,
				'module_csp_enforce'         => $this->cleanPolicy((array)($post['module_csp_enforce'] ?? [])),
				'module_csp_enforce_upgrade' => !empty($post['module_csp_enforce_upgrade']) ? 1 : 0,
				'module_csp_report_status'   => !empty($post['module_csp_report_status']) ? 1 : 0,
				'module_csp_report'          => $this->cleanPolicy((array)($post['module_csp_report'] ?? [])),
				'module_csp_report_upgrade'  => !empty($post['module_csp_report_upgrade']) ? 1 : 0
			];

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_csp', $setting);

			// Send the cleaned values back so the form shows exactly what was saved.
			$json['policy'] = ['enforce' => $setting['module_csp_enforce'], 'report' => $setting['module_csp_report']];
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Violation list, loaded into the Violations tab.
	 */
	public function report(): void {
		$this->load->language($this->route);

		$this->response->setOutput($this->getReportList());
	}

	private function getReportList(): string {
		$this->load->model('extension/csp/module/csp');

		$page = max(1, (int)($this->request->get['page'] ?? 1));
		$limit = 10;

		$data['reports'] = [];

		foreach ($this->model_extension_csp_module_csp->getReports(($page - 1) * $limit, $limit) as $result) {
			$suggestion = $this->suggest($result);

			// Twig autoescape is off in OpenCart, and these values come from a public URL: escape everything.
			$data['reports'][] = [
				'report_id'    => (int)$result['report_id'],
				'policy'       => $result['policy'],
				'directive'    => $this->e($result['directive']),
				'blocked'      => $this->e($result['blocked_uri'] ?: '-'),
				'document'     => $this->e($result['document_uri']),
				'source'       => $this->e($result['source_file'] ? $result['source_file'] . ($result['line'] ? ':' . $result['line'] : '') : ''),
				'sample'       => $this->e($result['sample']),
				'count'        => (int)$result['count'],
				'date'         => date($this->language->get('datetime_format'), strtotime($result['date_modified'])),
				'allow'        => $suggestion ? $this->e(sprintf($this->language->get('text_allow_add'), $suggestion['source'], $suggestion['directive'])) : '',
				'allow_title'  => $suggestion ? $this->e(sprintf($this->language->get('text_allow_title'), $suggestion['source'], $suggestion['directive'])) : '',
				'hint'         => $suggestion ? '' : $this->hint($result)
			];
		}

		$total = $this->model_extension_csp_module_csp->getTotalReports();

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link($this->route . '.report', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($total, $page * $limit), $total, (int)ceil($total / $limit));
		$data['total'] = $total;

		return $this->load->view('extension/csp/module/csp_report', $data + $this->language->all());
	}

	/**
	 * Adds the blocked source of a report to both policies, so switching from report-only to enforced keeps it.
	 */
	public function allow(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		$this->load->model('extension/csp/module/csp');

		$report = $this->model_extension_csp_module_csp->getReport((int)($this->request->get['report_id'] ?? 0));

		if (!$json && !$report) {
			$json['error'] = $this->language->get('error_report');
		}

		$suggestion = (!$json && $report) ? $this->suggest($report) : [];

		if (!$json && !$suggestion) {
			$json['error'] = $this->hint($report);
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$setting = $this->model_setting_setting->getSetting('module_csp');

			foreach (['enforce', 'report'] as $type) {
				$policy = (array)($setting['module_csp_' . $type] ?? self::starterPolicy());

				$setting['module_csp_' . $type] = $this->addSource($policy, $suggestion['directive'], $suggestion['source']);
			}

			$this->model_setting_setting->editSetting('module_csp', $setting);

			$blocked = $report['blocked_uri'];

			if (preg_match('#^((?:https?|wss?)://[^/]+)#i', $blocked, $match)) {
				$blocked = $match[1];
			}

			$this->model_extension_csp_module_csp->deleteReportsByBlocked($suggestion['directive'], $blocked);
			$this->model_extension_csp_module_csp->deleteReport((int)$report['report_id']);

			$json['policy'] = ['enforce' => $setting['module_csp_enforce'], 'report' => $setting['module_csp_report']];
			$json['success'] = sprintf($this->language->get('text_allowed'), $suggestion['source'], $suggestion['directive']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/csp/module/csp');

			if (!empty($this->request->get['all'])) {
				$this->model_extension_csp_module_csp->clearReports();
			} else {
				$this->model_extension_csp_module_csp->deleteReport((int)($this->request->get['report_id'] ?? 0));
			}

			$json['success'] = $this->language->get('text_deleted');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * What to add to the policy for a violation, or [] when it can't be fixed by allowing a source.
	 */
	private function suggest(array $report): array {
		$directive = (string)$report['directive'];
		$blocked = (string)$report['blocked_uri'];

		// script-src-elem / script-src-attr fall back to script-src, which is what this extension manages.
		$directive = preg_replace('/^(script|style)-src-(elem|attr)$/', '$1-src', $directive);

		if (!in_array($directive, self::DIRECTIVES) || in_array($directive, ['frame-ancestors', 'base-uri'])) {
			return [];
		}

		if ($blocked === 'inline') {
			// Inline styles are everywhere in themes and low risk; inline scripts are what CSP is for.
			return $directive == 'style-src' ? ['directive' => $directive, 'source' => "'unsafe-inline'"] : [];
		}

		$keywords = ['eval' => "'unsafe-eval'", 'wasm-eval' => "'wasm-unsafe-eval'", 'data' => 'data:', 'blob' => 'blob:'];

		if (isset($keywords[$blocked])) {
			return ['directive' => $directive, 'source' => $keywords[$blocked]];
		}

		if (preg_match('#^((?:https?|wss?)://[^/]+)#i', $blocked, $match)) {
			$origin = strtolower($match[1]);
			$store = strtolower(rtrim((string)preg_replace('#^((?:https?)://[^/]+).*$#i', '$1', HTTP_CATALOG), '/'));

			return ['directive' => $directive, 'source' => $origin === $store ? "'self'" : $origin];
		}

		return [];
	}

	private function hint(array $report): string {
		if (($report['blocked_uri'] ?? '') === 'inline' && str_starts_with((string)$report['directive'], 'script-src')) {
			return $this->language->get(str_ends_with((string)$report['directive'], '-attr') ? 'hint_inline_handler' : 'hint_inline_script');
		}

		if (($report['directive'] ?? '') === 'frame-ancestors') {
			return $this->language->get('hint_frame_ancestors');
		}

		return $this->language->get('hint_manual');
	}

	private function addSource(array $policy, string $directive, string $source): array {
		$value = trim((string)($policy[$directive] ?? ''));

		// A directive that isn't set falls back to default-src. Start from that, or the new directive would drop 'self'.
		if ($value === '') {
			$value = trim((string)($policy['default-src'] ?? '')) ?: "'self'";
		}

		$tokens = preg_split('/\s+/', $value);

		$tokens = array_values(array_filter($tokens, static fn($token) => strtolower($token) !== "'none'"));

		if (!in_array(strtolower($source), array_map('strtolower', $tokens))) {
			$tokens[] = $source;
		}

		$policy[$directive] = implode(' ', $tokens);

		return $policy;
	}

	private function cleanPolicy(array $policy): array {
		$clean = [];

		foreach (self::DIRECTIVES as $directive) {
			$value = html_entity_decode((string)($policy[$directive] ?? ''), ENT_QUOTES, 'UTF-8');

			// A semicolon or comma would start a new directive or policy; quotes are added back below.
			$value = trim(preg_replace('/\s+/', ' ', str_replace([';', ',', "\r", "\n", "\0"], ' ', $value)));

			$tokens = [];

			foreach ($value === '' ? [] : explode(' ', $value) as $token) {
				$bare = strtolower(trim($token, "'\""));

				if (in_array($bare, self::KEYWORDS) || preg_match('/^(nonce|sha256|sha384|sha512)-/', $bare)) {
					$token = "'" . (preg_match('/^(nonce|sha)/', $bare) ? trim($token, "'\"") : $bare) . "'";
				}

				$tokens[] = $token;
			}

			$clean[$directive] = implode(' ', array_unique($tokens));
		}

		return $clean;
	}

	private function e(string $value): string {
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}

	public function install(): void {
		$this->load->model('extension/csp/module/csp');

		$this->model_extension_csp_module_csp->install();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'csp_view',
			'description' => 'Content Security Policy: CSP headers and script nonces on storefront pages',
			'trigger'     => 'catalog/view/*/before',
			'action'      => 'extension/csp/event/csp.view',
			'status'      => 1,
			'sort_order'  => 999
		]);
	}

	public function uninstall(): void {
		$this->load->model('extension/csp/module/csp');

		$this->model_extension_csp_module_csp->uninstall();

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('csp_view');
	}
}
