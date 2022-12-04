<?php
/*
 *  location: admin/model/extension/d_opencart_patch/store.php
 *
 */

class ModelExtensionDOpencartPatchUrl extends Model {

    public function link($route, $url = '', $secure = 'SSL'){

        if(isset($this->session->data['user_token']) || isset($this->session->data['token'])){
            if(VERSION >= '3.0.0.0'){
                $token = 'user_token=' . $this->session->data['user_token'];
            }else{
                $token = 'token=' . $this->session->data['token'];
            }

            if($url){
                $url = $token . '&' .$url;
            }else{
                $url = $token;
            }
        }

        //this is a fallback for old versions. if you need to get Extension link - use getExtensionLink();
        if(VERSION >= '3.0.0.0'){
            $routes = array(
                'marketplace/extension' => 'marketplace/extension',
            );
        }elseif(VERSION >= '2.3.0.0'){
            $routes = array(
                'marketplace/extension' => 'extension/extension',
            );
        }else{
            $routes =  array(
                'marketplace/extension' => 'extension/module',
            );
        }
        
        foreach($routes as $key => $value){
            if (strpos($route, $key) === 0) {
                $route = str_replace($key, $value, $route);
            }
        }

        return $this->url->link($route, $url, $secure);
    }

    public function ajax($route, $url = '', $secure = true){
        return str_replace('&amp;', '&', $this->link($route, $url, $secure));
    }

    public function getExtensionLink($type, $url = '', $secure = 'SSL'){

        if(isset($this->session->data['user_token']) || isset($this->session->data['token'])){
            if(VERSION >= '3.0.0.0'){
                $token = 'user_token=' . $this->session->data['user_token'];
            }else{
                $token = 'token=' . $this->session->data['token'];
            }

            if($url){
                $url = $token . '&' .$url;
            }else{
                $url = $token;
            }
        }

        if(VERSION >= '3.0.0.0'){

            $route = 'marketplace/extension'; 
            $url = 'type='.$type.'&'.$url;

        }elseif(VERSION >= '2.3.0.0'){

            $route = 'extension/extension';
            $url = 'type='.$type.'&'.$url;

        }else{

            $route = 'extension/'.$type;

        }

        return $this->url->link($route, $url, $secure);
    }

    public function getExtensionAjax($route, $url = '', $secure = true){
        return str_replace('&amp;', '&', $this->getExtensionLink($route, $url, $secure));
    }
}