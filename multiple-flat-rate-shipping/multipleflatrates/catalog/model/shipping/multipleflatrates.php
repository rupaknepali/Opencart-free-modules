<?php
namespace Opencart\Catalog\Model\Extension\MultipleFlatRates\Shipping;
class MultipleFlatRates extends \Opencart\System\Engine\Model {
	public function getQuote(array $address): array {
		$this->load->language('extension/multipleflatrates/shipping/multipleflatrates');

		$quote_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "geo_zone` ORDER BY `name`");

		$multipleflatrates = $this->cart->getSubTotal();

		foreach ($query->rows as $result) {

			if ($this->config->get('shipping_multipleflatrates_' . $result['geo_zone_id'] . '_status')) {
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$result['geo_zone_id'] . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

				if ($query->num_rows) {
					$status = true;
				} else {
					$status = false;
				}
			} else {
				$status = false;
			}

			if ($status) {
				$cost = '';

				$rates = explode(',', $this->config->get('shipping_multipleflatrates_' . $result['geo_zone_id'] . '_rate'));

				foreach ($rates as $rate) {
					$data = explode(':', $rate);

					if ($data[0] >= $multipleflatrates) {

						if (isset($data[1])) {
							$cost = $data[1];
						}
					}
				}

				if ((string)$cost != '') {
					$quote_data['multipleflatrates_' . $result['geo_zone_id']] = [
						'code'         => 'multipleflatrates.multipleflatrates_' . $result['geo_zone_id'],
						'title'        => $result['name'] . '  (' . $this->language->get('text_multipleflatrates').')',
						'cost'         => $cost,
						'tax_class_id' => $this->config->get('shipping_multipleflatrates_tax_class_id'),
						'text'         => $this->currency->format($cost, $this->session->data['currency'])
					];
				}
			}
		}

		$method_data = [];

		if ($quote_data) {
			$method_data = [
				'code'       => 'multipleflatrates',
				'title'      => $this->language->get('heading_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_multipleflatrates_sort_order'),
				'error'      => false
			];
		}

		return $method_data;
	}
}
