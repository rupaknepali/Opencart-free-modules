<?php
/*
 *  location: admin/model/extension/d_opencart_patch/setting.php
 *
 */

class ModelExtensionDOpencartPatchSetting extends Model {
    public function getSetting($code, $store_id = 0) {
        $this->load->model('setting/setting');
        return $this->model_setting_setting->getSetting($code, $store_id);
    }

    public function editSetting($code, $data, $store_id = 0) {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting($code, $data, $store_id);
    }

    public function deleteSetting($code, $store_id = 0) {
        $this->load->model('setting/setting');
        return $this->model_setting_setting->deleteSetting($code, $store_id);
    }
    public function getSettingValue($key, $store_id = 0){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");
        if (empty($query->num_rows)) {
            return null;
        }
        if (!$query->row['serialized']) {
            return $query->row['value'];
        } else {
            if (VERSION >= '2.2.0.0') {
                return json_decode($query->row['value'], true);
            } else {
                return unserialize($query->row['value']);
            }
        }
    }
    public function editSettingValue($code = '', $key = '', $value = '', $store_id = 0) {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue($code, $key, $value, $store_id);
    }
}