<?php
namespace Opencart\Catalog\Model\Extension\RedirectManager\Module;
class RedirectManager extends \Opencart\System\Engine\Model {
    public function getActiveRedirects(): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect` WHERE `status` = '1'");
        return $query->rows;
    }

    public function logNotFound(string $url): void {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_404_log` WHERE `url` = '" . $this->db->escape($url) . "'");

        if ($query->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "redirect_404_log` SET `hits` = `hits` + 1, `date_modified` = NOW() WHERE `redirect_404_log_id` = '" . (int)$query->row['redirect_404_log_id'] . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_404_log` SET `url` = '" . $this->db->escape($url) . "', `hits` = 1, `date_added` = NOW(), `date_modified` = NOW()");
        }
    }
}
