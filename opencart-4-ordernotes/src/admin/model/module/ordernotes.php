<?php
namespace Opencart\Admin\Model\Extension\Ordernotes\Module;
/**
 * Staff Order Notes: storage in its own table, separate from the customer-visible order history.
 */
class Ordernotes extends \Opencart\System\Engine\Model {
	/**
	 * Saved settings with defaults for a fresh install (Config::get() returns '' for unsaved keys).
	 */
	public function getSettings(): array {
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_ordernotes');
		$out = [];

		foreach (['status' => 0, 'list' => 1, 'pinned' => 1] as $key => $value) {
			$out[$key] = array_key_exists('module_ordernotes_' . $key, $saved) ? (int)$saved['module_ordernotes_' . $key] : $value;
		}

		return $out;
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ordernote` (
			`ordernote_id` INT(11) NOT NULL AUTO_INCREMENT,
			`order_id` INT(11) NOT NULL,
			`user_id` INT(11) NOT NULL,
			`username` VARCHAR(64) NOT NULL,
			`note` TEXT NOT NULL,
			`pinned` TINYINT(1) NOT NULL DEFAULT '0',
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`ordernote_id`),
			KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function addNote(int $order_id, int $user_id, string $username, string $note, bool $pinned): int {
		if ($pinned) {
			// One pinned note per order: pinning a new one unpins the old one.
			$this->db->query("UPDATE `" . DB_PREFIX . "ordernote` SET `pinned` = '0' WHERE `order_id` = '" . (int)$order_id . "'");
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "ordernote` SET `order_id` = '" . (int)$order_id . "', `user_id` = '" . (int)$user_id . "', `username` = '" . $this->db->escape($username) . "', `note` = '" . $this->db->escape($note) . "', `pinned` = '" . (int)$pinned . "', `date_added` = NOW()");

		return $this->db->getLastId();
	}

	public function getNote(int $ordernote_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "ordernote` WHERE `ordernote_id` = '" . (int)$ordernote_id . "'")->row;
	}

	public function getNotes(int $order_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "ordernote` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `pinned` DESC, `date_added` DESC, `ordernote_id` DESC")->rows;
	}

	public function setPinned(int $ordernote_id, int $order_id, bool $pinned): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "ordernote` SET `pinned` = '0' WHERE `order_id` = '" . (int)$order_id . "'");

		if ($pinned) {
			$this->db->query("UPDATE `" . DB_PREFIX . "ordernote` SET `pinned` = '1' WHERE `ordernote_id` = '" . (int)$ordernote_id . "'");
		}
	}

	public function deleteNote(int $ordernote_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "ordernote` WHERE `ordernote_id` = '" . (int)$ordernote_id . "'");
	}

	/**
	 * Note count and latest note for a list of orders (one query for the whole order list page).
	 */
	public function getSummaries(array $order_ids): array {
		$order_ids = array_filter(array_map('intval', $order_ids));

		if (!$order_ids) {
			return [];
		}

		$query = $this->db->query("SELECT n.`order_id`, n.`note`, n.`username`, c.`total` FROM `" . DB_PREFIX . "ordernote` n JOIN (SELECT `order_id`, COUNT(*) AS `total`, MAX(`ordernote_id`) AS `latest` FROM `" . DB_PREFIX . "ordernote` WHERE `order_id` IN (" . implode(',', $order_ids) . ") GROUP BY `order_id`) c ON c.`latest` = n.`ordernote_id`");

		$out = [];

		foreach ($query->rows as $row) {
			$out[(int)$row['order_id']] = $row;
		}

		return $out;
	}

	public function getTotals(): array {
		return $this->db->query("SELECT COUNT(*) AS `notes`, COUNT(DISTINCT `order_id`) AS `orders` FROM `" . DB_PREFIX . "ordernote`")->row;
	}
}
