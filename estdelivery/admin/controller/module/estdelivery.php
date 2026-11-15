<?php
namespace Opencart\Admin\Controller\Extension\Estdelivery\Module;
/**
 * Estimated Delivery Date: settings page, and the event that shows the estimate.
 */
class Estdelivery extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/estdelivery/module/estdelivery');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/estdelivery/module/estdelivery', $token)];

		$data['save'] = $this->url->link('extension/estdelivery/module/estdelivery.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['module_estdelivery_cutoff'] = $this->config->get('module_estdelivery_cutoff') ?: '14:00';
		$data['module_estdelivery_processing'] = $this->config->get('module_estdelivery_processing') ?? 0;
		$data['module_estdelivery_min'] = $this->config->get('module_estdelivery_min') ?: 2;
		$data['module_estdelivery_max'] = $this->config->get('module_estdelivery_max') ?: 4;
		$data['module_estdelivery_days'] = $this->config->get('module_estdelivery_days') ?: [1, 2, 3, 4, 5];
		$data['module_estdelivery_holidays'] = $this->config->get('module_estdelivery_holidays') ?? '';
		$data['module_estdelivery_format'] = $this->config->get('module_estdelivery_format') ?: 'D j M';
		$data['module_estdelivery_stock'] = $this->config->get('module_estdelivery_stock') ?? 0;
		$data['module_estdelivery_status'] = $this->config->get('module_estdelivery_status');

		// ISO-8601 day numbers: 1 = Monday ... 7 = Sunday.
		$data['weekdays'] = [];

		foreach (range(1, 7) as $day) {
			$data['weekdays'][$day] = date('l', strtotime('Monday this week +' . ($day - 1) . ' days'));
		}

		$data['timezone'] = date_default_timezone_get();
		$data['now'] = date('D j M Y, H:i');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/estdelivery/module/estdelivery', $data));
	}

	public function save(): void {
		$this->load->language('extension/estdelivery/module/estdelivery');

		$json = [];
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', 'extension/estdelivery/module/estdelivery')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string)($post['module_estdelivery_cutoff'] ?? ''))) {
			$json['error']['cutoff'] = $this->language->get('error_cutoff');
		}

		$min = (int)($post['module_estdelivery_min'] ?? 0);
		$max = (int)($post['module_estdelivery_max'] ?? 0);

		if ($min < 0 || $max < $min || $max > 60) {
			$json['error']['transit'] = $this->language->get('error_transit');
		}

		if (empty($post['module_estdelivery_days'])) {
			$json['error']['days'] = $this->language->get('error_days');
		}

		foreach (preg_split('/\R/', trim((string)($post['module_estdelivery_holidays'] ?? ''))) as $line) {
			if (trim($line) !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($line))) {
				$json['error']['holidays'] = sprintf($this->language->get('error_holiday'), htmlspecialchars(trim($line)));
				break;
			}
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_estdelivery', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'estdelivery_product',
			'description' => 'Estimated Delivery Date: show the estimate on product pages',
			'trigger'     => 'catalog/view/product/product/after',
			'action'      => 'extension/estdelivery/event/estdelivery',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('estdelivery_product');
	}
}
