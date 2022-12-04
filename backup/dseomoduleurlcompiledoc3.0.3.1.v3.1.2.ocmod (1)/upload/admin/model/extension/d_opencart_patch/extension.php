<?php
/*
 *  location: admin/model/extension/d_opencart_patch/extension.php
 *
 */

class ModelExtensionDOpencartPatchExtension extends Model {

    public function getInstalled($type) {
        $extension_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = '" . $this->db->escape($type) . "' ORDER BY code");

        foreach ($query->rows as $result) {
            $extension_data[] = $result['code'];
        }

        return $extension_data;
    }

    public function isInstalled($code, $type = false) {
        $sql = "SELECT * FROM " . DB_PREFIX . "extension WHERE ";

        if($type){
            $sql .= "`type` = '" . $this->db->escape($type) . "' AND ";
        }

        $sql .= "`code` = '" . $this->db->escape($code) . "'";
        
        $query = $this->db->query($sql);
        if(!empty($query->row)){
            return true;
        }
        
        return false;
    }

    public function install($type, $code) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "extension SET `type` = '" . $this->db->escape($type) . "', `code` = '" . $this->db->escape($code) . "'");
    }

    public function uninstall($type, $code) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "extension WHERE `type` = '" . $this->db->escape($type) . "' AND `code` = '" . $this->db->escape($code) . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "'");
    }

    
}