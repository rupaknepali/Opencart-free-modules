<?php
namespace Opencart\Catalog\Controller\Extension\Preorder\Module;
/**
 * Pre-order: a "Pre-order" button and ship date for out-of-stock products, checkout for those
 * products even when the store blocks out-of-stock checkout, and pre-order lines saved with the order.
 */
class Preorder extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/preorder/module/preorder';

	/**
	 * The extension's texts, loaded with a prefix so they never overwrite the texts of the page they're added to.
	 */
	private function lang(): array {
		$text = [];

		foreach ($this->load->language($this->route, 'preorder') as $key => $value) {
			$text[str_starts_with($key, 'preorder_') ? substr($key, 9) : $key] = $value;
		}

		return $text;
	}

	private function active(array $product_ids): array {
		if (!$this->config->get('module_preorder_status') || !$product_ids) {
			return [];
		}

		$this->load->model($this->route);

		return $this->model_extension_preorder_module_preorder->getActive($product_ids);
	}

	/**
	 * "Ships by Dec 15, 2026", or the fallback text when there is no date or the date has passed.
	 */
	private function shipText(array $preorder): string {
		if ($preorder['ship_date'] && $preorder['ship_date'] >= date('Y-m-d')) {
			$date = date($this->language->get('date_format_short'), strtotime($preorder['ship_date']));

			return sprintf(html_entity_decode((string)$this->config->get('module_preorder_ship_text'), ENT_QUOTES, 'UTF-8'), $date);
		}

		return html_entity_decode((string)$this->config->get('module_preorder_no_date_text'), ENT_QUOTES, 'UTF-8');
	}

	private function buttonText(): string {
		return html_entity_decode((string)$this->config->get('module_preorder_button'), ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Startup action (runs on every storefront request, after the cart is loaded).
	 * OpenCart checks config_stock_checkout in every checkout step. If the store blocks out-of-stock
	 * checkout but every out-of-stock line in this cart is a pre-order product, allow it for this request only.
	 */
	public function startup(): void {
		if (!$this->config->get('module_preorder_status') || $this->config->get('config_stock_checkout') || !$this->cart->hasProducts()) {
			return;
		}

		$out_of_stock = [];

		foreach ($this->cart->getProducts() as $product) {
			if (!$product['stock']) {
				$out_of_stock[] = (int)$product['product_id'];
			}
		}

		if ($out_of_stock && count($this->active($out_of_stock)) == count(array_unique($out_of_stock))) {
			$this->config->set('config_stock_checkout', 1);
			// Also hides the "not in stock" warning and the *** on the cart page for these lines.
			$this->config->set('config_stock_warning', 0);
		}
	}

	/**
	 * Event: catalog/view/product/product/before. Button label and stock line.
	 */
	public function productBefore(string &$route, array &$args): void {
		$product_id = (int)($args['product_id'] ?? 0);
		$active = $this->active([$product_id]);

		if (isset($active[$product_id])) {
			$args['button_cart'] = $this->buttonText();
			$args['stock'] = $this->shipText($active[$product_id]);
		}
	}

	/**
	 * Event: catalog/view/product/product/after. A notice under the button.
	 */
	public function productAfter(string &$route, array &$args, string &$output): void {
		$product_id = (int)($args['product_id'] ?? 0);
		$active = $this->active([$product_id]);

		if (!isset($active[$product_id])) {
			return;
		}

		$data = $this->lang();
		$data['ship_text'] = $this->shipText($active[$product_id]);
		$data['note'] = $active[$product_id]['note'];

		$html = $this->load->view('extension/preorder/module/preorder_product', $data);

		if (preg_match('#<button type="submit" id="button-cart"[^>]*>.*?</button>#s', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1] + strlen($match[0][0]), 0);
		}
	}

	/**
	 * Event: catalog/view/product/thumb/after. "Pre-order" badge and button tooltip on product cards.
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		$product_id = (int)($args['product_id'] ?? 0);
		$active = $this->active([$product_id]);

		if (!isset($active[$product_id])) {
			return;
		}

		$data = $this->lang();
		$data['ship_text'] = $this->shipText($active[$product_id]);
		$data['button'] = $this->buttonText();

		$html = $this->load->view('extension/preorder/module/preorder_thumb', $data);

		// At the end of the description block (the </div> right before the cart form), under price and rating.
		if (($form = strpos($output, '<form')) !== false && ($position = strrpos(substr($output, 0, $form), '</div>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}

		$output = str_replace('title="' . ($args['button_cart'] ?? '') . '"', 'title="' . htmlspecialchars($data['button'], ENT_QUOTES, 'UTF-8') . '"', $output);
	}

	/**
	 * Event: catalog/view/checkout/cart_list/after. Ship date under pre-order lines in the cart.
	 */
	public function cartList(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_preorder_status')) {
			return;
		}

		$lines = [];

		foreach ($this->cart->getProducts() as $product) {
			if (!$product['stock']) {
				$lines[(int)$product['product_id']] = true;
			}
		}

		foreach ($this->active(array_keys($lines)) as $product_id => $preorder) {
			$label = '<br/><span class="badge bg-info text-dark">' . htmlspecialchars($this->buttonText(), ENT_QUOTES, 'UTF-8') . '</span> <small class="text-muted">' . htmlspecialchars($this->shipText($preorder), ENT_QUOTES, 'UTF-8') . '</small>';

			// Product links end in product_id=N (followed by & or "); add the label after the link.
			$output = preg_replace('#(<a href="[^"]*product_id=' . $product_id . '(?:&amp;[^"]*)?">[^<]*</a>)#', '$1' . $label, $output);
		}
	}

	/**
	 * Event: catalog/model/checkout/order.addOrder/after and order.editOrder/after.
	 * Saves which lines were pre-ordered, with the ship date promised at that moment.
	 */
	public function saveOrder(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_preorder_status')) {
			return;
		}

		$order_id = str_ends_with($route, 'addOrder') ? (int)$output : (int)($args[0] ?? 0);
		$data = str_ends_with($route, 'addOrder') ? ($args[0] ?? []) : ($args[1] ?? []);

		$products = (array)($data['products'] ?? []);
		$active = $this->active(array_column($products, 'product_id'));
		$lines = [];

		foreach ($products as $product) {
			if (isset($active[(int)$product['product_id']])) {
				$lines[] = [
					'product_id' => (int)$product['product_id'],
					'name'       => $product['name'],
					'quantity'   => (int)$product['quantity'],
					'ship_date'  => $active[(int)$product['product_id']]['ship_date']
				];
			}
		}

		if ($order_id) {
			$this->load->model($this->route);

			$this->model_extension_preorder_module_preorder->addOrderLines($order_id, $lines);
		}
	}

	/**
	 * Event: catalog/view/mail/order_add/after. A pre-order notice in the order confirmation email.
	 */
	public function mail(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_preorder_status') || empty($args['order_id'])) {
			return;
		}

		$this->load->model($this->route);

		$lines = $this->model_extension_preorder_module_preorder->getOrderLines((int)$args['order_id']);

		if (!$lines) {
			return;
		}

		$data = $this->lang();
		$data['lines'] = [];

		foreach ($lines as $line) {
			$data['lines'][] = [
				'name'      => $line['name'],
				'ship_text' => $this->shipText(['ship_date' => (string)$line['ship_date']])
			];
		}

		$html = $this->load->view('extension/preorder/module/preorder_mail', $data);

		// Before the products table: the last <table> that starts before the first product's name.
		$first = $args['products'][0]['name'] ?? '';
		$name_at = $first !== '' ? strpos($output, $first) : false;
		$table_at = $name_at !== false ? strrpos(substr($output, 0, $name_at), '<table') : false;

		if ($table_at !== false) {
			$output = substr_replace($output, $html, $table_at, 0);
		} elseif (($position = strpos($output, '</body>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}
}
