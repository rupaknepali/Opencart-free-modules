<?php
namespace Opencart\Catalog\Controller\Extension\Productbadges\Event;
/**
 * Product Badges: Sale, New, Bestseller and Sold out badges on product cards and the product page.
 */
class Productbadges extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/thumb/after
	 * Puts the badges inside the card's image box.
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_productbadges_status') || empty($args['product_id'])) {
			return;
		}

		$html = $this->badges((int)$args['product_id']);

		if ($html === '') {
			return;
		}

		// Default theme: <div class="image"> inside the card.
		if (preg_match('/<div class="image[^"]*">/', $output, $match, PREG_OFFSET_CAPTURE)) {
			$position = $match[0][1] + strlen($match[0][0]);
			$output = substr_replace($output, $html, $position, 0);
		} else {
			// Other themes: wrap the card so the badges have something to sit on.
			$output = '<div class="oc-badges-wrap">' . $html . $output . '</div>';
		}
	}

	/**
	 * Trigger: catalog/view/product/product/after
	 * Puts the badges over the main product image.
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_productbadges_status') || !$this->config->get('module_productbadges_product_page') || empty($args['product_id'])) {
			return;
		}

		$html = $this->badges((int)$args['product_id']);

		if ($html !== '' && preg_match('/<div class="image magnific-popup[^"]*">/', $output, $match, PREG_OFFSET_CAPTURE)) {
			$position = $match[0][1] + strlen($match[0][0]);
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * Trigger: catalog/view/common/header/after
	 * One small stylesheet, before </head>.
	 */
	public function header(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_productbadges_status')) {
			return;
		}

		$css = $this->load->view('extension/productbadges/module/productbadges_style', [
			'side' => $this->config->get('module_productbadges_position') == 'right' ? 'right' : 'left'
		]);

		$position = stripos($output, '</head>');

		if ($position !== false) {
			$output = substr_replace($output, $css, $position, 0);
		}
	}

	/**
	 * The badge HTML for one product, or '' when none apply.
	 */
	private function badges(int $product_id): string {
		$this->load->model('catalog/product');

		// getProduct() applies the shopper's customer group to specials, and skips disabled products.
		$product = $this->model_catalog_product->getProduct($product_id);

		if (!$product) {
			return '';
		}

		$this->load->language('extension/productbadges/module/productbadges');

		$badges = [];

		// Sold out: stock is tracked and none is left.
		if ($this->config->get('module_productbadges_soldout') && $product['subtract'] && $product['quantity'] <= 0) {
			$badges[] = ['class' => 'soldout', 'text' => $this->language->get('pb_soldout')];
		}

		// Sale: an active special price below the normal price.
		if ($this->config->get('module_productbadges_sale') && (float)$product['special'] && (float)$product['price'] > 0 && (float)$product['special'] < (float)$product['price']) {
			if ($this->config->get('module_productbadges_sale_format') == 'word') {
				$text = $this->language->get('pb_sale');
			} else {
				// Rounded down, so the badge never promises more than the real discount. The inner round()
				// stops float noise turning 20% (1 - 80/100 = 0.19999...) into 19%.
				$text = '-' . (int)floor(round((1 - (float)$product['special'] / (float)$product['price']) * 100, 2)) . '%';
			}

			$badges[] = ['class' => 'sale', 'text' => $text];
		}

		// New: added or made available within the last N days.
		if ($this->config->get('module_productbadges_new')) {
			$days = max(1, (int)$this->config->get('module_productbadges_new_days'));
			$since = max(strtotime($product['date_added']), strtotime($product['date_available']));

			if ($since >= strtotime('-' . $days . ' days')) {
				$badges[] = ['class' => 'new', 'text' => $this->language->get('pb_new')];
			}
		}

		// Bestseller: in the store's top sellers.
		if ($this->config->get('module_productbadges_bestseller')) {
			$this->load->model('extension/productbadges/module/productbadges');

			$ids = $this->model_extension_productbadges_module_productbadges->getBestsellerIds(
				max(1, (int)$this->config->get('module_productbadges_bestseller_limit')),
				max(1, (int)$this->config->get('module_productbadges_bestseller_days'))
			);

			if (in_array($product_id, $ids)) {
				$badges[] = ['class' => 'bestseller', 'text' => $this->language->get('pb_bestseller')];
			}
		}

		if (!$badges) {
			return '';
		}

		return $this->load->view('extension/productbadges/module/productbadges', ['badges' => $badges]);
	}
}
