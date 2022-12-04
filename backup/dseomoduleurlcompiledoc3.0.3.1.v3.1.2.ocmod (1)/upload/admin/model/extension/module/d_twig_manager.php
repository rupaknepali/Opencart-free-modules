<?php
/*
 *  location: admin/model
 */

class ModelExtensionModuleDTwigManager extends Model {

    /**

     Modal functions

     **/

    public function editTheme($store_id, $theme, $route, $code) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "theme` WHERE store_id = '" . (int)$store_id . "' AND theme = '" . $this->db->escape($theme) . "' AND route = '" . $this->db->escape($route) . "'");
        
        $this->db->query("INSERT INTO `" . DB_PREFIX . "theme` SET store_id = '" . (int)$store_id . "', theme = '" . $this->db->escape($theme) . "', route = '" . $this->db->escape($route) . "', code = '" . $this->db->escape($code) . "', date_added = NOW()");
    }

    public function deleteTheme($theme_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "theme` WHERE theme_id = '" . (int)$theme_id . "'");
    }

    public function getTheme($store_id, $theme, $route) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "theme` WHERE store_id = '" . (int)$store_id . "' AND theme = '" . $this->db->escape($theme) . "' AND route = '" . $this->db->escape($route) . "'");

        return $query->row;
    }
    
    public function getThemes($start = 0, $limit = 10) {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 10;
        }       
        
        $query = $this->db->query("SELECT *, (SELECT name FROM `" . DB_PREFIX . "store` s WHERE s.store_id = t.store_id) AS store FROM `" . DB_PREFIX . "theme` t ORDER BY t.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }
    
    public function getTotalThemes() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "theme`");

        return $query->row['total'];
    }

    public function installDatabase(){
        
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "theme` (
          `theme_id` int(11) NOT NULL AUTO_INCREMENT,
          `store_id` int(11) NOT NULL,
          `theme` varchar(64) NOT NULL,
          `route` varchar(64) NOT NULL,
          `code` mediumtext NOT NULL,
          `date_added` datetime NOT NULL,
          PRIMARY KEY (`theme_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");


        $result = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '" . DB_PREFIX . "theme' ORDER BY ORDINAL_POSITION")->rows; 
        $columns = array();
        foreach($result as $column){
            $columns[] = $column['COLUMN_NAME'];
        }

        if(in_array('code', $columns)){
             $this->db->query("ALTER TABLE `" . DB_PREFIX . "theme` MODIFY COLUMN `code` mediumtext NOT NULL");
        }

        if(!in_array('date_added', $columns)){
             $this->db->query("ALTER TABLE `" . DB_PREFIX . "theme` ADD `date_added` datetime NOT NULL");
        }

    }

    public function isCompatible(){

        $d_opencart_patch = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_opencart_patch.json'));
        if(!$d_opencart_patch){
            return false;
        }

        $this->load->model('extension/d_opencart_patch/modification');

        $compatibility = $this->model_extension_d_opencart_patch_modification->getModificationByName('d_twig_manager');
        if($compatibility){
            if(!empty($compatibility['status'])){
                return true;
            }
        }

        return false;

    }

    public function installCompatibility(){

        $d_opencart_patch = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_opencart_patch.json'));
        if(!$d_opencart_patch){
            return false;
        }

        if(!$this->isCompatible()){
            $this->load->model('extension/d_opencart_patch/modification');

            $this->model_extension_d_opencart_patch_modification->setModification('d_twig_manager.xml', 0);
            $this->model_extension_d_opencart_patch_modification->setModification('d_twig_manager.xml', 1);

            $this->installDatabase();

            $this->model_extension_d_opencart_patch_modification->refreshCache();

            $this->load->model('extension/d_opencart_patch/url');
            $this->response->redirect($this->model_extension_d_opencart_patch_url->link($this->request->get['route']));
        }

        return true;
    }

    public function uninstallCompatibility(){

        $d_opencart_patch = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_opencart_patch.json'));
        if(!$d_opencart_patch){
            return false;
        }

        $this->load->model('extension/d_opencart_patch/modification');
        $this->model_extension_d_opencart_patch_modification->setModification('d_twig_manager.xml', 0);
        $this->model_extension_d_opencart_patch_modification->refreshCache();

        return true;
    }
        

    /**

     Helper functions

     **/

    /*
    *   Format the link to work with ajax requests
    */
    public function ajax($route, $url = '', $ssl = true){
        return str_replace('&amp;', '&', $this->url->link($route, $url, $ssl));
    }

    public function getSettingValue($key, $store_id = 0) {
        $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

        if ($query->num_rows) {
            return $query->row['value'];
        } else {
            return null;    
        }
    }
}
?>