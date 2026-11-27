<?php
namespace Opencart\Extension\Stocklast\System\Library;
/**
 * Wraps OpenCart's database object for one storefront request and changes only the two product list
 * queries of catalog/model/catalog/product (getProducts and getTotalProducts). OpenCart has no event
 * that can change their ORDER BY or WHERE, and sorting must happen in SQL to work across pages.
 */
class Stocklast extends \Opencart\System\Library\DB {
	private object $inner;
	private string $mode;

	// The parent constructor (which connects to the database) is intentionally not called.
	public function __construct(object $inner, string $mode) {
		$this->inner = $inner;
		$this->mode = $mode;
	}

	public function query(string $sql) {
		return $this->inner->query($this->rewrite($sql));
	}

	public function rewrite(string $sql): string {
		// getProducts(): the only product query that starts like this and groups by product.
		if (str_starts_with($sql, 'SELECT DISTINCT *, `pd`.`name`, `p`.`image`,') && str_contains($sql, ' GROUP BY `p`.`product_id` ORDER BY ')) {
			if ($this->mode == 'hide') {
				return str_replace(' GROUP BY `p`.`product_id` ORDER BY ', " AND `p`.`quantity` > '0' GROUP BY `p`.`product_id` ORDER BY ", $sql);
			}

			// In stock (0) before out of stock (1), then the shopper's chosen sort order.
			return str_replace(' GROUP BY `p`.`product_id` ORDER BY ', " GROUP BY `p`.`product_id` ORDER BY (`p`.`quantity` <= '0') ASC, ", $sql);
		}

		// getTotalProducts(): only needs changing when out-of-stock products are hidden, so pagination counts match.
		if ($this->mode == 'hide' && str_starts_with($sql, 'SELECT COUNT(DISTINCT `p`.`product_id`) AS `total`')) {
			return $sql . " AND `p`.`quantity` > '0'";
		}

		return $sql;
	}

	public function escape(string $value): string {
		return $this->inner->escape($value);
	}

	public function countAffected(): int {
		return $this->inner->countAffected();
	}

	public function getLastId(): int {
		return $this->inner->getLastId();
	}

	public function isConnected(): bool {
		return $this->inner->isConnected();
	}
}
