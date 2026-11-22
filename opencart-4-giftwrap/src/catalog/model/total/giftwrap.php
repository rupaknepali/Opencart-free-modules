<?php
namespace Opencart\Catalog\Model\Extension\Giftwrap\Total;
/**
 * Gift wrap: a fixed price per order, added to the totals when the customer asks for it.
 */
class Giftwrap extends \Opencart\System\Engine\Model {
	public function getTotal(array &$totals, array &$taxes, float &$total): void {
		if (empty($this->session->data['giftwrap']) || !$this->config->get('total_giftwrap_status') || !$this->cart->hasProducts()) {
			return;
		}

		// Downloads only: there's nothing to wrap.
		if (!$this->cart->hasShipping()) {
			return;
		}

		$this->load->language('extension/giftwrap/checkout/giftwrap', 'giftwrap');

		$price = (float)$this->config->get('total_giftwrap_price');

		$totals[] = [
			'extension'  => 'giftwrap',
			'code'       => 'giftwrap',
			'title'      => $this->language->get('giftwrap_text_total'),
			'value'      => $price,
			'sort_order' => (int)$this->config->get('total_giftwrap_sort_order')
		];

		if ($this->config->get('total_giftwrap_tax_class_id')) {
			foreach ($this->tax->getRates($price, (int)$this->config->get('total_giftwrap_tax_class_id')) as $tax_rate) {
				$taxes[(int)$tax_rate['tax_rate_id']] = ($taxes[(int)$tax_rate['tax_rate_id']] ?? 0) + (float)$tax_rate['amount'];
			}
		}

		$total += $price;
	}
}
