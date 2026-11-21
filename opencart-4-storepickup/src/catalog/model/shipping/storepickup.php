<?php
namespace Opencart\Catalog\Model\Extension\Storepickup\Shipping;
/**
 * Store Pickup: one shipping option per pickup location.
 */
class Storepickup extends \Opencart\System\Engine\Model {
	public function getQuote(array $address): array {
		$this->load->language('extension/storepickup/shipping/storepickup');
		$this->load->model('localisation/geo_zone');

		$quote_data = [];

		foreach (array_values((array)$this->config->get('shipping_storepickup_locations')) as $key => $location) {
			if (empty($location['status'])) {
				continue;
			}

			// A location can be limited to customers in one geo zone, for example its own city or region.
			if (!empty($location['geo_zone_id']) && !$this->model_localisation_geo_zone->getGeoZone((int)$location['geo_zone_id'], (int)($address['country_id'] ?? 0), (int)($address['zone_id'] ?? 0))) {
				continue;
			}

			$cost = (float)$location['cost'];
			$tax_class_id = (int)$this->config->get('shipping_storepickup_tax_class_id');

			// Settings are stored HTML-encoded. The name is plain text: it's saved on the order and used in
			// emails, invoices and payment gateways, so it holds only the location and its address.
			$name = html_entity_decode($location['name'], ENT_QUOTES, 'UTF-8');
			$street = html_entity_decode($location['address'], ENT_QUOTES, 'UTF-8');

			$quote_data['location' . $key] = [
				'code'         => 'storepickup.location' . $key,
				'name'         => sprintf($this->language->get('sp_option'), $name, $street),
				'cost'         => $cost,
				'tax_class_id' => $tax_class_id,
				'text'         => $cost > 0 ? $this->currency->format($this->tax->calculate($cost, $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency']) : $this->language->get('sp_free'),
				// Kept with the order (shipping_method is stored as JSON), for the email and the order page.
				'storepickup'  => [
					'location' => $name,
					'address'  => $street,
					'hours'    => html_entity_decode((string)$location['hours'], ENT_QUOTES, 'UTF-8'),
					'ready'    => html_entity_decode((string)$location['ready'], ENT_QUOTES, 'UTF-8')
				]
			];
		}

		if (!$quote_data) {
			return [];
		}

		return [
			'code'       => 'storepickup',
			'name'       => html_entity_decode((string)$this->config->get('shipping_storepickup_title'), ENT_QUOTES, 'UTF-8') ?: $this->language->get('heading_title'),
			'quote'      => $quote_data,
			'sort_order' => (int)$this->config->get('shipping_storepickup_sort_order'),
			'error'      => false
		];
	}
}
