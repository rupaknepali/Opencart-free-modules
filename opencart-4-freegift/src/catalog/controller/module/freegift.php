<?php
namespace Opencart\Catalog\Controller\Extension\Freegift\Module;
/**
 * Free Gift with Purchase: a startup action keeps one free gift line in the cart while the cart
 * qualifies, and removes it when the cart no longer does.
 *
 * The gift line is a normal cart row with two differences:
 *  - its option JSON holds {"freegift":"1"}, so the core Cart library never merges it with a paid line of
 *    the same product (cart rows are matched on product, options and subscription plan);
 *  - its override JSON sets price, total and reward to 0 and adds the gift label to the name.
 */
class Freegift extends \Opencart\System\Engine\Controller {
	private const MARK = '{"freegift":"1"}';

	private function setting(string $key): string {
		return html_entity_decode((string)$this->config->get('module_freegift_' . $key), ENT_QUOTES, 'UTF-8');
	}

	private function where(): string {
		return "`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `customer_id` = '" . (int)$this->customer->getId() . "' AND `session_id` = '" . $this->db->escape($this->session->getId()) . "'";
	}

	/**
	 * Is the offer running for this visitor right now, and which product is the gift?
	 */
	private function offer(): array {
		$product_id = (int)$this->config->get('module_freegift_product_id');

		if (!$this->config->get('module_freegift_status') || !$product_id) {
			return [];
		}

		$today = date('Y-m-d');
		$start = (string)$this->config->get('module_freegift_date_start');
		$end = (string)$this->config->get('module_freegift_date_end');

		if (($start && $today < $start) || ($end && $today > $end)) {
			return [];
		}

		$groups = array_map('intval', (array)$this->config->get('module_freegift_customer_group'));

		if ($groups && !in_array((int)$this->config->get('config_customer_group_id'), $groups, true)) {
			return [];
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct($product_id);

		if (!$product) {
			return [];
		}

		return $product;
	}

	/**
	 * Subtotal of everything except the gift, before tax, in the default currency.
	 */
	private function qualifyingSubtotal(): float {
		$total = 0.0;

		foreach ($this->cart->getProducts() as $product) {
			if (empty($product['freegift'])) {
				$total += (float)$product['total'];
			}
		}

		return $total;
	}

	/**
	 * Startup action: runs on every storefront request, after the cart is loaded.
	 */
	public function index(): void {
		$route = (string)($this->request->get['route'] ?? '');

		// Leave the cart alone while an order is being confirmed or paid.
		if (preg_match('#^(checkout/confirm|checkout/success|api/|extension/[^/]+/payment)#', $route)) {
			return;
		}

		$gift_rows = $this->db->query("SELECT `cart_id`, `product_id`, `quantity` FROM `" . DB_PREFIX . "cart` WHERE " . $this->where() . " AND `option` = '" . $this->db->escape(self::MARK) . "'")->rows;

		$offer = $this->offer();

		if (!$offer) {
			foreach ($gift_rows as $row) {
				$this->cart->remove((int)$row['cart_id']);
			}

			return;
		}

		$qualifies = $this->qualifyingSubtotal() >= (float)$this->config->get('module_freegift_threshold');
		$in_stock = (int)$offer['quantity'] > 0 || !(int)$offer['subtract'] || $this->config->get('config_stock_checkout');

		// Gift lines for another product (the gift changed in the admin) or extra lines go.
		$keep = 0;

		foreach ($gift_rows as $row) {
			if (!$qualifies || (int)$row['product_id'] != (int)$offer['product_id'] || $keep) {
				$this->cart->remove((int)$row['cart_id']);

				continue;
			}

			$keep = (int)$row['cart_id'];

			// Shoppers can change quantities on the cart page; the gift is always one item.
			if ((int)$row['quantity'] != 1) {
				$this->cart->update($keep, 1);
			}
		}

		if (!$qualifies) {
			unset($this->session->data['freegift_added']);

			return;
		}

		// The shopper removed the gift after we added it: respect that until the cart drops below the threshold.
		if (!$keep && !empty($this->session->data['freegift_added'])) {
			return;
		}

		if (!$keep && $in_stock) {
			$override = [
				'price'    => 0,
				'total'    => 0,
				'reward'   => 0,
				'points'   => 0,
				// No tax class: a free item must not pick up fixed per-item taxes (like an eco fee).
				'tax_class_id' => 0,
				'freegift' => 1,
				'name'     => html_entity_decode($offer['name'], ENT_QUOTES, 'UTF-8') . ' (' . $this->setting('label') . ')'
			];

			$this->db->query("INSERT INTO `" . DB_PREFIX . "cart` SET `store_id` = '" . (int)$this->config->get('config_store_id') . "', `customer_id` = '" . (int)$this->customer->getId() . "', `session_id` = '" . $this->db->escape($this->session->getId()) . "', `product_id` = '" . (int)$offer['product_id'] . "', `subscription_plan_id` = '0', `option` = '" . $this->db->escape(self::MARK) . "', `quantity` = '1', `override` = '" . $this->db->escape(json_encode($override)) . "', `date_added` = NOW()");

			// update() also clears the Cart library's cached rows, so the gift shows on this same request.
			$this->cart->update($this->db->getLastId(), 1);

			$this->session->data['freegift_added'] = 1;
			$this->session->data['freegift_notice'] = 1;
		}
	}

	/**
	 * Event: catalog/view/checkout/cart_list/after. "Spend $12 more and get a free tote bag."
	 */
	public function cartList(string &$route, array &$args, string &$output): void {
		$offer = $this->offer();

		if (!$offer || empty($args['products'])) {
			return;
		}

		$name = html_entity_decode($offer['name'], ENT_QUOTES, 'UTF-8');
		$missing = (float)$this->config->get('module_freegift_threshold') - $this->qualifyingSubtotal();
		$html = '';

		if ($missing > 0) {
			$progress = $this->setting('progress');

			if ($progress !== '') {
				$amount = $this->currency->format($missing, $this->session->data['currency']);
				$html = '<div class="alert alert-info freegift-progress"><i class="fa-solid fa-gift"></i> ' . htmlspecialchars(sprintf($progress, $amount, $name), ENT_QUOTES, 'UTF-8') . '</div>';
			}
		} elseif (!empty($this->session->data['freegift_notice'])) {
			unset($this->session->data['freegift_notice']);

			$html = '<div class="alert alert-success freegift-added"><i class="fa-solid fa-gift"></i> ' . htmlspecialchars(sprintf($this->setting('added'), $name), ENT_QUOTES, 'UTF-8') . '</div>';
		}

		$output = $html . $output;
	}
}
