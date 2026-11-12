<?php
namespace Opencart\Catalog\Controller\Extension\Autorelated\Event;

class Autorelated extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/model/catalog/product?getRelated/after
	 * (fires as .../product/getRelated/after in 4.0.2.x and .../product.getRelated/after in 4.1.x)
	 *
	 * @param string $route  'catalog/product/getRelated' or 'catalog/product.getRelated'
	 * @param array  $args   [0 => product_id]
	 * @param mixed  $output the related products OpenCart found (empty if none set)
	 */
	public function index(string &$route, array &$args, mixed &$output): void {
		// Off, or the store owner picked related products by hand: leave it alone.
		if (!$this->config->get('module_autorelated_status') || !empty($output)) {
			return;
		}

		$product_id = (int)($args[0] ?? 0);

		if (!$product_id) {
			return;
		}

		$limit = (int)$this->config->get('module_autorelated_limit');

		if ($limit < 1) {
			$limit = 4;
		}

		// The product's deepest category: "Laptops" rather than "Computers".
		$query = $this->db->query("SELECT p2c.`category_id`
			FROM `" . DB_PREFIX . "product_to_category` p2c
			LEFT JOIN `" . DB_PREFIX . "category_path` cp ON (cp.`category_id` = p2c.`category_id`)
			WHERE p2c.`product_id` = '" . $product_id . "'
			GROUP BY p2c.`category_id`
			ORDER BY MAX(cp.`level`) DESC
			LIMIT 1");

		if (!$query->num_rows) {
			return;
		}

		// getProducts() already filters by store, status, date available and customer group.
		$this->load->model('catalog/product');

		$results = $this->model_catalog_product->getProducts([
			'filter_category_id' => (int)$query->row['category_id'],
			'sort'               => 'p.date_added',
			'order'              => 'DESC',
			'start'              => 0,
			'limit'              => $limit + 1 // one extra, in case the current product is in the list
		]);

		$related = [];

		foreach ($results as $result) {
			if ((int)$result['product_id'] === $product_id) {
				continue;
			}

			$related[$result['product_id']] = $result;

			if (count($related) >= $limit) {
				break;
			}
		}

		$output = $related;
	}
}
