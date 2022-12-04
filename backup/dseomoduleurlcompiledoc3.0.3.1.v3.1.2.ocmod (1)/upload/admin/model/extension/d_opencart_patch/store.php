<?php
/*
 *  location: admin/model/extension/d_opencart_patch/store.php
 *
 */

class ModelExtensionDOpencartPatchStore extends Model {

    public function getAllStores(){
        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();
        $result = array();
        if($stores){
            $result[] = array(
                'store_id' => 0, 
                'name' => $this->config->get('config_name')
                );
            foreach ($stores as $store) {
                $result[] = array(
                    'store_id' => $store['store_id'],
                    'name' => $store['name']    
                    );
            }   
        }
        return $result;
    }
}