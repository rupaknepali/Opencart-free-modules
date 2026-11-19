<?php
namespace Opencart\Catalog\Model\Extension\Csp\Module;
/**
 * Content Security Policy: stores violation reports, one row per distinct violation with a counter.
 */
class Csp extends \Opencart\System\Engine\Model {
	// The report URL is public, so cap the number of distinct rows. Existing rows still count up.
	private const MAX_ROWS = 1000;

	public function addReport(array $data): void {
		// Group by origin: seven Google Fonts files are one thing to allow, not seven. The first URL seen is kept as an example.
		$group = preg_match('#^((?:https?|wss?)://[^/]+)#i', $data['blocked'], $match) ? $match[1] : $data['blocked'];

		$hash = md5($data['policy'] . '|' . $data['directive'] . '|' . $group . '|' . $data['document'] . '|' . $data['source']);

		$query = $this->db->query("SELECT `report_id` FROM `" . DB_PREFIX . "csp_report` WHERE `hash` = '" . $this->db->escape($hash) . "'");

		if ($query->num_rows) {
			$this->db->query("UPDATE `" . DB_PREFIX . "csp_report` SET `count` = `count` + 1, `line` = '" . (int)$data['line'] . "', `sample` = '" . $this->db->escape($data['sample']) . "', `date_modified` = NOW() WHERE `report_id` = '" . (int)$query->row['report_id'] . "'");

			return;
		}

		$total = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "csp_report`");

		if ((int)$total->row['total'] >= self::MAX_ROWS) {
			return;
		}

		$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "csp_report` SET `hash` = '" . $this->db->escape($hash) . "', `policy` = '" . $this->db->escape($data['policy']) . "', `directive` = '" . $this->db->escape($data['directive']) . "', `blocked_uri` = '" . $this->db->escape($data['blocked']) . "', `document_uri` = '" . $this->db->escape($data['document']) . "', `source_file` = '" . $this->db->escape($data['source']) . "', `line` = '" . (int)$data['line'] . "', `sample` = '" . $this->db->escape($data['sample']) . "', `count` = '1', `date_added` = NOW(), `date_modified` = NOW()");
	}
}
