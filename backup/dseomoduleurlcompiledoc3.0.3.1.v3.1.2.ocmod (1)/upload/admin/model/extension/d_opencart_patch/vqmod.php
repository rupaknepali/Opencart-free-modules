<?php
/*
 *  location: admin/model/extension/d_opencart_patch/vqmod.php
 *
 */

class ModelExtensionDOpencartPatchVqmod extends Model {

    /*
    *   Vqmod: turn on or off
    */
    public function setModification($xml, $action = 1){
        $dir_vqmod =  str_replace("system", "vqmod/xml", DIR_SYSTEM);
        $on  = $dir_vqmod.$xml;
        $off = $dir_vqmod.$xml.'_';
        if($action){
            if (file_exists($off)) { 
                return rename($off, $on);
            }
        }else{
            if (file_exists($on)) { 
                return rename($on, $off);
            }
        }
        return false;
    }

    public function refreshCache(){
        $dir_vqmod =  str_replace("system", "vqmod", DIR_SYSTEM);
        $file  = $dir_vqmod.'mods.cache';

        if (file_exists($file)) { 
            return unlink($file);
        }
    
        return false;
    }
}