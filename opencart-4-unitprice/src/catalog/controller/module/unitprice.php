<?php
namespace Opencart\Catalog\Controller\Extension\Unitprice\Module;
/**
 * Unit Price: shows "$9.98 / kg" under the price on product pages and product cards.
 */
class Unitprice extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/unitprice/module/unitprice';

	/**
	 * The texts for this product, or [] when it has no content set or prices are hidden.
	 */
	private function build(int $product_id): array {
		if (!$product_id || !$this->config->get('module_unitprice_status')) {
			return [];
		}

		// Prices hidden from guests (System > Settings > Option > Login Display Prices).
		if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
			return [];
		}

		$this->load->model($this->route);

		$content = $this->model_extension_unitprice_module_unitprice->getContent($product_id);

		if (!$content) {
			return [];
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct($product_id);

		if (!$product) {
			return [];
		}

		// The price the shopper pays for one item: special, else customer group discount, else the regular price.
		$price = (float)$product['special'] ?: (float)$product['price'];
		$price = $this->tax->calculate($price, (int)$product['tax_class_id'], $this->config->get('config_tax'));

		$result = $this->model_extension_unitprice_module_unitprice->calculate($price, (float)$content['amount'], $content['unit']);

		if (!$result) {
			return [];
		}

		$text = $this->load->language($this->route, 'unitprice');
		$currency = $this->session->data['currency'];

		$unit_price = str_replace(
			['{price}', '{unit}'],
			[$this->currency->format($result['price'], $currency), $text['unitprice_' . $result['reference']]],
			html_entity_decode((string)$this->config->get('module_unitprice_format'), ENT_QUOTES, 'UTF-8')
		);

		$amount = rtrim(rtrim(number_format((float)$content['amount'], 3, $this->language->get('decimal_point'), ''), '0'), $this->language->get('decimal_point'));

		$content_text = str_replace('{content}', $amount . ' ' . $text['unitprice_unit_' . $content['unit']], html_entity_decode((string)$this->config->get('module_unitprice_content'), ENT_QUOTES, 'UTF-8'));

		return ['unit_price' => $unit_price, 'content' => trim($content_text)];
	}

	/**
	 * Event: catalog/view/product/product/after
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_unitprice_product')) {
			return;
		}

		$data = $this->build((int)($args['product_id'] ?? 0));

		if (!$data) {
			return;
		}

		$html = '<li class="unit-price text-body-secondary">' . htmlspecialchars($data['unit_price'], ENT_QUOTES, 'UTF-8');

		if ($data['content']) {
			$html .= ' <span class="unit-price-content">&middot; ' . htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8') . '</span>';
		}

		$html .= '</li>';

		// Right after the current price (the <h2> line), before the "Ex Tax" line.
		if (preg_match('#<li><h2><span class="price-new">.*?</span></h2></li>#s', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1] + strlen($match[0][0]), 0);
		}
	}

	/**
	 * Event: catalog/view/product/thumb/after
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_unitprice_thumb')) {
			return;
		}

		$data = $this->build((int)($args['product_id'] ?? 0));

		if (!$data) {
			return;
		}

		$html = '<span class="unit-price d-block small text-body-secondary">' . htmlspecialchars($data['unit_price'], ENT_QUOTES, 'UTF-8') . '</span>';

		// At the end of the price block, so it sits under the price (and the Ex Tax line).
		if (($start = strpos($output, '<div class="price">')) !== false && ($end = strpos($output, '</div>', $start)) !== false) {
			$output = substr_replace($output, $html, $end, 0);
		}
	}
}
