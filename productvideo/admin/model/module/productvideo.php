<?php
namespace Opencart\Admin\Model\Extension\Productvideo\Module;
/**
 * Product Video: video table and admin queries.
 */
class Productvideo extends \Opencart\System\Engine\Model {
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_video` (
			`product_video_id` int(11) NOT NULL AUTO_INCREMENT,
			`product_id` int(11) NOT NULL,
			`provider` varchar(16) NOT NULL,
			`video_id` varchar(32) NOT NULL,
			`title` varchar(128) NOT NULL,
			`sort_order` int(3) NOT NULL DEFAULT '0',
			PRIMARY KEY (`product_video_id`),
			KEY `product_id` (`product_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function getVideos(): array {
		return $this->db->query("SELECT `v`.*, `pd`.`name` AS `product` FROM `" . DB_PREFIX . "product_video` `v` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `v`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') ORDER BY `pd`.`name`, `v`.`sort_order`")->rows;
	}

	/**
	 * Replaces all videos with the submitted list: the form always posts the whole table.
	 */
	public function saveVideos(array $videos): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_video`");

		foreach ($videos as $video) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_video` SET `product_id` = '" . (int)$video['product_id'] . "', `provider` = '" . $this->db->escape($video['provider']) . "', `video_id` = '" . $this->db->escape($video['video_id']) . "', `title` = '" . $this->db->escape($video['title']) . "', `sort_order` = '" . (int)$video['sort_order'] . "'");
		}
	}
}
