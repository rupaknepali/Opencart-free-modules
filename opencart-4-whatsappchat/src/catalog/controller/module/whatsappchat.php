<?php
namespace Opencart\Catalog\Controller\Extension\Whatsappchat\Module;
/**
 * WhatsApp Chat Button: a floating click-to-chat button and an "Ask on WhatsApp" button on product pages.
 * Both are plain wa.me links, so no WhatsApp script loads and nothing is sent until the shopper taps.
 */
class Whatsappchat extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/whatsappchat/module/whatsappchat';

	private function setting(string $key): string {
		return html_entity_decode((string)$this->config->get('module_whatsappchat_' . $key), ENT_QUOTES, 'UTF-8');
	}

	private function enabled(): bool {
		return $this->config->get('module_whatsappchat_status') && preg_match('/^[1-9]\d{7,14}$/', (string)$this->config->get('module_whatsappchat_phone'));
	}

	/**
	 * The product being viewed, or [] when this isn't a product page.
	 */
	private function currentProduct(): array {
		if (($this->request->get['route'] ?? '') != 'product/product' || empty($this->request->get['product_id'])) {
			return [];
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct((int)$this->request->get['product_id']);

		if (!$product) {
			return [];
		}

		return [
			'name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
			'url'  => html_entity_decode($this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$product['product_id']), ENT_QUOTES, 'UTF-8')
		];
	}

	private function link(array $product = []): string {
		if ($product) {
			$message = str_replace(['{product}', '{url}'], [$product['name'], $product['url']], $this->setting('product_message'));
		} else {
			$message = $this->setting('message');
		}

		return 'https://wa.me/' . $this->config->get('module_whatsappchat_phone') . ($message !== '' ? '?text=' . rawurlencode($message) : '');
	}

	/**
	 * Event: catalog/view/common/footer/after. Adds the floating button before </body>.
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->enabled()) {
			return;
		}

		$this->load->language($this->route, 'whatsappchat');

		$data['link'] = $this->link($this->currentProduct());
		$data['label'] = $this->setting('label');
		$data['greeting'] = $this->setting('greeting');
		$data['position'] = $this->config->get('module_whatsappchat_position') == 'left' ? 'left' : 'right';
		$data['devices'] = (string)$this->config->get('module_whatsappchat_devices') ?: 'all';
		$data['color'] = preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->config->get('module_whatsappchat_color')) ? $this->config->get('module_whatsappchat_color') : '#25d366';
		$data['text_close'] = $this->language->get('whatsappchat_text_close');
		$data['text_open_chat'] = $this->language->get('whatsappchat_text_open_chat');

		// Opening hours are checked in the browser with the store's time zone, so they stay right on cached pages.
		$data['hours'] = '';

		if ($this->config->get('module_whatsappchat_hours_status')) {
			$data['hours'] = json_encode([
				'tz'      => date_default_timezone_get(),
				'days'    => array_map('intval', (array)$this->config->get('module_whatsappchat_days')),
				'open'    => (string)$this->config->get('module_whatsappchat_open'),
				'close'   => (string)$this->config->get('module_whatsappchat_close'),
				'offline' => $this->config->get('module_whatsappchat_offline') == 'hide' ? 'hide' : 'note',
				'note'    => $this->setting('offline_text')
			], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		}

		$html = $this->load->view('extension/whatsappchat/module/whatsappchat_button', $data);

		if (($position = strripos($output, '</body>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}

	/**
	 * Event: catalog/view/product/product/after. Adds "Ask on WhatsApp" under the add to cart form.
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->enabled() || !$this->config->get('module_whatsappchat_product_button')) {
			return;
		}

		$product = $this->currentProduct();

		if (!$product) {
			return;
		}

		$html = '<a href="' . htmlspecialchars($this->link($product), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" class="btn btn-outline-success w-100 mt-2 wac-ask" data-wac="product"><i class="fa-brands fa-whatsapp"></i> ' . htmlspecialchars($this->setting('ask'), ENT_QUOTES, 'UTF-8') . '</a>';

		// After the add to cart form.
		if (($button = strpos($output, 'id="button-cart"')) !== false && ($end = strpos($output, '</form>', $button)) !== false) {
			$output = substr_replace($output, $html, $end + 7, 0);
		}
	}
}
