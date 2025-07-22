<?php
class ModelExtensionRedirectManagerToolRedirectManager extends Model {
    public function getRedirect(string $from_url): array {
        // First, try to find an exact match
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect` WHERE `from_url` = '" . $this->db->escape($from_url) . "' AND `status` = '1'");

        if ($query->num_rows) {
            return $query->row;
        }

        // If no exact match, look for regex matches
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect` WHERE `is_regex` = '1' AND `status` = '1'");

        foreach ($query->rows as $redirect) {
            if (preg_match('~' . str_replace('~', '\~', $redirect['from_url']) . '~', $from_url)) {
                return $redirect;
            }
        }

        return [];
    }

    public function log404(string $url): void {
        // Check if this URL is already logged
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_404_log` WHERE `url` = '" . $this->db->escape($url) . "'");

        if ($query->num_rows) {
            // If it exists, increment the hit count and update the date_modified
            $this->db->query("UPDATE `" . DB_PREFIX . "redirect_404_log` SET `hits` = `hits` + 1, `date_modified` = NOW() WHERE `log_id` = '" . (int)$query->row['log_id'] . "'");
        } else {
            // If it doesn't exist, create a new log entry
            $this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_404_log` SET `url` = '" . $this->db->escape($url) . "', `hits` = 1, `date_added` = NOW(), `date_modified` = NOW()");
        }
    }
}
