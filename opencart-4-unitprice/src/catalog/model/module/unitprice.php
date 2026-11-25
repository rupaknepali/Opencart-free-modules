<?php
namespace Opencart\Catalog\Model\Extension\Unitprice\Module;
/**
 * Unit Price: reads product contents and turns a pack price into a price per reference unit.
 */
class Unitprice extends \Opencart\System\Engine\Model {
	/**
	 * Each content unit: [family, size in the family's smallest unit].
	 */
	private const UNITS = [
		'mg'    => ['weight', 0.001],
		'g'     => ['weight', 1],
		'kg'    => ['weight', 1000],
		'ml'    => ['volume', 1],
		'cl'    => ['volume', 10],
		'l'     => ['volume', 1000],
		'cm'    => ['length', 1],
		'm'     => ['length', 100],
		'm2'    => ['area', 1],
		'oz'    => ['us_weight', 1],
		'lb'    => ['us_weight', 16],
		'floz'  => ['us_volume', 1],
		'piece' => ['count', 1]
	];

	/**
	 * Reference units: [size in the family's smallest unit, language key].
	 */
	private const REFERENCE = [
		'kg'    => [1000, 'ref_kg'],
		'100g'  => [100, 'ref_100g'],
		'l'     => [1000, 'ref_l'],
		'100ml' => [100, 'ref_100ml'],
		'm'     => [100, 'ref_m'],
		'm2'    => [1, 'ref_m2'],
		'oz'    => [1, 'ref_oz'],
		'lb'    => [16, 'ref_lb'],
		'floz'  => [1, 'ref_floz'],
		'piece' => [1, 'ref_piece']
	];

	private static array $cache = [];

	public function getContent(int $product_id): array {
		if (!array_key_exists($product_id, self::$cache)) {
			$query = $this->db->query("SELECT `amount`, `unit` FROM `" . DB_PREFIX . "unitprice` WHERE `product_id` = '" . $product_id . "' AND `amount` > '0'");

			self::$cache[$product_id] = $query->row;
		}

		return self::$cache[$product_id];
	}

	/**
	 * The reference unit for a content unit, from the store settings.
	 */
	public function getReference(string $unit): string {
		$family = self::UNITS[$unit][0] ?? '';

		return match ($family) {
			'weight'    => $this->config->get('module_unitprice_weight') == '100g' ? '100g' : 'kg',
			'volume'    => $this->config->get('module_unitprice_volume') == '100ml' ? '100ml' : 'l',
			'length'    => 'm',
			'area'      => 'm2',
			'us_weight' => $this->config->get('module_unitprice_us_weight') == 'lb' ? 'lb' : 'oz',
			'us_volume' => 'floz',
			'count'     => 'piece',
			default     => ''
		};
	}

	/**
	 * Price per reference unit, e.g. 4.99 for 500 g priced per kg gives 9.98.
	 */
	public function calculate(float $price, float $amount, string $unit): ?array {
		$reference = $this->getReference($unit);

		if (!$reference || $amount <= 0) {
			return null;
		}

		$content = $amount * self::UNITS[$unit][1];

		return [
			'price'     => $price / $content * self::REFERENCE[$reference][0],
			'reference' => self::REFERENCE[$reference][1]
		];
	}
}
