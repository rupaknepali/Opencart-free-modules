<?php
namespace Opencart\Catalog\Model\Extension\Productvideo\Module;
/**
 * Product Video: a product's videos.
 */
class Productvideo extends \Opencart\System\Engine\Model {
	public function getVideos(int $product_id): array {
		return $this->db->query("SELECT `provider`, `video_id`, `title` FROM `" . DB_PREFIX . "product_video` WHERE `product_id` = '" . (int)$product_id . "' ORDER BY `sort_order`, `product_video_id`")->rows;
	}
}
