<?php
namespace Opencart\Catalog\Model\Extension\Facetfilter\Module;
/**
 * Faceted Filter: reads the facets from the category page's own "filter" parameter, works out which products
 * match, and answers getProducts()/getTotalProducts() for filtered category pages.
 *
 * The filter parameter keeps OpenCart's own filter IDs in its first segment and adds facet segments after "_":
 *   filter=12,15_b5.8_p100-500_s1_r4_a2x1844473850.4x99
 *   b = brands (manufacturer IDs), p = price range, s1 = in stock only, r = minimum average rating,
 *   a = attribute values (attribute ID "x" CRC32 of the value text).
 * OpenCart already copies "filter" into every sort, limit and pagination link, so the facets survive all of them.
 */
class Facetfilter extends \Opencart\Catalog\Model\Catalog\Product {
	/**
	 * filter string -> ['core' => [filter IDs], 'brand' => [IDs], 'price' => [min, max] or [], 'stock' => bool, 'rating' => int, 'attr' => [attribute_id => [crc, ...]]]
	 */
	public function parse(string $filter): array {
		$facets = ['core' => [], 'brand' => [], 'price' => [], 'stock' => false, 'rating' => 0, 'attr' => []];

		foreach (explode('_', $filter) as $segment) {
			if (preg_match('/^[0-9,]+$/', $segment)) {
				$facets['core'] = array_values(array_filter(array_map('intval', explode(',', $segment))));
			} elseif (preg_match('/^b([0-9.]+)$/', $segment, $m)) {
				$facets['brand'] = array_values(array_filter(array_map('intval', explode('.', $m[1]))));
			} elseif (preg_match('/^p([0-9]+(?:\.[0-9]+)?)-([0-9]+(?:\.[0-9]+)?)$/', $segment, $m)) {
				$facets['price'] = [(float)min($m[1], $m[2]), (float)max($m[1], $m[2])];
			} elseif ($segment === 's1') {
				$facets['stock'] = true;
			} elseif (preg_match('/^r([1-5])$/', $segment, $m)) {
				$facets['rating'] = (int)$m[1];
			} elseif (preg_match('/^a([0-9x.]+)$/', $segment, $m)) {
				foreach (explode('.', $m[1]) as $pair) {
					if (preg_match('/^([0-9]+)x([0-9]+)$/', $pair, $p)) {
						$facets['attr'][(int)$p[1]][] = (int)$p[2];
					}
				}
			}
		}

		return $facets;
	}

	/**
	 * The products in a category with everything the facets need, cached with OpenCart's product cache
	 * (so editing a product in the admin clears it).
	 */
	public function getCategoryProducts(int $category_id): array {
		$key = 'product.facetfilter.' . md5(implode('.', [$category_id, (int)$this->config->get('config_store_id'), (int)$this->config->get('config_language_id'), (int)$this->config->get('config_customer_group_id')]));

		// OpenCart's cache returns [] for a miss, so an empty result is simply worked out again.
		$products = $this->cache->get($key);

		if ($products) {
			return $products;
		}

		$products = [];

		$query = $this->db->query("SELECT `p`.`product_id`, `p`.`manufacturer_id`, `p`.`quantity`, `p`.`price`, `p`.`tax_class_id`, " . $this->statement['discount'] . ", " . $this->statement['special'] . ", (SELECT AVG(`r`.`rating`) FROM `" . DB_PREFIX . "review` `r` WHERE `r`.`product_id` = `p`.`product_id` AND `r`.`status` = '1') AS `avg_rating` FROM `" . DB_PREFIX . "product_to_category` `p2c` INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p2c`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "') INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `p2c`.`product_id` AND `p`.`status` = '1' AND `p`.`date_available` <= NOW()) WHERE `p2c`.`category_id` = '" . (int)$category_id . "' GROUP BY `p`.`product_id`");

		foreach ($query->rows as $row) {
			$base = $row['special'] !== null ? (float)$row['special'] : ($row['discount'] !== null ? (float)$row['discount'] : (float)$row['price']);

			$products[(int)$row['product_id']] = [
				'manufacturer_id' => (int)$row['manufacturer_id'],
				'quantity'        => (int)$row['quantity'],
				'base_price'      => $base,
				'tax_class_id'    => (int)$row['tax_class_id'],
				'rating'          => (float)$row['avg_rating'],
				'attr'            => [],
				'core'            => []
			];
		}

		if ($products) {
			$ids = implode(',', array_keys($products));

			$query = $this->db->query("SELECT `product_id`, `attribute_id`, CRC32(`text`) AS `crc` FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` IN (" . $ids . ") AND `language_id` = '" . (int)$this->config->get('config_language_id') . "' AND TRIM(`text`) != ''");

			foreach ($query->rows as $row) {
				$products[(int)$row['product_id']]['attr'][(int)$row['attribute_id']] = (int)$row['crc'];
			}

			$query = $this->db->query("SELECT `product_id`, `filter_id` FROM `" . DB_PREFIX . "product_filter` WHERE `product_id` IN (" . $ids . ")");

			foreach ($query->rows as $row) {
				$products[(int)$row['product_id']]['core'][] = (int)$row['filter_id'];
			}
		}

		$this->cache->set($key, $products);

		return $products;
	}

	/**
	 * The price a shopper sees for a product, in their currency (with tax when the store shows prices with tax).
	 */
	public function shownPrice(array $product): float {
		$price = $this->tax->calculate($product['base_price'], $product['tax_class_id'], $this->config->get('config_tax'));

		return round((float)$this->currency->format($price, $this->session->data['currency'], 0, false), 2);
	}

	/**
	 * Does a product pass the facets? $skip leaves one facet group out, for "how many would match if you ticked this" counts.
	 */
	public function matches(array $product, array $facets, array $core_groups, string $skip = ''): bool {
		if ($skip != 'brand' && $facets['brand'] && !in_array($product['manufacturer_id'], $facets['brand'])) {
			return false;
		}

		if ($skip != 'price' && $facets['price']) {
			$price = $this->shownPrice($product);

			if ($price < $facets['price'][0] || $price > $facets['price'][1]) {
				return false;
			}
		}

		if ($skip != 'stock' && $facets['stock'] && $product['quantity'] <= 0) {
			return false;
		}

		if ($skip != 'rating' && $facets['rating'] && $product['rating'] < $facets['rating']) {
			return false;
		}

		// Attributes: any ticked value of an attribute, and every attribute with a ticked value.
		foreach ($facets['attr'] as $attribute_id => $crcs) {
			if ($skip == 'a' . $attribute_id) {
				continue;
			}

			if (!isset($product['attr'][$attribute_id]) || !in_array($product['attr'][$attribute_id], $crcs)) {
				return false;
			}
		}

		// OpenCart filters: any ticked filter of a group, and every group with a ticked filter.
		if ($facets['core']) {
			$ticked = [];

			foreach ($facets['core'] as $filter_id) {
				$ticked[$core_groups[$filter_id] ?? 0][] = $filter_id;
			}

			foreach ($ticked as $group_id => $filter_ids) {
				if ($skip != 'f' . $group_id && !array_intersect($filter_ids, $product['core'])) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * filter_id => filter_group_id for every OpenCart filter (used to OR within a group and AND across groups).
	 */
	public function getFilterGroups(): array {
		$groups = [];

		$query = $this->db->query("SELECT `filter_id`, `filter_group_id` FROM `" . DB_PREFIX . "filter`");

		foreach ($query->rows as $row) {
			$groups[(int)$row['filter_id']] = (int)$row['filter_group_id'];
		}

		return $groups;
	}

	public function getMatchingIds(int $category_id, string $filter): array {
		$facets = $this->parse($filter);
		$core_groups = $this->getFilterGroups();
		$ids = [];

		foreach ($this->getCategoryProducts($category_id) as $product_id => $product) {
			if ($this->matches($product, $facets, $core_groups)) {
				$ids[] = $product_id;
			}
		}

		return $ids;
	}

	/**
	 * Takes the place of catalog/product.getProducts on filtered category pages: same columns, same sorting.
	 */
	public function getProducts(array $data = []): array {
		$ids = $this->getMatchingIds((int)($data['filter_category_id'] ?? 0), (string)($data['filter_filter'] ?? ''));

		if (!$ids) {
			return [];
		}

		$sql = "SELECT DISTINCT *, `pd`.`name`, `p`.`image`, " . $this->statement['discount'] . ", " . $this->statement['special'] . ", " . $this->statement['reward'] . ", " . $this->statement['review'] . " FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `p`.`product_id` IN (" . implode(',', array_map('intval', $ids)) . ") AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' GROUP BY `p`.`product_id`";

		$sort_data = ['pd.name', 'p.model', 'p.quantity', 'p.price', 'rating', 'p.sort_order', 'p.date_added'];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				$sql .= " ORDER BY (CASE WHEN `special` IS NOT NULL THEN `special` WHEN `discount` IS NOT NULL THEN `discount` ELSE `p`.`price` END)";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY `p`.`sort_order`";
		}

		if (isset($data['order']) && $data['order'] == 'DESC') {
			$sql .= " DESC, LCASE(`pd`.`name`) DESC";
		} else {
			$sql .= " ASC, LCASE(`pd`.`name`) ASC";
		}

		$start = max(0, (int)($data['start'] ?? 0));
		$limit = (int)($data['limit'] ?? 20);

		$sql .= " LIMIT " . $start . "," . ($limit < 1 ? 20 : $limit);

		return $this->db->query($sql)->rows;
	}

	public function getTotalProducts(array $data = []): int {
		return count($this->getMatchingIds((int)($data['filter_category_id'] ?? 0), (string)($data['filter_filter'] ?? '')));
	}

	/**
	 * Names for the facet groups.
	 */
	public function getManufacturerNames(array $ids): array {
		if (!$ids) {
			return [];
		}

		$names = [];

		$query = $this->db->query("SELECT `manufacturer_id`, `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` IN (" . implode(',', array_map('intval', $ids)) . ") ORDER BY `sort_order`, `name`");

		foreach ($query->rows as $row) {
			$names[(int)$row['manufacturer_id']] = $row['name'];
		}

		return $names;
	}

	/**
	 * attribute_id => name, in the admin's group and attribute order, plus each value's text by CRC.
	 */
	public function getFacetAttributes(array $product_ids, array $only = []): array {
		if (!$product_ids) {
			return [];
		}

		$sql = "SELECT `pa`.`attribute_id`, `ad`.`name`, `pa`.`text`, CRC32(`pa`.`text`) AS `crc` FROM `" . DB_PREFIX . "product_attribute` `pa` LEFT JOIN `" . DB_PREFIX . "attribute` `a` ON (`a`.`attribute_id` = `pa`.`attribute_id`) LEFT JOIN `" . DB_PREFIX . "attribute_group` `ag` ON (`ag`.`attribute_group_id` = `a`.`attribute_group_id`) LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`ad`.`attribute_id` = `pa`.`attribute_id` AND `ad`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `pa`.`product_id` IN (" . implode(',', array_map('intval', $product_ids)) . ") AND `pa`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND TRIM(`pa`.`text`) != ''";

		if ($only) {
			$sql .= " AND `pa`.`attribute_id` IN (" . implode(',', array_map('intval', $only)) . ")";
		}

		$sql .= " GROUP BY `pa`.`attribute_id`, `pa`.`text` ORDER BY `ag`.`sort_order`, `a`.`sort_order`, `ad`.`name`, `pa`.`text`";

		$attributes = [];

		foreach ($this->db->query($sql)->rows as $row) {
			$attributes[(int)$row['attribute_id']]['name'] = $row['name'];
			$attributes[(int)$row['attribute_id']]['values'][(int)$row['crc']] = $row['text'];
		}

		return $attributes;
	}

	/**
	 * The OpenCart filter groups assigned to the category (Catalog > Categories > Filters), with their filters.
	 */
	public function getCategoryFilters(int $category_id): array {
		$groups = [];

		$query = $this->db->query("SELECT `f`.`filter_id`, `f`.`filter_group_id`, `fd`.`name`, `fgd`.`name` AS `group_name` FROM `" . DB_PREFIX . "category_filter` `cf` LEFT JOIN `" . DB_PREFIX . "filter` `f` ON (`f`.`filter_id` = `cf`.`filter_id`) LEFT JOIN `" . DB_PREFIX . "filter_description` `fd` ON (`fd`.`filter_id` = `f`.`filter_id` AND `fd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "filter_group` `fg` ON (`fg`.`filter_group_id` = `f`.`filter_group_id`) LEFT JOIN `" . DB_PREFIX . "filter_group_description` `fgd` ON (`fgd`.`filter_group_id` = `f`.`filter_group_id` AND `fgd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `cf`.`category_id` = '" . (int)$category_id . "' ORDER BY `fg`.`sort_order`, `f`.`sort_order`, `fd`.`name`");

		foreach ($query->rows as $row) {
			$groups[(int)$row['filter_group_id']]['name'] = $row['group_name'];
			$groups[(int)$row['filter_group_id']]['filters'][(int)$row['filter_id']] = $row['name'];
		}

		return $groups;
	}
}
