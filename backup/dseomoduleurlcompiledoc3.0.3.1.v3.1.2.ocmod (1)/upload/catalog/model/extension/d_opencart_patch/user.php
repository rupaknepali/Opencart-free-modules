<?php
/*
 *  location: catalog/model/extension/d_opencart_patch/user.php
 *
 */

class ModelExtensionDOpencartPatchUser extends Model {

    public function __construct($registry) {
        parent::__construct($registry);
        if(!isset($this->user)){
            $this->registry->set('user', new Cart\User($registry));
        }

    }

    public function isLogged(){
        return $this->user->isLogged();
    }

    public function getId(){
        return $this->user->getId();
    }

    public function getUserName(){
        return $this->user->getUserName();
    }

    public function logout(){
        return $this->user->logout();
    }

    public function hasPermission($key, $value) {
        return $this->user->hasPermission($key, $value);
    }

    public function getGroupId(){
        if(VERSION == '2.0.0.0'){
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE user_id = '" . $this->user->getId() . "'");
            $user_group_id = (int)$user_query->row['user_group_id'];
        }else{
            $user_group_id = $this->user->getGroupId();
        }

        return $user_group_id;
    }
}