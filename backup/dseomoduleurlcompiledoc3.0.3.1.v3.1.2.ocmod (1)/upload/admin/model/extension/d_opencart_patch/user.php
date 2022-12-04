<?php
/*
 *  location: admin/model/extension/d_opencart_patch/user.php
 *
 */

class ModelExtensionDOpencartPatchUser extends Model {

    public function getGroupId(){
        if(VERSION == '2.0.0.0'){
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE user_id = '" . $this->user->getId() . "'");
            $user_group_id = (int)$user_query->row['user_group_id'];
        }else{
            $user_group_id = $this->user->getGroupId();
        }

        return $user_group_id;
    }

    public function getUrlToken(){
        if(VERSION >= '3.0.0.0'){
            return 'user_token=' . $this->session->data['user_token'];
        }else{
            return 'token=' . $this->session->data['token'];
        }
    }

    public function getToken(){
        if(VERSION >= '3.0.0.0'){
            return $this->session->data['user_token'];
        }else{
            return $this->session->data['token'];
        }
    }
    
    public function getCustomerGroups(){
        if (VERSION >= '2.1.0.1') {
            $this->load->model('customer/customer_group');
            return $this->model_customer_customer_group->getCustomerGroups();
        } else {
            $this->load->model('sale/customer_group');
            return $this->model_sale_customer_group->getCustomerGroups();
        }
     }


}