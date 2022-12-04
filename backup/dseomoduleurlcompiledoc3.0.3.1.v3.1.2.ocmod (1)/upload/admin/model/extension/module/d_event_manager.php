<?php
/*
 *  location: admin/model
 */

class ModelExtensionModuleDEventManager extends Model {

    /**

     Modal functions

     **/

    public function getEvents($data = array()) {
        $sql = "SELECT ";

        if(!empty($data['unique'])){
            $sql .= " DISTINCT ";
        }

        $sql .= " * FROM `" . DB_PREFIX . "event` ";

        $implode = array();

        if (!empty($data['filter_code'])) {
            $implode[] = "`code` LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
        }

        if (!empty($data['filter_trigger'])) {
            $implode[] = "`trigger` LIKE '%" . $this->db->escape($data['filter_trigger']) . "%'";
        }

        if (!empty($data['filter_action'])) {
            $implode[] = "`action` LIKE '%" . $this->db->escape($data['filter_action']) . "%'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $implode[] = "`status` = '" . (int)$data['filter_status'] . "'";
        }


        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        if(!empty($data['unique'])){
            $sql .= " GROUP BY code ";
        }

        $sort_data = array(
            'code',
            'trigger',
            'action',
            'sort_order',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY `" . $data['sort'] . "`";
        } else {
            $sql .= " ORDER BY `sort_order`";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalEvents($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "event";

        $implode = array();

        if (!empty($data['filter_code'])) {
            $implode[] = "`code` LIKE '%" . $this->db->escape($data['filter_code']) . "%'";
        }

        if (!empty($data['filter_trigger'])) {
            $implode[] = "`trigger` LIKE '%" . $this->db->escape($data['filter_trigger']) . "%'";
        }

        if (!empty($data['filter_action'])) {
            $implode[] = "`action` LIKE '%" . $this->db->escape($data['filter_action']) . "%'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $implode[] = "`status` = '" . (int)$data['filter_status'] . "'";
        }

        if ($implode) {
            $sql .= " WHERE " . implode(" AND ", $implode);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function updateEvent($event_id, $data){
        if(!isset($data['sort_order'])){
            $data['sort_order'] = 0;
        }
        $this->db->query("UPDATE " . DB_PREFIX . "event SET 
            `code` = '" . $this->db->escape($data['code'])."',
            `trigger` = '" . $this->db->escape($data['trigger'])."',
            `action` = '" . $this->db->escape($data['action'])."',
            `status` = '". (int)$data['status']."',
            `sort_order` = '". (int)$data['sort_order']."'
            WHERE event_id = '" . (int)$event_id . "'");

        return $this->getEventById($event_id);
    }

    public function addEvent($code, $trigger, $action, $status = 1, $sort_order = 0) {
        //fix conflict
        if(VERSION >= '2.3.0.0' && VERSION < '3.0.0.0'){
            $this->installDatabase();
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = '" . $this->db->escape($code) . "', `trigger` = '" . $this->db->escape($trigger) . "', `action` = '" . $this->db->escape($action) . "', `status` = '" . (int)$status . "', `sort_order` = '" . (int)$sort_order . "'");
    
        return $this->db->getLastId();
    }

    public function deleteEvent($code) {
        //if you have several events under one code - they will all be deleted. 
        //please use deleteEventById.

        if(VERSION >= '3.0.0.0'){
            $this->load->model('setting/event');
            return $this->model_setting_event->deleteEventByCode($code);
        }elseif(VERSION > '2.0.0.0'){
            $this->load->model('extension/event');
            return $this->model_extension_event->deleteEvent($code);
        }else{

            $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE `code` = '" . $this->db->escape($code) . "'");

        }
        
    }

    public function deleteEventById($event_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `event_id` = '" . (int)$event_id . "'");
    }

    public function deleteEventByCode($code) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($code) . "'");
    }

    public function getEventById($event_id) {
        $event = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE `event_id` = '" . $this->db->escape($event_id) ."'");
        
        return $event->row;
    }

    public function getEventByCode($code) {
        $query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($code) . "' LIMIT 1");

        return $query->row;
    }

    public function enableEvent($event_id) {
        if(VERSION >= '3.0.0.0'){
            $this->load->model('setting/event');
            return $this->model_setting_event->enableEvent($event_id);
        }elseif(VERSION > '2.3.0.0'){
            $this->load->model('extension/event');
            return $this->model_extension_event->enableEvent($event_id);    
        }else{
            $this->db->query("UPDATE " . DB_PREFIX . "event SET `status` = '1' WHERE event_id = '" . (int)$event_id . "'");
        }
        
    }
    
    public function disableEvent($event_id) {
        if(VERSION >= '3.0.0.0'){
            $this->load->model('setting/event');
            return $this->model_setting_event->disableEvent($event_id);
        }elseif(VERSION > '2.3.0.0'){
            $this->load->model('extension/event');
            return $this->model_extension_event->disableEvent($event_id);
        }else{
            $this->db->query("UPDATE " . DB_PREFIX . "event SET `status` = '0' WHERE event_id = '" . (int)$event_id . "'");
        }
    }

    public function installDatabase(){
        
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "event` (
          `event_id` int(11) NOT NULL AUTO_INCREMENT,
          `code` varchar(32) NOT NULL,
          `trigger` text NOT NULL,
          `action` text NOT NULL,
          `status` tinyint(1) NOT NULL,
          `sort_order` int(3) NOT NULL,
          PRIMARY KEY (`event_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");


        $result = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '" . DB_PREFIX . "event' ORDER BY ORDINAL_POSITION")->rows; 
        $columns = array();
        foreach($result as $column){
            $columns[] = $column['COLUMN_NAME'];
        }

        if(!in_array('status', $columns)){
             $this->db->query("ALTER TABLE `" . DB_PREFIX . "event` ADD status int( 1 ) NOT NULL default '1'");
        }

        if(!in_array('sort_order', $columns)){
             $this->db->query("ALTER TABLE `" . DB_PREFIX . "event` ADD `sort_order` int(3) NOT NULL");
        }

    }

    public function isCompatible(){

        if(VERSION >= '2.3.0.0'){
            if(VERSION < '3.0.0.0'){
                $this->installDatabase();
            }
            return true;
        }

        $this->load->model('extension/d_opencart_patch/modification');
        
        $compatibility = $this->model_extension_d_opencart_patch_modification->getModificationByName('d_event_manager');
        if($compatibility){
            if(!empty($compatibility['status'])){
                return true;
            }
        }

        return false;
    }

    public function installCompatibility(){

        $this->installDatabase();

        if(VERSION >= '2.3.0.0'){
            return true;
        }

        $d_opencart_patch = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_opencart_patch.json'));
        if(!$d_opencart_patch){
            return false;
        }

        $this->load->model('extension/d_opencart_patch/modification');
        
        $compatibility = $this->model_extension_d_opencart_patch_modification->getModificationByName('d_event_manager');
        if($compatibility){
            if(!empty($compatibility['status'])){
                return true;
            }else{
                $this->model_extension_d_opencart_patch_modification->setModification('d_event_manager.xml', 0);
            }
        }

        $this->model_extension_d_opencart_patch_modification->setModification('d_event_manager.xml', 1);
        $this->model_extension_d_opencart_patch_modification->refreshCache();

        return true;
    }

    public function uninstallCompatibility(){

        if(VERSION >= '2.3.0.0'){
            return true;
        }

        $d_opencart_patch = (file_exists(DIR_SYSTEM.'library/d_shopunity/extension/d_opencart_patch.json'));
        if(!$d_opencart_patch){
            return false;
        }

        $this->load->model('extension/d_opencart_patch/modification');

        $compatibility = $this->model_extension_d_opencart_patch_modification->getModificationByName('d_event_manager');
        if(!$compatibility){
            return true;
        }

        $this->model_extension_d_opencart_patch_modification->setModification('d_event_manager.xml', 0);
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


    private $subversions = array('lite', 'light', 'free');
    /*
    *   Return name of config file.
    */
    public function getConfigFileName($codename){
        
        if(isset($this->request->post['config'])){
            return $this->request->post['config'];
        }

        $setting = $this->config->get($codename.'_setting');

        if(isset($setting['config'])){
            return $setting['config'];
        }

        $full = DIR_SYSTEM . 'config/'. $codename . '.php';
        if (file_exists($full)) {
            return $codename;
        } 

        foreach ($this->subversions as $subversion){
            if (file_exists(DIR_SYSTEM . 'config/'. $codename . '_' . $subversion . '.php')) {
                return $codename . '_' . $subversion;
            }
        }
        
        return false;
    }

    /*
    *   Return list of config files that contain the codename of the module.
    */
    public function getConfigFileNames($codename){
        $files = array();
        $results = glob(DIR_SYSTEM . 'config/'. $codename .'*');
        foreach($results as $result){
            $files[] = str_replace('.php', '', str_replace(DIR_SYSTEM . 'config/', '', $result));
        }
        return $files;
    }
}