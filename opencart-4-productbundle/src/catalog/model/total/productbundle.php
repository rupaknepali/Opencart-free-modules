<?php
namespace Opencart\Catalog\Model\Extension\Productbundle\Total;
/**
 * Product Bundles order total: a discount line for every complete bundle in the cart.
 */
class Productbundle extends \Opencart\System\Engine\Model {
	/**
	 * Enabled bundles with at least two products: [['name', 'discount', 'products' => [ids]], ...]
	 */
	public function getBundles(): array {
		$bundles = [];

		foreach ((array)$this->config->get('total_productbundle_bundles') as $bundle) {
			if (!empty($bundle['status']) && count((array)$bundle['products']) >= 2 && (float)$bundle['discount'] > 0) {
				$bundles[] = [
					'name'     => html_entity_decode((string)$bundle['name'], ENT_QUOTES, 'UTF-8'),
					'discount' => (float)$bundle['discount'],
					'products' => array_map('intval', (array)$bundle['products'])
				];
			}
		}

		return $bundles;
	}

	/**
	 * Quantity and average unit price (ex tax) of each product in the cart. Lines of the same product with
	 * different options are added together.
	 */
	public function getCartProducts(): array {
		$items = [];

		foreach ($this->cart->getProducts() as $product) {
			$id = (int)$product['product_id'];

			if (!isset($items[$id])) {
				$items[$id] = ['quantity' => 0, 'total' => 0.0, 'tax_class_id' => (int)$product['tax_class_id'], 'name' => $product['name']];
			}

			$items[$id]['quantity'] += (int)$product['quantity'];
			$items[$id]['total'] += (float)$product['total'];
		}

		return $items;
	}

	/**
	 * Complete bundles in the cart. Each cart item counts towards one bundle only: bundles are matched in
	 * the order they're listed in the admin, and the items they use aren't available to later bundles.
	 */
	public function getMatches(): array {
		$items = $this->getCartProducts();
		$left = array_map(fn($item) => $item['quantity'], $items);

		$matches = [];

		foreach ($this->getBundles() as $bundle) {
			$sets = PHP_INT_MAX;

			foreach ($bundle['products'] as $product_id) {
				$sets = min($sets, $left[$product_id] ?? 0);
			}

			if ($sets < 1) {
				continue;
			}

			$discounts = [];

			foreach ($bundle['products'] as $product_id) {
				$left[$product_id] -= $sets;

				$unit = $items[$product_id]['total'] / $items[$product_id]['quantity'];

				$discounts[$product_id] = $unit * $sets * $bundle['discount'] / 100;
			}

			$matches[] = $bundle + ['sets' => $sets, 'discounts' => $discounts];
		}

		return $matches;
	}

	public function getTotal(array &$totals, array &$taxes, float &$total): void {
		if (!$this->config->get('total_productbundle_coupon') && !empty($this->session->data['coupon'])) {
			return;
		}

		$this->load->language('extension/productbundle/total/productbundle', 'productbundle');

		$items = $this->getCartProducts();

		foreach ($this->getMatches() as $match) {
			$discount_total = 0.0;

			foreach ($match['discounts'] as $product_id => $discount) {
				// Take the tax on the discounted part off as well, the same way OpenCart's coupon total does.
				if ($items[$product_id]['tax_class_id']) {
					foreach ($this->tax->getRates($discount, $items[$product_id]['tax_class_id']) as $tax_rate) {
						if ($tax_rate['type'] == 'P') {
							$tax_rate_id = (int)$tax_rate['tax_rate_id'];

							if (!isset($taxes[$tax_rate_id])) {
								$taxes[$tax_rate_id] = 0.0;
							}

							$taxes[$tax_rate_id] -= (float)$tax_rate['amount'];
						}
					}
				}

				$discount_total += $discount;
			}

			$discount_total = min($discount_total, $total);

			if ($discount_total <= 0) {
				continue;
			}

			$title = $match['sets'] > 1 ? sprintf($this->language->get('productbundle_pb_line_x'), $match['name'], $match['sets']) : sprintf($this->language->get('productbundle_pb_line'), $match['name']);

			$totals[] = [
				'extension'  => 'productbundle',
				'code'       => 'productbundle',
				'title'      => $title,
				'value'      => -$discount_total,
				'sort_order' => (int)$this->config->get('total_productbundle_sort_order')
			];

			$total -= $discount_total;
		}
	}
}
