<?php
namespace Opencart\Admin\Controller\Extension\Storepickup\Shipping;
/**
 * Store Pickup: click & collect from several locations, each with opening hours and a "ready in" time.
 */
class Storepickup extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/storepickup/shipping/storepickup';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=shipping')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=shipping');

		$defaults = [
			'title'        => $this->language->get('text_default_title'),
			'instructions' => $this->language->get('text_default_instructions'),
			'map'          => 1,
			'tax_class_id' => 0,
			'status'       => 0,
			'sort_order'   => 0,
			'locations'    => [[
				'name'        => $this->config->get('config_name'),
				'address'     => preg_replace('/\s*\R\s*/', ', ', trim(html_entity_decode((string)$this->config->get('config_address'), ENT_QUOTES, 'UTF-8'))),
				'hours'       => (string)$this->config->get('config_open'),
				'ready'       => $this->language->get('text_default_ready'),
				'cost'        => 0,
				'geo_zone_id' => 0,
				'status'      => 1
			]]
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('shipping_storepickup');

		foreach ($defaults as $key => $value) {
			$data['shipping_storepickup_' . $key] = array_key_exists('shipping_storepickup_' . $key, $saved) ? $saved['shipping_storepickup_' . $key] : $value;
		}

		if (!is_array($data['shipping_storepickup_locations'])) {
			$data['shipping_storepickup_locations'] = [];
		}

		$this->load->model('localisation/tax_class');
		$this->load->model('localisation/geo_zone');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (oc_strlen(trim((string)($post['shipping_storepickup_title'] ?? ''))) < 2) {
			$json['error']['title'] = $this->language->get('error_title');
		}

		$locations = [];

		foreach ((array)($post['shipping_storepickup_locations'] ?? []) as $row => $location) {
			$name = trim((string)($location['name'] ?? ''));
			$address = trim((string)($location['address'] ?? ''));

			if ($name === '' && $address === '') {
				continue;
			}

			if ($name === '' || $address === '') {
				$json['error']['location-' . $row] = $this->language->get('error_location');
			}

			$locations[] = [
				'name'        => oc_substr($name, 0, 64),
				'address'     => oc_substr($address, 0, 255),
				'hours'       => oc_substr(trim((string)($location['hours'] ?? '')), 0, 255),
				'ready'       => oc_substr(trim((string)($location['ready'] ?? '')), 0, 64),
				'cost'        => max(0, (float)($location['cost'] ?? 0)),
				'geo_zone_id' => (int)($location['geo_zone_id'] ?? 0),
				'status'      => (int)!empty($location['status'])
			];
		}

		if (!$locations) {
			$json['error']['warning'] = $this->language->get('error_locations');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['shipping_storepickup_locations'] = $locations;

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('shipping_storepickup', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			['storepickup_quote', 'catalog/controller/checkout/shipping_method.quote/after', 'quote', 'opening hours and ready time in the checkout list'],
			['storepickup_mail', 'catalog/view/mail/order_add/after', 'mail', 'pickup details in the order email'],
			['storepickup_order', 'catalog/view/account/order_info/after', 'order', 'pickup details on the customer order page']
		];

		foreach ($events as [$code, $trigger, $method, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Store Pickup: ' . $description,
				'trigger'     => $trigger,
				'action'      => 'extension/storepickup/event/storepickup.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['storepickup_quote', 'storepickup_mail', 'storepickup_order'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
