<?php
namespace Opencart\Admin\Controller\Extension\Speculation\Module;
/**
 * Instant Pages: prerender the next page with the Speculation Rules API.
 */
class Speculation extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/speculation/module/speculation';

	private array $defaults = [
		'module_speculation_status'    => 0,
		'module_speculation_mode'      => 'prerender',
		'module_speculation_eagerness' => 'moderate',
		'module_speculation_logged'    => 1,
		'module_speculation_exclude'   => "/*\\?*route=account*\n/*\\?*route=checkout*\n/*\\?*route=affiliate*\n/*\\?*route=product/compare*\n/*\\?*route=tool/*\n/*logout*\n/*\\?*language=*\n/*\\?*currency=*\n/*.pdf\n/*.zip\n/*.jpg\n/*.jpeg\n/*.png\n/*.webp\n/*.gif\n/*.svg"
	];

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

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_speculation');

		foreach ($this->defaults as $key => $value) {
			$data[$key] = array_key_exists($key, $saved) ? $saved[$key] : $value;
		}

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

		$exclude = [];

		foreach (preg_split('/\R/', trim(html_entity_decode((string)($post['module_speculation_exclude'] ?? ''), ENT_QUOTES, 'UTF-8'))) as $line) {
			$line = trim($line);

			if ($line === '') {
				continue;
			}

			// URL patterns start with / or a full origin; anything else would make the whole rule set invalid.
			if (!preg_match('#^(/|https?://)#', $line) || preg_match('/[\s"<>]/', $line)) {
				$json['error']['exclude'] = sprintf($this->language->get('error_pattern'), htmlspecialchars($line));

				break;
			}

			$exclude[] = $line;
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_speculation', [
				'module_speculation_status'    => (int)!empty($post['module_speculation_status']),
				'module_speculation_mode'      => ($post['module_speculation_mode'] ?? '') == 'prefetch' ? 'prefetch' : 'prerender',
				'module_speculation_eagerness' => in_array($post['module_speculation_eagerness'] ?? '', ['conservative', 'moderate', 'eager']) ? $post['module_speculation_eagerness'] : 'moderate',
				'module_speculation_logged'    => (int)!empty($post['module_speculation_logged']),
				'module_speculation_exclude'   => implode("\n", $exclude)
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('speculation_rules');

		$this->model_setting_event->addEvent([
			'code'        => 'speculation_rules',
			'description' => 'Instant Pages: Speculation Rules',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => $this->route . '.rules',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_speculation', $this->defaults);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('speculation_rules');
	}
}
