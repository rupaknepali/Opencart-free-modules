<?php
namespace Opencart\Admin\Controller\Extension\Facetfilter\Module;
/**
 * Faceted Filter: settings page, and the events that filter category listings.
 */
class Facetfilter extends \Opencart\System\Engine\Controller {
	private const DEFAULTS = ['status' => 0, 'price' => 1, 'brand' => 1, 'attribute' => 1, 'filters' => 1, 'stock' => 1, 'rating' => 1, 'attributes' => [], 'limit' => 6];

	public function index(): void {
		$this->load->language('extension/facetfilter/module/facetfilter');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/facetfilter/module/facetfilter', $token)];

		$data['save'] = $this->url->link('extension/facetfilter/module/facetfilter.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');
		$data['layout'] = $this->url->link('design/layout', $token);

		// Config::get() returns '' for settings never saved, so read the stored values for fresh-install defaults.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_facetfilter');

		foreach (self::DEFAULTS as $key => $default) {
			$data[$key] = array_key_exists('module_facetfilter_' . $key, $saved) ? $saved['module_facetfilter_' . $key] : $default;
		}

		$data['attributes'] = array_map('intval', (array)$data['attributes']);

		// Every attribute, grouped, for the "Attributes to show" list.
		$query = $this->db->query("SELECT `a`.`attribute_id`, `ad`.`name`, `agd`.`name` AS `group_name` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`ad`.`attribute_id` = `a`.`attribute_id` AND `ad`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "attribute_group` `ag` ON (`ag`.`attribute_group_id` = `a`.`attribute_group_id`) LEFT JOIN `" . DB_PREFIX . "attribute_group_description` `agd` ON (`agd`.`attribute_group_id` = `a`.`attribute_group_id` AND `agd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') ORDER BY `ag`.`sort_order`, `agd`.`name`, `a`.`sort_order`, `ad`.`name`");

		$data['all_attributes'] = $query->rows;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/facetfilter/module/facetfilter', $data));
	}

	public function save(): void {
		$this->load->language('extension/facetfilter/module/facetfilter');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/facetfilter/module/facetfilter')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$limit = (int)($this->request->post['limit'] ?? 0);

		if ($limit < 3 || $limit > 50) {
			$json['error']['limit'] = $this->language->get('error_limit');
		}

		if (!$json) {
			$settings = [];

			foreach (self::DEFAULTS as $key => $default) {
				if ($key == 'attributes') {
					$settings['module_facetfilter_attributes'] = array_values(array_unique(array_map('intval', (array)($this->request->post['attributes'] ?? []))));
				} elseif ($key == 'limit') {
					$settings['module_facetfilter_limit'] = $limit;
				} else {
					$settings['module_facetfilter_' . $key] = (int)!empty($this->request->post[$key]);
				}
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_facetfilter', $settings);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			'facetfilter_products' => ['catalog/model/catalog/product.getProducts/before', 'extension/facetfilter/event/facetfilter.route', 'Faceted Filter: answer filtered category listings'],
			'facetfilter_total'    => ['catalog/model/catalog/product.getTotalProducts/before', 'extension/facetfilter/event/facetfilter.route', 'Faceted Filter: count filtered category listings'],
			'facetfilter_noindex'  => ['catalog/view/common/header/after', 'extension/facetfilter/event/facetfilter.noindex', 'Faceted Filter: noindex filtered category pages']
		];

		foreach ($events as $code => [$trigger, $action, $description]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['facetfilter_products', 'facetfilter_total', 'facetfilter_noindex'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
