<?php
namespace Opencart\Admin\Controller\Extension\Multipleflatrates\Shipping;
class Multipleflatrates extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/multipleflatrates/shipping/multipleflatrates');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/multipleflatrates/shipping/multipleflatrates', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/multipleflatrates/shipping/multipleflatrates|save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');

		$this->load->model('localisation/geo_zone');

		$geo_zones = $this->model_localisation_geo_zone->getGeoZones();

		foreach ($geo_zones as $geo_zone) {
			$data['shipping_multipleflatrates_geo_zone_rate'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_multipleflatrates_' . $geo_zone['geo_zone_id'] . '_rate');
			$data['shipping_multipleflatrates_geo_zone_status'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_multipleflatrates_' . $geo_zone['geo_zone_id'] . '_status');
		}

		$data['geo_zones'] = $geo_zones;

		$data['shipping_multipleflatrates_tax_class_id'] = $this->config->get('shipping_multipleflatrates_tax_class_id');

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		$data['shipping_multipleflatrates_status'] = $this->config->get('shipping_multipleflatrates_status');
		$data['shipping_multipleflatrates_sort_order'] = $this->config->get('shipping_multipleflatrates_sort_order');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/multipleflatrates/shipping/multipleflatrates', $data));
	}

	public function save(): void {
		$this->load->language('extension/multipleflatrates/shipping/multipleflatrates');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/multipleflatrates/shipping/multipleflatrates')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('shipping_multipleflatrates', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}