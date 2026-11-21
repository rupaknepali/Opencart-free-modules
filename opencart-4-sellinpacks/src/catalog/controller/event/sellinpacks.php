<?php
namespace Opencart\Catalog\Controller\Extension\Sellinpacks\Event;
/**
 * Sell in Packs: whole packs on the product page and product cards, a warning in the cart,
 * and checkout closed while any product is in a quantity that isn't allowed.
 */
class Sellinpacks extends \Opencart\System\Engine\Controller {
	/**
	 * The rule for a product, or [] if it has none: ['step' => 6, 'max' => 24, 'start' => 6].
	 */
	private function rule(int $product_id, int $minimum = 1): array {
		if (!$this->config->get('module_sellinpacks_status')) {
			return [];
		}

		$rules = (array)$this->config->get('module_sellinpacks_rule');
		$rule = $rules[$product_id] ?? $rules[(string)$product_id] ?? null;

		if (!$rule || ((int)$rule['step'] <= 1 && (int)$rule['max'] <= 0)) {
			return [];
		}

		$step = max(1, (int)$rule['step']);

		// The smallest quantity that is a whole number of packs and meets the product's own minimum.
		$start = (int)(ceil(max(1, $minimum) / $step) * $step);

		return ['step' => $step, 'max' => max(0, (int)$rule['max']), 'start' => $start];
	}

	/**
	 * Trigger: catalog/view/product/product/after
	 */
	public function product(string &$route, array &$args, string &$output): void {
		$rule = $this->rule((int)($args['product_id'] ?? 0), (int)($args['minimum'] ?? 1));

		if (!$rule) {
			return;
		}

		$this->load->language('extension/sellinpacks/module/sellinpacks');

		// Start the quantity box at one pack, and let the script below keep it to whole packs.
		$output = preg_replace('/(<input type="text" name="quantity" value=")[^"]*(")/', '${1}' . $rule['start'] . '$2 data-sip-step="' . $rule['step'] . '" data-sip-max="' . $rule['max'] . '" data-sip-min="' . $rule['start'] . '"', $output, 1);

		$html = $this->load->view('extension/sellinpacks/module/sellinpacks_product', [
			'note'      => $this->note($rule),
			'step'      => $rule['step']
		]);

		// Under the quantity box, where OpenCart shows its own quantity errors.
		if (($position = strpos($output, '<div id="error-quantity"')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * Trigger: catalog/view/product/thumb/after
	 * The card's Add to Cart button adds one pack instead of the product's minimum.
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		$rule = $this->rule((int)($args['product_id'] ?? 0), (int)($args['minimum'] ?? 1));

		if ($rule) {
			$output = preg_replace('/(<input type="hidden" name="quantity" value=")[^"]*(")/', '${1}' . $rule['start'] . '$2', $output, 1);
		}
	}

	/**
	 * Trigger: catalog/view/checkout/cart_list/after
	 * The cart list is reloaded by AJAX after every quantity change, so the warning stays current.
	 */
	public function cart(string &$route, array &$args, string &$output): void {
		$problems = $this->problems();

		if (!$problems) {
			return;
		}

		$this->load->language('extension/sellinpacks/module/sellinpacks');

		$output = $this->load->view('extension/sellinpacks/module/sellinpacks_cart', ['problems' => $problems]) . $output;

		// Checkout becomes a disabled button until the quantities are fixed.
		$output = preg_replace('/<a href="[^"]*checkout\/checkout[^"]*" class="btn btn-primary">([^<]*)<\/a>/', '<button type="button" class="btn btn-primary" disabled>$1</button>', $output);
	}

	/**
	 * Trigger: catalog/controller/checkout/checkout/before
	 */
	public function checkout(string &$route, array &$args): void {
		if ($this->problems()) {
			$this->response->redirect($this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true));
		}
	}

	/**
	 * Products in the cart whose total quantity isn't allowed. Lines of the same product with different
	 * options count together, because a pack of 6 can be mixed (3 red, 3 blue).
	 */
	private function problems(): array {
		if (!$this->config->get('module_sellinpacks_status') || !$this->cart->hasProducts()) {
			return [];
		}

		$this->load->language('extension/sellinpacks/module/sellinpacks');

		$totals = [];

		foreach ($this->cart->getProducts() as $product) {
			$id = (int)$product['product_id'];

			$totals[$id]['quantity'] = ($totals[$id]['quantity'] ?? 0) + (int)$product['quantity'];
			$totals[$id]['name'] = $product['name'];
			$totals[$id]['minimum'] = (int)($product['minimum'] ?? 1);
		}

		$problems = [];

		foreach ($totals as $product_id => $total) {
			$rule = $this->rule($product_id, $total['minimum']);

			if (!$rule) {
				continue;
			}

			$quantity = $total['quantity'];

			if ($rule['max'] && $quantity > $rule['max']) {
				$problems[] = sprintf($this->language->get('sip_too_many'), $total['name'], $quantity, $rule['max']);
			} elseif ($quantity % $rule['step']) {
				$down = intdiv($quantity, $rule['step']) * $rule['step'];
				$up = $down + $rule['step'];

				$choices = $down >= $rule['start'] ? sprintf($this->language->get('sip_or'), $down, $up) : (string)$up;

				$problems[] = sprintf($this->language->get('sip_not_pack'), $total['name'], $rule['step'], $quantity, $choices);
			}
		}

		return $problems;
	}

	private function note(array $rule): string {
		$note = sprintf($this->language->get('sip_note'), $rule['step']);

		if ($rule['max']) {
			$note .= ' ' . sprintf($this->language->get('sip_note_max'), $rule['max']);
		}

		return $note;
	}
}
