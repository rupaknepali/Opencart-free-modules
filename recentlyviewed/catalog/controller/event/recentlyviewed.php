<?php
namespace Opencart\Catalog\Controller\Extension\Recentlyviewed\Event;
/**
 * Recently Viewed Products: records each product page a shopper opens.
 */
class Recentlyviewed extends \Opencart\System\Engine\Controller {
	public const COOKIE = 'oc_recently_viewed';
	public const MAX = 20;

	/**
	 * Trigger: catalog/view/product/product/before
	 * Runs only when a product page is actually rendered, so missing or disabled products are never recorded.
	 */
	public function track(string &$route, array &$args): void {
		if (!$this->config->get('module_recentlyviewed_status') || empty($args['product_id'])) {
			return;
		}

		$product_id = (int)$args['product_id'];

		// Newest first, no duplicates, at most 20 IDs.
		$ids = array_diff($this->read($this->request->cookie[self::COOKIE] ?? ''), [$product_id]);
		array_unshift($ids, $product_id);
		$ids = array_slice($ids, 0, self::MAX);

		// The cookie holds product IDs only. It isn't HttpOnly, so the "Clear" link can delete it in the browser.
		setcookie(self::COOKIE, implode('-', $ids), [
			'expires'  => time() + 60 * 60 * 24 * 30,
			'path'     => '/',
			'secure'   => !empty($this->request->server['HTTPS']),
			'httponly' => false,
			'samesite' => 'Lax'
		]);

		// Make the new list visible to modules rendered later in this request.
		$this->request->cookie[self::COOKIE] = implode('-', $ids);
	}

	/**
	 * Parses the cookie into a list of positive product IDs.
	 */
	private function read(string $value): array {
		$ids = [];

		foreach (explode('-', $value) as $id) {
			if ((int)$id > 0) {
				$ids[] = (int)$id;
			}
		}

		return array_values(array_unique($ids));
	}
}
