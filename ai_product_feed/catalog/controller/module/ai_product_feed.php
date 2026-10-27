<?php
namespace Opencart\Catalog\Controller\Extension\AiProductFeed\Module;
/**
 * Class AiProductFeed
 *
 * AI Shopping Product Feed for OpenCart 4.1.0.x
 *
 * Fields follow the OpenAI product feed specification: https://developers.openai.com/commerce/specs/feed
 *
 * Cron:
 *   ai_product_feed (hour) -> cron (writes storage/ai_product_feed/feed-{store_id}.jsonl.gz)
 * Public route:
 *   extension/ai_product_feed/module/ai_product_feed            -> the gzip file
 *   extension/ai_product_feed/module/ai_product_feed&preview=1  -> first 20 items as readable JSON
 *
 * @package Opencart\Catalog\Controller\Extension\AiProductFeed\Module
 */
class AiProductFeed extends \Opencart\System\Engine\Controller {
	/**
	 * Category paths by category_id
	 *
	 * @var array<int, string>
	 */
	private array $categories = [];

	/**
	 * Manufacturer names by manufacturer_id
	 *
	 * @var array<int, string>
	 */
	private array $manufacturers = [];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		if (!$this->config->get('module_ai_product_feed_status')) {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			return;
		}

		if (!empty($this->request->get['preview'])) {
			$items = [];

			foreach ($this->getProductIds(0, 20) as $product_id) {
				$item = $this->getItem($product_id);

				if ($item) {
					$items[] = $item;
				}
			}

			$this->response->addHeader('Content-Type: application/json; charset=utf-8');
			$this->response->addHeader('X-Robots-Tag: noindex');
			$this->response->setOutput(json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			return;
		}

		$file = $this->getFilename();

		// Small catalogs work before the cron has run once.
		if (!is_file($file)) {
			$this->generate();
		}

		if (!is_file($file)) {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 503 Service Unavailable');

			return;
		}

		$this->response->addHeader('Content-Type: application/gzip');
		$this->response->addHeader('Content-Disposition: attachment; filename="products.jsonl.gz"');
		$this->response->addHeader('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int)filemtime($file)) . ' GMT');
		$this->response->addHeader('X-Robots-Tag: noindex');
		$this->response->setOutput((string)file_get_contents($file));
	}

	/**
	 * Cron
	 *
	 * @param int    $cron_id
	 * @param string $code
	 * @param string $cycle
	 * @param string $date_added
	 * @param string $date_modified
	 *
	 * @return void
	 */
	public function cron(int $cron_id, string $code, string $cycle, string $date_added, string $date_modified): void {
		if ($this->config->get('module_ai_product_feed_status')) {
			$this->generate();
		}
	}

	/**
	 * Generate
	 *
	 * Writes to a temporary file and renames it, so a download never gets a half-written feed.
	 *
	 * @return void
	 */
	private function generate(): void {
		$directory = DIR_STORAGE . 'ai_product_feed/';

		if (!is_dir($directory)) {
			mkdir($directory, 0755, true);
		}

		$file = $this->getFilename();
		$temp = $file . '.tmp';

		$handle = gzopen($temp, 'wb6');

		if (!$handle) {
			$this->log->write('AI Shopping Product Feed: cannot write ' . $temp);

			return;
		}

		$total = 0;
		$start = 0;

		do {
			$product_ids = $this->getProductIds($start, 200);

			foreach ($product_ids as $product_id) {
				try {
					$item = $this->getItem($product_id);

					if ($item) {
						gzwrite($handle, json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

						$total++;
					}
				} catch (\Throwable $e) {
					$this->log->write('AI Shopping Product Feed: product ' . $product_id . ': ' . $e->getMessage());
				}
			}

			$start += 200;
		} while ($product_ids);

		gzclose($handle);

		rename($temp, $file);

		// Read by the admin page to show when the feed was last built.
		file_put_contents(preg_replace('/\.jsonl\.gz$/', '.json', $file), json_encode([
			'generated' => date('Y-m-d H:i:s'),
			'items'     => $total,
			'size'      => filesize($file)
		]));
	}

	/**
	 * Get Item
	 *
	 * @param int $product_id
	 *
	 * @return array<string, mixed> empty if the product can't be listed
	 */
	private function getItem(int $product_id): array {
		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct($product_id);

		if (!$product) {
			return [];
		}

		$quantity = (int)$product['quantity'];

		if ($quantity <= 0 && !$this->config->get('module_ai_product_feed_out_of_stock')) {
			return [];
		}

		$currency = (string)$this->config->get('module_ai_product_feed_currency') ?: (string)$this->config->get('config_currency');

		if (!$this->currency->has($currency)) {
			$currency = (string)$this->config->get('config_currency');
		}

		$tax = (bool)$this->config->get('module_ai_product_feed_tax');

		$price = $this->money((float)$product['price'], (int)$product['tax_class_id'], $tax, $currency);

		// Free items and items without a price can't be listed.
		if ((float)$price <= 0) {
			return [];
		}

		$codes = $this->getCodes($product_id);

		$store_name = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		$brand = $this->getManufacturer((int)$product['manufacturer_id']) ?: ($this->config->get('module_ai_product_feed_brand_fallback') ? $store_name : '');

		$item = [
			'item_id'      => $this->getItemId($product, $codes),
			'title'        => oc_substr($this->plain($product['name']), 0, 150),
			'description'  => oc_substr($this->plain($product['description']) ?: $this->plain($product['name']), 0, 5000),
			'url'          => $this->link('product/product', 'product_id=' . $product_id),
			'brand'        => $brand,
			'seller_name'  => $store_name,
			'image_url'    => $product['image'] && is_file(DIR_IMAGE . html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8')) ? $this->imageUrl($product['image']) : '',
			'availability' => $this->getAvailability($product),
			'price'        => $price . ' ' . $currency,
			'condition'    => 'new'
		];

		// Required fields can't be empty. Leave the product out instead of sending a row that will be rejected.
		if ($item['brand'] === '' || $item['image_url'] === '') {
			return [];
		}

		if ((float)$product['special'] > 0 && (float)$product['special'] < (float)$product['price']) {
			$item['sale_price'] = $this->money((float)$product['special'], (int)$product['tax_class_id'], $tax, $currency) . ' ' . $currency;
		}

		$images = [];

		foreach ($this->model_catalog_product->getImages($product_id) as $image) {
			if ($image['image'] && is_file(DIR_IMAGE . html_entity_decode($image['image'], ENT_QUOTES, 'UTF-8'))) {
				$images[] = $this->imageUrl($image['image']);
			}
		}

		if ($images) {
			$item['additional_image_urls'] = array_slice($images, 0, 10);
		}

		foreach (['GTIN', 'EAN', 'UPC', 'JAN', 'ISBN'] as $code) {
			if (!empty($codes[$code]) && $this->isGtin($codes[$code])) {
				$item['gtin'] = $codes[$code];

				break;
			}
		}

		if (!empty($codes['MPN'])) {
			$item['mpn'] = $codes['MPN'];
		}

		$category = $this->getCategoryPath($product_id);

		if ($category) {
			$item['product_category'] = $category;
		}

		if (!empty($product['master_id'])) {
			$item['group_id'] = 'group-' . (int)$product['master_id'];
			$item['listing_has_variations'] = true;

			// The variant column maps product_option_id => product_option_value_id. Turn that into names.
			$variant = $this->getVariantNames((array)$product['variant']);

			if ($variant) {
				$item['variant_dict'] = $variant;
				$item['title'] = oc_substr($item['title'] . ' - ' . implode(', ', $variant), 0, 150);
			}
		}

		if ((float)$product['weight'] > 0) {
			$unit = $this->getWeightUnit((int)$product['weight_class_id']);

			if ($unit) {
				$item['weight'] = rtrim(rtrim(number_format((float)$product['weight'], 3, '.', ''), '0'), '.');
				$item['item_weight_unit'] = $unit;
			}
		}

		if ((int)$product['reviews'] > 0) {
			$item['review_count'] = (int)$product['reviews'];
			$item['star_rating'] = number_format((float)$product['rating'], 2, '.', '');
		}

		foreach (['return_policy' => 'module_ai_product_feed_return', 'seller_privacy_policy' => 'module_ai_product_feed_privacy', 'seller_tos' => 'module_ai_product_feed_terms'] as $field => $setting) {
			if ($this->config->get($setting)) {
				$item[$field] = $this->link('information/information', 'information_id=' . (int)$this->config->get($setting));
			}
		}

		$item['is_eligible_search'] = true;
		$item['is_eligible_checkout'] = false;

		return $item;
	}

	/**
	 * Get Product IDs
	 *
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array<int, int>
	 */
	private function getProductIds(int $start, int $limit): array {
		$query = $this->db->query("SELECT `p`.`product_id` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`) WHERE `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `p`.`status` = '1' AND `p`.`date_available` <= NOW() ORDER BY `p`.`product_id` ASC LIMIT " . (int)$start . "," . (int)$limit);

		return array_map('intval', array_column($query->rows, 'product_id'));
	}

	/**
	 * Get Item ID
	 *
	 * @param array<string, mixed>  $product
	 * @param array<string, string> $codes
	 *
	 * @return string
	 */
	private function getItemId(array $product, array $codes): string {
		$setting = $this->config->get('module_ai_product_feed_item_id');

		if ($setting == 'sku' && !empty($codes['SKU'])) {
			return $codes['SKU'];
		}

		if ($setting == 'model' && trim((string)$product['model']) !== '') {
			return html_entity_decode((string)$product['model'], ENT_QUOTES, 'UTF-8');
		}

		return (string)$product['product_id'];
	}

	/**
	 * Get Codes
	 *
	 * Product identifiers from Catalog > Products > Data (product_code table), keyed by upper case code.
	 *
	 * @param int $product_id
	 *
	 * @return array<string, string>
	 */
	private function getCodes(int $product_id): array {
		$codes = [];

		$query = $this->db->query("SELECT `code`, `value` FROM `" . DB_PREFIX . "product_code` WHERE `product_id` = '" . (int)$product_id . "' AND `value` != ''");

		foreach ($query->rows as $row) {
			$codes[strtoupper((string)$row['code'])] = trim(html_entity_decode((string)$row['value'], ENT_QUOTES, 'UTF-8'));
		}

		return $codes;
	}

	/**
	 * Get Availability
	 *
	 * @param array<string, mixed> $product
	 *
	 * @return string
	 */
	private function getAvailability(array $product): string {
		if ((int)$product['quantity'] > 0) {
			return 'in_stock';
		}

		if ($this->config->get('config_stock_checkout')) {
			$query = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "stock_status` WHERE `stock_status_id` = '" . (int)$product['stock_status_id'] . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

			$status = $query->num_rows ? strtolower((string)$query->row['name']) : '';

			return str_contains($status, 'pre') ? 'pre_order' : 'backorder';
		}

		return 'out_of_stock';
	}

	/**
	 * Get Category Path
	 *
	 * The deepest category of the product, written as "Parent > Child".
	 *
	 * @param int $product_id
	 *
	 * @return string
	 */
	private function getCategoryPath(int $product_id): string {
		$query = $this->db->query("SELECT `p2c`.`category_id`, (SELECT MAX(`level`) FROM `" . DB_PREFIX . "category_path` `cp` WHERE `cp`.`category_id` = `p2c`.`category_id`) AS `depth` FROM `" . DB_PREFIX . "product_to_category` `p2c` WHERE `p2c`.`product_id` = '" . (int)$product_id . "' ORDER BY `depth` DESC LIMIT 1");

		if (!$query->num_rows) {
			return '';
		}

		$category_id = (int)$query->row['category_id'];

		if (!isset($this->categories[$category_id])) {
			$path = $this->db->query("SELECT `cd`.`name` FROM `" . DB_PREFIX . "category_path` `cp` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cp`.`path_id` = `cd`.`category_id` AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `cp`.`category_id` = '" . $category_id . "' ORDER BY `cp`.`level` ASC");

			$this->categories[$category_id] = implode(' > ', array_map(fn ($row) => $this->plain((string)$row['name']), $path->rows));
		}

		return $this->categories[$category_id];
	}

	/**
	 * Get Variant Names
	 *
	 * @param array<int|string, mixed> $variant product_option_id => product_option_value_id
	 *
	 * @return array<string, string> option name => value name
	 */
	private function getVariantNames(array $variant): array {
		$names = [];

		foreach ($variant as $product_option_id => $product_option_value_id) {
			if (!is_scalar($product_option_value_id) || !(int)$product_option_value_id) {
				continue;
			}

			$query = $this->db->query("SELECT `od`.`name` AS `option`, `ovd`.`name` AS `value` FROM `" . DB_PREFIX . "product_option_value` `pov` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`pov`.`option_id` = `od`.`option_id` AND `od`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`pov`.`option_value_id` = `ovd`.`option_value_id` AND `ovd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `pov`.`product_option_id` = '" . (int)$product_option_id . "' AND `pov`.`product_option_value_id` = '" . (int)$product_option_value_id . "'");

			if ($query->num_rows && $query->row['option'] && $query->row['value']) {
				$names[$this->plain((string)$query->row['option'])] = $this->plain((string)$query->row['value']);
			}
		}

		return $names;
	}

	/**
	 * Get Manufacturer
	 *
	 * @param int $manufacturer_id
	 *
	 * @return string
	 */
	private function getManufacturer(int $manufacturer_id): string {
		if (!$manufacturer_id) {
			return '';
		}

		if (!isset($this->manufacturers[$manufacturer_id])) {
			$query = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");

			$this->manufacturers[$manufacturer_id] = $query->num_rows ? $this->plain((string)$query->row['name']) : '';
		}

		return $this->manufacturers[$manufacturer_id];
	}

	/**
	 * Get Weight Unit
	 *
	 * @param int $weight_class_id
	 *
	 * @return string g, kg, oz or lb, or empty if the store uses another unit
	 */
	private function getWeightUnit(int $weight_class_id): string {
		$query = $this->db->query("SELECT `unit` FROM `" . DB_PREFIX . "weight_class_description` WHERE `weight_class_id` = '" . (int)$weight_class_id . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		$unit = $query->num_rows ? strtolower(trim((string)$query->row['unit'])) : '';

		return in_array($unit, ['g', 'kg', 'oz', 'lb']) ? $unit : '';
	}

	/**
	 * Is GTIN
	 *
	 * 8, 12, 13 or 14 digits with a valid check digit.
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	private function isGtin(string $value): bool {
		if (!preg_match('/^(\d{8}|\d{12}|\d{13}|\d{14})$/', $value)) {
			return false;
		}

		$digits = array_map('intval', str_split(strrev(substr($value, 0, -1))));

		$sum = 0;

		foreach ($digits as $index => $digit) {
			$sum += $digit * ($index % 2 ? 1 : 3);
		}

		return (10 - $sum % 10) % 10 == (int)substr($value, -1);
	}

	/**
	 * Money
	 *
	 * @param float  $value
	 * @param int    $tax_class_id
	 * @param bool   $tax
	 * @param string $currency
	 *
	 * @return string e.g. 79.99
	 */
	private function money(float $value, int $tax_class_id, bool $tax, string $currency): string {
		if ($tax) {
			$value = $this->tax->calculate($value, $tax_class_id, true);
		}

		return number_format((float)$this->currency->format($value, $currency, '', false), 2, '.', '');
	}

	/**
	 * Plain
	 *
	 * @param string $value HTML-encoded text from the database
	 *
	 * @return string
	 */
	private function plain(string $value): string {
		$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

		$value = preg_replace('/<(br|\/p|\/li|\/h[1-6])[^>]*>/i', "\n", $value);

		$value = strip_tags($value);

		$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

		return trim(preg_replace(['/[ \t]+/', '/\n\s*\n+/'], [' ', "\n"], $value));
	}

	/**
	 * Link
	 *
	 * @param string $route
	 * @param string $args
	 *
	 * @return string
	 */
	private function link(string $route, string $args): string {
		return $this->url->link($route, 'language=' . $this->config->get('config_language') . '&' . $args, true);
	}

	/**
	 * Image URL
	 *
	 * @param string $image
	 *
	 * @return string
	 */
	private function imageUrl(string $image): string {
		return $this->config->get('config_url') . 'image/' . str_replace(' ', '%20', html_entity_decode($image, ENT_QUOTES, 'UTF-8'));
	}

	/**
	 * Get Filename
	 *
	 * @return string
	 */
	private function getFilename(): string {
		return DIR_STORAGE . 'ai_product_feed/feed-' . (int)$this->config->get('config_store_id') . '.jsonl.gz';
	}
}
