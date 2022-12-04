<?php
/*
 *  location: admin/model/extension/d_opencart_patch/event.php
 *  
 *  This is only a wrapper for basic event model. If you want full controll of events
 *  you should go for d_event_manager. You will be able to delete events by ID and more.
 */

class ModelExtensionDOpencartPatchEvent extends Model {


    public function addEvent($code, $trigger, $action, $status = 1) {
        $this->installDatabase();
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = '" . $this->db->escape($code) . "', `trigger` = '" . $this->db->escape($trigger) . "', `action` = '" . $this->db->escape($action) . "', `status` = '" . (int)$status . "', `date_added` = now()");
    
        return $this->db->getLastId();
    }

    public function deleteEvent($code) {
        $this->installDatabase();
        //if you have several events under one code - they will all be deleted. 
        //please use deleteEventById from d_event_manager.
        if(VERSION > '2.0.0.0'){
            $this->load->model('extension/event');
            return $this->model_extension_event->deleteEvent($code);
        }else{
            $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE `code` = '" . $this->db->escape($code) . "'");
        }
        
    }

    /**
    *   Install Databse for Events in case it is missing. This will not add the functionality of events. 
    **/
    public function installDatabase(){
        
        $this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."event` (
          `event_id` int(11) NOT NULL AUTO_INCREMENT,
          `code` varchar(32) NOT NULL,
          `trigger` text NOT NULL,
          `action` text NOT NULL,
          `status` tinyint(1) NOT NULL,
          `date_added` datetime NOT NULL,
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

        if(!in_array('date_added', $columns)){
             $this->db->query("ALTER TABLE `" . DB_PREFIX . "event` ADD `date_added` datetime NOT NULL");
        }

    }
}
?>