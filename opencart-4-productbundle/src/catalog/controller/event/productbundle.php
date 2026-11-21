<?php
namespace Opencart\Catalog\Controller\Extension\Productbundle\Event;
/**
 * Product Bundles: the "Buy these together" offer on product pages and a "complete the bundle" hint in the cart.
 * The discount itself is the order total in catalog/model/total/productbundle.php.
 */
class Productbundle extends \Opencart\System\Engine\Controller {
	private function enabled(): bool {
		if (!$this->config->get('total_productbundle_status')) {
			return false;
		}

		// Stores that hide prices from guests shouldn't show bundle prices either.
		if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
			return false;
		}

		return !(!$this->config->get('total_productbundle_coupon') && !empty($this->session->data['coupon']));
	}

	/**
	 * Trigger: catalog/view/product/product/after
	 */
	public function product(string &$route, array &$args, string &$output): void {
		$product_id = (int)($args['product_id'] ?? 0);

		if (!$product_id || !$this->config->get('total_productbundle_product') || !$this->enabled()) {
			return;
		}

		$this->load->model('extension/productbundle/total/productbundle');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->language('extension/productbundle/total/productbundle');

		$language = $this->config->get('config_language');
		$html = '';

		foreach ($this->model_extension_productbundle_total_productbundle->getBundles() as $key => $bundle) {
			if (!in_array($product_id, $bundle['products'])) {
				continue;
			}

			// The page's own product first, then the others in the order they were added.
			$ids = array_merge([$product_id], array_values(array_diff($bundle['products'], [$product_id])));

			$items = [];
			$full = 0.0;
			$discounted = 0.0;

			foreach ($ids as $id) {
				$product_info = $this->model_catalog_product->getProduct($id);

				// A disabled or out-of-store product makes the whole bundle unavailable.
				if (!$product_info) {
					$items = [];
					break;
				}

				$base = (float)($product_info['special'] ?: $product_info['price']);
				$price = $this->tax->calculate($base, $product_info['tax_class_id'], $this->config->get('config_tax'));

				// The discount comes off the price before tax, as in the cart, so fixed taxes stay whole.
				$discounted += $this->tax->calculate($base * (1 - $bundle['discount'] / 100), $product_info['tax_class_id'], $this->config->get('config_tax'));

				$needs_options = false;

				foreach ($this->model_catalog_product->getOptions($id) as $option) {
					if ($option['required']) {
						$needs_options = true;
					}
				}

				if ($this->model_catalog_product->getSubscriptions($id)) {
					$needs_options = true;
				}

				$full += $price;

				$items[] = [
					'product_id'    => $id,
					'name'          => $product_info['name'],
					'href'          => $this->url->link('product/product', 'language=' . $language . '&product_id=' . $id),
					'thumb'         => $this->model_tool_image->resize(html_entity_decode((string)($product_info['image'] ?: 'placeholder.png'), ENT_QUOTES, 'UTF-8'), 100, 100),
					'price'         => $this->currency->format($price, $this->session->data['currency']),
					'minimum'       => max(1, (int)$product_info['minimum']),
					'needs_options' => $needs_options,
					'is_this'       => $id == $product_id
				];
			}

			if (count($items) < 2) {
				continue;
			}

			$bundle_price = $discounted;

			$html .= $this->load->view('extension/productbundle/total/productbundle_product', [
				'id'           => $key,
				'name'         => $bundle['name'],
				'heading'      => sprintf($this->language->get('pb_heading'), (float)$bundle['discount']),
				'items'        => $items,
				'full'         => $this->currency->format($full, $this->session->data['currency']),
				'price'        => $this->currency->format($bundle_price, $this->session->data['currency']),
				'save'         => sprintf($this->language->get('pb_save'), $this->currency->format($full - $bundle_price, $this->session->data['currency'])),
				'total_text'   => sprintf($this->language->get('pb_total'), count($items)),
				'has_options'  => (bool)array_filter($items, fn($item) => $item['needs_options'] && !$item['is_this']),
				'added'        => sprintf($this->language->get('pb_added'), htmlspecialchars($bundle['name'], ENT_QUOTES, 'UTF-8'), $this->url->link('checkout/cart', 'language=' . $language)),
				'failed'       => $this->language->get('pb_failed'),
				'cart_link'    => $this->url->link('checkout/cart', 'language=' . $language),
				'language'     => $language,
				'pb_this'      => $this->language->get('pb_this'),
				'pb_button'    => $this->language->get('pb_button'),
				'pb_options'   => $this->language->get('pb_options'),
				'pb_options_help' => $this->language->get('pb_options_help'),
				'pb_choose'    => $this->language->get('pb_choose')
			]);
		}

		if ($html && ($position = strpos($output, '<ul class="nav nav-tabs">')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * Trigger: catalog/view/checkout/cart_list/after
	 * "Add the mouse to complete the Home office set and save 10%" for bundles that are partly in the cart.
	 */
	public function cart(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('total_productbundle_cart') || !$this->enabled() || !$this->cart->hasProducts()) {
			return;
		}

		$this->load->model('extension/productbundle/total/productbundle');
		$this->load->model('catalog/product');
		$this->load->language('extension/productbundle/total/productbundle');

		$model = $this->model_extension_productbundle_total_productbundle;
		$in_cart = $model->getCartProducts();
		$matched = array_column($model->getMatches(), 'name');
		$language = $this->config->get('config_language');

		$hints = [];

		foreach ($model->getBundles() as $bundle) {
			if (in_array($bundle['name'], $matched)) {
				continue;
			}

			$have = array_intersect($bundle['products'], array_keys($in_cart));

			if (!$have) {
				continue;
			}

			$missing = [];

			foreach (array_diff($bundle['products'], $have) as $id) {
				$product_info = $this->model_catalog_product->getProduct($id);

				if (!$product_info) {
					$missing = [];
					break;
				}

				$missing[] = '<a href="' . $this->url->link('product/product', 'language=' . $language . '&product_id=' . $id) . '">' . $product_info['name'] . '</a>';
			}

			if ($missing) {
				$hints[] = sprintf($this->language->get('pb_hint'), implode($this->language->get('pb_and'), $missing), htmlspecialchars($bundle['name'], ENT_QUOTES, 'UTF-8'), (float)$bundle['discount']);
			}
		}

		if ($hints) {
			$output = $this->load->view('extension/productbundle/total/productbundle_cart', ['hints' => $hints]) . $output;
		}
	}
}
