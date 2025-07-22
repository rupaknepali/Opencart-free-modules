<?php
namespace Opencart\Admin\Model\Extension\RedirectManager\Module;
class RedirectManager extends \Opencart\System\Engine\Model {
    public function install(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect` (
            `redirect_id` INT(11) NOT NULL AUTO_INCREMENT,
            `from_url` VARCHAR(255) NOT NULL,
            `to_url` VARCHAR(255) NOT NULL,
            `response_code` INT(3) NOT NULL,
            `is_regex` TINYINT(1) NOT NULL DEFAULT '0',
            `status` TINYINT(1) NOT NULL DEFAULT '1',
            `date_added` DATETIME NOT NULL,
            PRIMARY KEY (`redirect_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect_404_log` (
            `redirect_404_log_id` INT(11) NOT NULL AUTO_INCREMENT,
            `url` VARCHAR(255) NOT NULL,
            `hits` INT(11) NOT NULL DEFAULT '1',
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`redirect_404_log_id`),
            UNIQUE KEY `url` (`url`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->load->model('setting/event');
        $event_data = [
            'code'        => 'redirect_manager_404',
            'description' => 'Log 404 Not Found errors for Redirect Manager',
            'trigger'     => 'catalog/controller/error/not_found/before',
            'action'      => 'extension/redirect_manager/module/redirect_manager.handler',
            'status'      => 1,
            'sort_order'  => 0
        ];
        $this->model_setting_event->addEvent($event_data);
    }

    public function uninstall(): void {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect_404_log`");

        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('redirect_manager_404');
    }

    public function getRedirect(int $redirect_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect` WHERE `redirect_id` = '" . (int)$redirect_id . "'");
        return $query->row;
    }

    public function getRedirects(array $data = []): array {
        $sql = "SELECT * FROM `" . DB_PREFIX . "redirect` r";

        $implode = [];

        if (!empty($data['filter_from_url'])) {
            $implode[] = "r.from_url LIKE '" . $this->db->escape((string)$data['filter_from_url'] . '%') . "'";
        }

        if (!empty($data['filter_to_url'])) {
            $implode[] = "r.to_url LIKE '" . $this->db->escape((string)$data['filter_to_url'] . '%') . "'";
        }

        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        $sort_data = ['from_url', 'to_url', 'response_code', 'date_added'];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY r." . $data['sort'];
        } else {
            $sql .= " ORDER BY r.from_url";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalRedirects(array $data = []): int {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "redirect` r";

        $implode = [];

        if (!empty($data['filter_from_url'])) {
            $implode[] = "r.from_url LIKE '" . $this->db->escape((string)$data['filter_from_url'] . '%') . "'";
        }

        if (!empty($data['filter_to_url'])) {
            $implode[] = "r.to_url LIKE '" . $this->db->escape((string)$data['filter_to_url'] . '%') . "'";
        }

        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    }

    public function addRedirect(array $data): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "redirect` SET `from_url` = '" . $this->db->escape((string)$data['from_url']) . "', `to_url` = '" . $this->db->escape((string)$data['to_url']) . "', `response_code` = '" . (int)$data['response_code'] . "', `is_regex` = '" . (isset($data['is_regex']) ? (int)$data['is_regex'] : 0) . "', `status` = '" . (isset($data['status']) ? (int)$data['status'] : 0) . "', `date_added` = NOW()");
    }

    public function editRedirect(int $redirect_id, array $data): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "redirect` SET `from_url` = '" . $this->db->escape((string)$data['from_url']) . "', `to_url` = '" . $this->db->escape((string)$data['to_url']) . "', `response_code` = '" . (int)$data['response_code'] . "', `is_regex` = '" . (isset($data['is_regex']) ? (int)$data['is_regex'] : 0) . "', `status` = '" . (isset($data['status']) ? (int)$data['status'] : 0) . "' WHERE `redirect_id` = '" . (int)$redirect_id . "'");
    }

    public function deleteRedirect(int $redirect_id): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "redirect` WHERE `redirect_id` = '" . (int)$redirect_id . "'");
    }

    public function getNotFoundLogs(array $data = []): array {
        $sql = "SELECT * FROM `" . DB_PREFIX . "redirect_404_log` n";

        if (!empty($data['filter_url'])) {
            $sql .= " WHERE n.url LIKE '" . $this->db->escape('%' . (string)$data['filter_url'] . '%') . "'";
        }

        $sort_data = ['url', 'hits', 'date_added', 'date_modified'];

        if (isset($data['sort']) && in_array(str_replace('n.', '', $data['sort']), $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY n.date_modified";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) $data['start'] = 0;
            if ($data['limit'] < 1) $data['limit'] = 20;
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalNotFoundLogs(array $data = []): int {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "redirect_404_log` n";

        if (!empty($data['filter_url'])) {
            $sql .= " WHERE n.url LIKE '" . $this->db->escape('%' . (string)$data['filter_url'] . '%') . "'";
        }
        
        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    }

    public function clearNotFoundLogs(): void {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "redirect_404_log`");
    }
}
