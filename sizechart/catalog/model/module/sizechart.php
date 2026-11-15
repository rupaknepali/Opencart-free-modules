<?php
namespace Opencart\Catalog\Model\Extension\Sizechart\Module;
/**
 * Size Chart: finds the chart for a product's categories.
 */
class Sizechart extends \Opencart\System\Engine\Model {
	public function getChartByProduct(int $product_id): array {
		return $this->db->query("SELECT `sc`.* FROM `" . DB_PREFIX . "size_chart` `sc`
			INNER JOIN `" . DB_PREFIX . "size_chart_to_category` `s2c` ON (`s2c`.`size_chart_id` = `sc`.`size_chart_id`)
			INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`category_id` = `s2c`.`category_id`)
			WHERE `p2c`.`product_id` = '" . (int)$product_id . "' AND `sc`.`status` = '1'
			ORDER BY `sc`.`sort_order`, `sc`.`size_chart_id` LIMIT 1")->row;
	}
}
