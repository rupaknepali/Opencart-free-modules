<?php 
    class ModelNewstickerInstall extends Model {
        public function addExtensionTables() {

            $this->db->query("CREATE TABLE IF NOT EXISTS ". DB_PREFIX . "newsticker(
            `newsticker_id` int(11) NOT NULL,
            `name` varchar(64) NOT NULL,
            `status` tinyint(1) NOT NULL,
            PRIMARY KEY (`newsticker_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

            $this->db->query("CREATE TABLE " . DB_PREFIX . "newsticker_description(
            `newsticker_description_id` int(11) NOT NULL,
              `newsticker_id` int(11) NOT NULL,
              `language_id` int(11) NOT NULL,
              `name` varchar(255) NOT NULL,
              `message` varchar(255) NOT NULL,
              `position` int(3) NOT NULL DEFAULT '0',
              PRIMARY KEY (`newsticker_description_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

            $this->db->query("ALTER TABLE " . DB_PREFIX . "newsticker
            MODIFY `newsticker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1");
            $this->db->query("ALTER TABLE " . DB_PREFIX . "newsticker_description
            MODIFY `newsticker_description_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1");

        }
    }
?>