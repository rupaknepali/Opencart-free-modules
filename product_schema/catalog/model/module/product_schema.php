<?php
namespace Opencart\Catalog\Model\Extension\ProductSchema\Module;
/**
 * Class ProductSchema
 *
 * Builds schema.org JSON-LD arrays for OpenCart 4.1.0.x pages.
 *
 * @package Opencart\Catalog\Model\Extension\ProductSchema\Module
 */
class ProductSchema extends \Opencart\System\Engine\Model {
	/**
	 * Get Product Schema
	 *
	 * @param int                              $product_id
	 * @param array<int, array<string, mixed>> $breadcrumbs from the product page
	 * @param string                           $manufacturer from the product page
	 *
	 * @return array<string, mixed>
	 */
	public function getProductSchema(int $product_id, array $breadcrumbs = [], string $manufacturer = ''): array {
		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			return [];
		}

		$language = $this->config->get('config_language');

		$url = $this->decode($this->url->link('product/product', 'language=' . $language . '&product_id=' . $product_id));

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'@id'      => $url . '#product',
			'name'     => $this->decode($product_info['name']),
			'url'      => $url
		];

		// Description
		$description = trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace('<', ' <', $this->decode($product_info['description'])))));

		if ($description) {
			$schema['description'] = oc_substr($description, 0, 5000);
		}

		// Images
		$images = $this->getImages($product_info);

		if ($images) {
			$schema['image'] = $images;
		}

		// Identifiers
		$schema += $this->getIdentifiers($product_info);

		// Brand
		if ($manufacturer !== '') {
			$schema['brand'] = [
				'@type' => 'Brand',
				'name'  => $this->decode($manufacturer)
			];
		}

		// Category: the last breadcrumb before the product itself.
		if (count($breadcrumbs) > 2) {
			$category = $breadcrumbs[count($breadcrumbs) - 2];

			$schema['category'] = trim(strip_tags($this->decode($category['text'])));
		}

		// Offer - only when prices are visible to this visitor, like the product page.
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$schema['offers'] = $this->getOffer($product_info, $url);
		}

		// Reviews
		if ($this->config->get('config_review_status')) {
			$schema += $this->getReviews($product_id);
		}

		return $schema;
	}

	/**
	 * Get Breadcrumb Schema
	 *
	 * @param array<int, array<string, mixed>> $breadcrumbs
	 *
	 * @return array<string, mixed>
	 */
	public function getBreadcrumbSchema(array $breadcrumbs): array {
		$items = [];

		$position = 1;

		foreach ($breadcrumbs as $key => $breadcrumb) {
			$name = trim(strip_tags($this->decode((string)$breadcrumb['text'])));

			// The home breadcrumb is an icon in the default theme.
			if ($name === '' && $key == 0) {
				$name = $this->decode((string)$this->config->get('config_name'));
			}

			if ($name === '' || empty($breadcrumb['href'])) {
				continue;
			}

			$items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $name,
				'item'     => $this->decode($breadcrumb['href'])
			];
		}

		if (count($items) < 2) {
			return [];
		}

		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items
		];
	}

	/**
	 * Get Organization Schema
	 *
	 * @return array<string, mixed>
	 */
	public function getOrganizationSchema(): array {
		$url = $this->config->get('config_url');

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'@id'      => $url . '#organization',
			'name'     => $this->decode((string)$this->config->get('config_name')),
			'url'      => $url
		];

		if ($this->config->get('config_logo') && is_file(DIR_IMAGE . $this->decode($this->config->get('config_logo')))) {
			$schema['logo'] = $url . 'image/' . $this->decode($this->config->get('config_logo'));
		}

		if ($this->config->get('config_email')) {
			$schema['email'] = $this->config->get('config_email');
		}

		if ($this->config->get('config_telephone')) {
			$schema['telephone'] = $this->config->get('config_telephone');
		}

		$same_as = [];

		foreach (preg_split('/\r\n|\r|\n/', $this->decode((string)$this->config->get('module_product_schema_same_as'))) as $line) {
			$line = trim($line);

			if (filter_var($line, FILTER_VALIDATE_URL)) {
				$same_as[] = $line;
			}
		}

		if ($same_as) {
			$schema['sameAs'] = $same_as;
		}

		return $schema;
	}

	/**
	 * Get Website Schema
	 *
	 * @return array<string, mixed>
	 */
	public function getWebsiteSchema(): array {
		$url = $this->config->get('config_url');

		$search = $this->decode($this->url->link('product/search', 'language=' . $this->config->get('config_language') . '&search='));

		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'@id'             => $url . '#website',
			'name'            => $this->decode((string)$this->config->get('config_name')),
			'url'             => $url,
			'publisher'       => ['@id' => $url . '#organization'],
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => [
					'@type'       => 'EntryPoint',
					'urlTemplate' => $search . '{search_term_string}'
				],
				'query-input' => 'required name=search_term_string'
			]
		];
	}

	/**
	 * Get Offer
	 *
	 * @param array<string, mixed> $product_info
	 * @param string               $url
	 *
	 * @return array<string, mixed>
	 */
	private function getOffer(array $product_info, string $url): array {
		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		// Same price the product page shows: special, otherwise discount/price, with tax if the store displays tax.
		$price = (float)$product_info['special'] ? (float)$product_info['special'] : (float)$product_info['price'];

		$price = $this->tax->calculate($price, (int)$product_info['tax_class_id'], $this->config->get('config_tax'));

		$offer = [
			'@type'         => 'Offer',
			'url'           => $url,
			'priceCurrency' => $currency,
			'price'         => number_format((float)$this->currency->format($price, $currency, 0, false), $this->currency->getDecimalPlace($currency), '.', ''),
			'availability'  => 'https://schema.org/' . $this->getAvailability($product_info),
			'itemCondition' => 'https://schema.org/' . ($this->config->get('module_product_schema_condition') ?: 'NewCondition'),
			'seller'        => [
				'@type' => 'Organization',
				'name'  => $this->decode((string)$this->config->get('config_name'))
			]
		];

		if ((float)$product_info['special']) {
			$date_end = $this->getSpecialDateEnd((int)$product_info['product_id']);

			if ($date_end) {
				$offer['priceValidUntil'] = $date_end;
			}
		}

		// Return policy
		if ($this->config->get('module_product_schema_return_status') && $this->config->get('module_product_schema_return_country')) {
			$days = (int)$this->config->get('module_product_schema_return_days');

			$policy = [
				'@type'             => 'MerchantReturnPolicy',
				'applicableCountry' => strtoupper($this->config->get('module_product_schema_return_country'))
			];

			if ($days > 0) {
				$policy['returnPolicyCategory'] = 'https://schema.org/MerchantReturnFiniteReturnWindow';
				$policy['merchantReturnDays'] = $days;
				$policy['returnMethod'] = 'https://schema.org/ReturnByMail';
				$policy['returnFees'] = 'https://schema.org/' . ($this->config->get('module_product_schema_return_fees') ?: 'FreeReturn');
			} else {
				$policy['returnPolicyCategory'] = 'https://schema.org/MerchantReturnNotPermitted';
			}

			$offer['hasMerchantReturnPolicy'] = $policy;
		}

		// Shipping
		if ($this->config->get('module_product_schema_shipping_status') && $this->config->get('module_product_schema_shipping_country')) {
			$rate = (float)$this->config->get('module_product_schema_shipping_rate');

			$offer['shippingDetails'] = [
				'@type'               => 'OfferShippingDetails',
				'shippingRate'        => [
					'@type'    => 'MonetaryAmount',
					'value'    => number_format((float)$this->currency->format($rate, $currency, 0, false), $this->currency->getDecimalPlace($currency), '.', ''),
					'currency' => $currency
				],
				'shippingDestination' => [
					'@type'          => 'DefinedRegion',
					'addressCountry' => strtoupper($this->config->get('module_product_schema_shipping_country'))
				],
				'deliveryTime'        => [
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => [
						'@type'    => 'QuantitativeValue',
						'minValue' => (int)$this->config->get('module_product_schema_handling_min'),
						'maxValue' => max((int)$this->config->get('module_product_schema_handling_min'), (int)$this->config->get('module_product_schema_handling_max')),
						'unitCode' => 'DAY'
					],
					'transitTime'  => [
						'@type'    => 'QuantitativeValue',
						'minValue' => (int)$this->config->get('module_product_schema_transit_min'),
						'maxValue' => max((int)$this->config->get('module_product_schema_transit_min'), (int)$this->config->get('module_product_schema_transit_max')),
						'unitCode' => 'DAY'
					]
				]
			];
		}

		return $offer;
	}

	/**
	 * Get Availability
	 *
	 * @param array<string, mixed> $product_info
	 *
	 * @return string
	 */
	private function getAvailability(array $product_info): string {
		if ((int)$product_info['quantity'] > 0) {
			return 'InStock';
		}

		$map = (array)$this->config->get('module_product_schema_availability');

		$allowed = ['InStock', 'OutOfStock', 'PreOrder', 'BackOrder', 'LimitedAvailability', 'SoldOut', 'Discontinued', 'InStoreOnly', 'OnlineOnly', 'PreSale'];

		$value = $map[(int)$product_info['stock_status_id']] ?? 'OutOfStock';

		return in_array($value, $allowed) ? $value : 'OutOfStock';
	}

	/**
	 * Get Special Date End
	 *
	 * Specials are stored in oc_product_discount with special = 1 in OpenCart 4.1.
	 *
	 * @param int $product_id
	 *
	 * @return string
	 */
	private function getSpecialDateEnd(int $product_id): string {
		$query = $this->db->query("SELECT `date_end` FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = '" . (int)$product_id . "' AND `customer_group_id` = '" . (int)$this->config->get('config_customer_group_id') . "' AND `quantity` = '1' AND `special` = '1' AND ((`date_start` = '0000-00-00' OR `date_start` < NOW()) AND (`date_end` = '0000-00-00' OR `date_end` > NOW())) ORDER BY `priority` ASC, `price` ASC LIMIT 1");

		if ($query->num_rows && $query->row['date_end'] && $query->row['date_end'] != '0000-00-00') {
			return $query->row['date_end'];
		}

		return '';
	}

	/**
	 * Get Identifiers
	 *
	 * Reads oc_product_code (OpenCart 4.1) and falls back to the legacy oc_product columns.
	 *
	 * @param array<string, mixed> $product_info
	 *
	 * @return array<string, string>
	 */
	private function getIdentifiers(array $product_info): array {
		$codes = [];

		foreach ($this->model_catalog_product->getCodes((int)$product_info['product_id']) as $result) {
			if ($result['status'] && trim($result['value']) !== '') {
				$codes[strtoupper($result['code'])] = trim($this->decode($result['value']));
			}
		}

		foreach (['SKU', 'UPC', 'EAN', 'JAN', 'ISBN', 'MPN'] as $code) {
			$column = strtolower($code);

			if (!isset($codes[$code]) && !empty($product_info[$column])) {
				$codes[$code] = trim($this->decode($product_info[$column]));
			}
		}

		$identifiers = [];

		if (!empty($codes['SKU'])) {
			$identifiers['sku'] = $codes['SKU'];
		} elseif (!empty($product_info['model'])) {
			$identifiers['sku'] = $this->decode($product_info['model']);
		}

		if (!empty($codes['MPN'])) {
			$identifiers['mpn'] = $codes['MPN'];
		}

		$gtin_codes = array_filter(array_map('trim', explode(',', strtoupper((string)($this->config->get('module_product_schema_gtin') ?: 'EAN,UPC,JAN,ISBN')))));

		foreach ($gtin_codes as $code) {
			if (!empty($codes[$code])) {
				$value = preg_replace('/[\s-]/', '', $codes[$code]);

				if (preg_match('/^\d+$/', $value) && in_array(strlen($value), [8, 12, 13, 14])) {
					$identifiers['gtin'] = $value;

					break;
				}
			}
		}

		return $identifiers;
	}

	/**
	 * Get Images
	 *
	 * @param array<string, mixed> $product_info
	 *
	 * @return array<int, string>
	 */
	private function getImages(array $product_info): array {
		$this->load->model('tool/image');

		$width = (int)$this->config->get('config_image_popup_width') ?: 800;
		$height = (int)$this->config->get('config_image_popup_height') ?: 800;

		$files = [];

		if ($product_info['image']) {
			$files[] = $product_info['image'];
		}

		foreach ($this->model_catalog_product->getImages((int)$product_info['product_id']) as $result) {
			$files[] = $result['image'];
		}

		$images = [];

		foreach (array_slice(array_unique($files), 0, 5) as $file) {
			if (is_file(DIR_IMAGE . $this->decode($file))) {
				$images[] = $this->model_tool_image->resize($this->decode($file), $width, $height);
			}
		}

		return $images;
	}

	/**
	 * Get Reviews
	 *
	 * Only approved reviews are used.
	 *
	 * @param int $product_id
	 *
	 * @return array<string, mixed>
	 */
	private function getReviews(int $product_id): array {
		$query = $this->db->query("SELECT AVG(`rating`) AS `rating`, COUNT(*) AS `total` FROM `" . DB_PREFIX . "review` WHERE `product_id` = '" . (int)$product_id . "' AND `status` = '1'");

		if (!(int)$query->row['total']) {
			return [];
		}

		$schema = [
			'aggregateRating' => [
				'@type'       => 'AggregateRating',
				'ratingValue' => number_format((float)$query->row['rating'], 1, '.', ''),
				'reviewCount' => (int)$query->row['total'],
				'bestRating'  => 5,
				'worstRating' => 1
			]
		];

		$limit = (int)$this->config->get('module_product_schema_reviews');

		if ($limit > 0) {
			$query = $this->db->query("SELECT `author`, `rating`, `text`, `date_added` FROM `" . DB_PREFIX . "review` WHERE `product_id` = '" . (int)$product_id . "' AND `status` = '1' ORDER BY `date_added` DESC LIMIT " . min(10, $limit));

			foreach ($query->rows as $result) {
				$schema['review'][] = [
					'@type'         => 'Review',
					'author'        => [
						'@type' => 'Person',
						'name'  => $this->decode($result['author'])
					],
					'datePublished' => date('Y-m-d', strtotime($result['date_added'])),
					'reviewBody'    => trim(strip_tags($this->decode($result['text']))),
					'reviewRating'  => [
						'@type'       => 'Rating',
						'ratingValue' => (int)$result['rating'],
						'bestRating'  => 5,
						'worstRating' => 1
					]
				];
			}
		}

		return $schema;
	}

	/**
	 * Decode
	 *
	 * OpenCart stores text HTML-escaped; JSON-LD needs plain text.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function decode(string $value): string {
		return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
	}
}
