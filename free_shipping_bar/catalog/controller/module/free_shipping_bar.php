<?php
namespace Opencart\Catalog\Controller\Extension\FreeShippingBar\Module;
/**
 * Class FreeShippingBar
 *
 * Free Shipping Progress Bar for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/view/checkout/cart_list/after -> cartList (bar and suggested products above the cart table)
 *   catalog/view/checkout/cart/after      -> cart (reloads the cart list when a suggested product is added)
 *   catalog/view/common/cart/after        -> miniCart (bar at the top of the header mini-cart)
 *
 * The cart list and mini-cart are re-rendered by OpenCart's own AJAX calls after every change,
 * so the bar is always calculated from the current cart.
 *
 * @package Opencart\Catalog\Controller\Extension\FreeShippingBar\Module
 */
class FreeShippingBar extends \Opencart\System\Engine\Controller {
	/**
	 * Cart List
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function cartList(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_free_shipping_bar_cart') || !$this->cart->hasProducts()) {
			return;
		}

		try {
			$bar = $this->getBar(true);

			if ($bar === '') {
				return;
			}

			$position = strpos($output, '<div id="output-cart"');

			if ($position !== false) {
				$output = substr_replace($output, $bar, $position, 0);
			} else {
				$output = $bar . $output;
			}
		} catch (\Throwable $e) {
			$this->log->write('Free Shipping Progress Bar: ' . $e->getMessage());
		}
	}

	/**
	 * Cart
	 *
	 * Suggested products use the theme's product card, whose Add to Cart only refreshes the mini-cart.
	 * On the cart page, reload the cart list too, so the new product and the updated bar appear.
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function cart(string &$route, array &$data, string &$output): void {
		if (!$this->isActive() || !$this->config->get('module_free_shipping_bar_cart') || !(int)$this->config->get('module_free_shipping_bar_suggest')) {
			return;
		}

		$list = $this->url->link('checkout/cart.list', 'language=' . $this->config->get('config_language'));

		$script = '<script>$(document).ajaxSuccess(function(event, xhr, settings) { var json = xhr.responseJSON; if (settings.url && settings.url.indexOf(\'cart.add\') !== -1 && json && json.success && !json.redirect) { $(\'#shopping-cart\').load(' . json_encode(html_entity_decode($list, ENT_QUOTES, 'UTF-8'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . '); } });</script>';

		$position = strpos($output, '<div id="shopping-cart"');

		if ($position !== false) {
			$output = substr_replace($output, $script, $position, 0);
		}
	}

	/**
	 * Mini Cart
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function miniCart(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_free_shipping_bar_mini_cart') || !$this->cart->hasProducts()) {
			return;
		}

		try {
			$bar = $this->getBar(false);

			if ($bar === '') {
				return;
			}

			// Insert as the first item of the dropdown list. Themes that change the markup simply don't get the bar.
			if (preg_match('/<ul[^>]*class="[^"]*dropdown-menu[^"]*"[^>]*>/', $output, $match, PREG_OFFSET_CAPTURE)) {
				$position = $match[0][1] + strlen($match[0][0]);

				$output = substr_replace($output, '<li>' . $bar . '</li>', $position, 0);
			}
		} catch (\Throwable $e) {
			$this->log->write('Free Shipping Progress Bar: ' . $e->getMessage());
		}
	}

	/**
	 * Get Bar
	 *
	 * @param bool $suggest
	 *
	 * @return string
	 */
	private function getBar(bool $suggest): string {
		if (!$this->isActive()) {
			return '';
		}

		$threshold = $this->getThreshold();

		if ($threshold <= 0) {
			return '';
		}

		$this->load->language('extension/free_shipping_bar/module/free_shipping_bar');

		$currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));

		// Same comparison as the Free Shipping method: the sub-total before tax, in the default currency.
		$sub_total = (float)$this->cart->getSubTotal();
		$remaining = $threshold - $sub_total;

		$data['percent'] = $remaining > 0 ? (int)floor($sub_total / $threshold * 100) : 100;

		if ($remaining > 0) {
			$data['text'] = sprintf($this->language->get('text_remaining'), $this->currency->format($remaining, $currency));
		} else {
			$data['text'] = $this->language->get('text_reached');
		}

		$data['reached'] = $remaining <= 0;
		$data['compact'] = !$suggest;

		$data['products'] = [];

		if ($suggest && $remaining > 0) {
			foreach ($this->getSuggestions($remaining) as $product) {
				$data['products'][] = $this->load->controller('product/thumb', $product);
			}
		}

		return $this->load->view('extension/free_shipping_bar/module/free_shipping_bar', $data);
	}

	/**
	 * Get Threshold
	 *
	 * Returns 0 when the bar should not be shown.
	 *
	 * @return float
	 */
	private function getThreshold(): float {
		if ($this->config->get('module_free_shipping_bar_source') == 'custom') {
			return (float)$this->config->get('module_free_shipping_bar_total');
		}

		if (!$this->config->get('shipping_free_status')) {
			return 0.0;
		}

		$geo_zone_id = (int)$this->config->get('shipping_free_geo_zone_id');

		// Free Shipping limited to a geo zone: hide the bar once we know the customer is outside it.
		if ($geo_zone_id && !empty($this->session->data['shipping_address']['country_id'])) {
			$this->load->model('localisation/geo_zone');

			$address = $this->session->data['shipping_address'];

			if (!$this->model_localisation_geo_zone->getGeoZone($geo_zone_id, (int)$address['country_id'], (int)($address['zone_id'] ?? 0))) {
				return 0.0;
			}
		}

		return (float)$this->config->get('shipping_free_total');
	}

	/**
	 * Get Suggestions
	 *
	 * In-stock products not already in the cart, whose price (before tax, after specials) closes the gap. Cheapest first.
	 *
	 * @param float $remaining
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getSuggestions(float $remaining): array {
		$limit = (int)$this->config->get('module_free_shipping_bar_suggest');

		if ($limit <= 0) {
			return [];
		}

		$cart_ids = array_unique(array_map('intval', array_column($this->cart->getProducts(), 'product_id')));

		$product_ids = [];

		if ($this->config->get('module_free_shipping_bar_category')) {
			$product_ids = $this->getCandidates($remaining, $cart_ids, true);
		}

		if (!$product_ids) {
			$product_ids = $this->getCandidates($remaining, $cart_ids, false);
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));

		$price_status = $this->customer->isLogged() || !$this->config->get('config_customer_price');

		$products = [];

		foreach ($product_ids as $product_id) {
			$result = $this->model_catalog_product->getProduct($product_id);

			if (!$result) {
				continue;
			}

			// The candidate query uses the base price. Specials and customer group discounts can be lower.
			$final = (float)$result['special'] ? (float)$result['special'] : (float)$result['price'];

			if ($final < $remaining) {
				continue;
			}

			$description = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));

			if (oc_strlen($description) > $this->config->get('config_product_description_length')) {
				$description = oc_substr($description, 0, $this->config->get('config_product_description_length')) . '..';
			}

			if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $result['image'];
			} else {
				$image = 'placeholder.png';
			}

			$products[] = [
				'thumb'       => $this->model_tool_image->resize($image, $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height')),
				'description' => $description,
				'price'       => $price_status ? $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $currency) : false,
				'special'     => $price_status && (float)$result['special'] ? $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $currency) : false,
				'tax'         => $this->config->get('config_tax') ? $this->currency->format($final, $currency) : false,
				'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
				'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id'])
			] + $result;

			if (count($products) >= $limit) {
				break;
			}
		}

		return $products;
	}

	/**
	 * Get Candidates
	 *
	 * @param float          $remaining
	 * @param array<int,int> $cart_ids
	 * @param bool           $same_category
	 *
	 * @return array<int, int>
	 */
	private function getCandidates(float $remaining, array $cart_ids, bool $same_category): array {
		$sql = "SELECT DISTINCT `p`.`product_id`, `p`.`price` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`)";

		if ($same_category) {
			if (!$cart_ids) {
				return [];
			}

			$sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p`.`product_id` = `p2c`.`product_id`)";
		}

		$sql .= " WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW() AND `p`.`quantity` > '0' AND `p`.`price` >= '" . (float)$remaining . "' AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "'";

		if ($cart_ids) {
			$sql .= " AND `p`.`product_id` NOT IN (" . implode(',', $cart_ids) . ")";
		}

		if ($same_category) {
			$sql .= " AND `p2c`.`category_id` IN (SELECT `category_id` FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` IN (" . implode(',', $cart_ids) . "))";
		}

		// Fetch a few extra: some may drop out after specials are applied.
		$sql .= " ORDER BY `p`.`price` ASC LIMIT " . ((int)$this->config->get('module_free_shipping_bar_suggest') * 4);

		return array_map('intval', array_column($this->db->query($sql)->rows, 'product_id'));
	}

	/**
	 * Is Active
	 *
	 * @return bool
	 */
	private function isActive(): bool {
		return (bool)$this->config->get('module_free_shipping_bar_status');
	}
}
