<?php
class ModelDesignNewsticker extends Model {
	public function getnewsticker($newsticker_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "newsticker b LEFT JOIN " . DB_PREFIX . "newsticker_description bi ON (b.newsticker_id = bi.newsticker_id) WHERE b.newsticker_id = '" . (int)$newsticker_id . "' AND b.status = '1' AND bi.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY bi.position ASC");
		return $query->rows;
	}
}
