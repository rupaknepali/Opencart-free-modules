<?php
namespace Opencart\Catalog\Model\Extension\Salecountdown\Module;
/**
 * Sale Countdown Timer: finds when a product's active special ends.
 */
class Salecountdown extends \Opencart\System\Engine\Model {
	/**
	 * The end date of the special OpenCart is currently applying, or '' if it has none.
	 * Same conditions and order as OpenCart 4.1's own special price query, so it's always the same special.
	 */
	public function getSpecialEnd(int $product_id): string {
		$query = $this->db->query("SELECT `date_end` FROM `" . DB_PREFIX . "product_discount`
			WHERE `product_id` = '" . (int)$product_id . "'
			AND `customer_group_id` = '" . (int)$this->config->get('config_customer_group_id') . "'
			AND `quantity` = '1' AND `special` = '1'
			AND (`date_start` = '0000-00-00' OR `date_start` < NOW())
			AND (`date_end` = '0000-00-00' OR `date_end` > NOW())
			ORDER BY `priority` ASC, `price` ASC LIMIT 1");

		if ($query->num_rows && $query->row['date_end'] != '0000-00-00') {
			return $query->row['date_end'];
		}

		return '';
	}
}
