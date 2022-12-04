<?php
/*
 *  location: admin/model/extension/d_opencart_patch/cache.php
 *  
 *  This will help you refreshing the twig cache on update of an extension. 
 */

class ModelExtensionDOpencartPatchCache extends Model {

    public function clearCache() {

        $files = glob(DIR_CACHE . 'cache.*');

        if ($files) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        return true;
    }

    public function clearTwig() {
        
        $directories = glob(DIR_CACHE . '*', GLOB_ONLYDIR);

        if ($directories) {
            foreach ($directories as $directory) {
                $files = glob($directory . '/*');
                
                foreach ($files as $file) { 
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                if (is_dir($directory) && is_readable($directory) && count(scandir($directory)) == 0) {
                    rmdir($directory);
                }
            }
        }
                    
        return true;
    }
        
    public function clearSass() {
        $file = DIR_APPLICATION  . 'view/stylesheet/bootstrap.css';
            
        if (is_file($file) && is_file(DIR_APPLICATION . 'view/stylesheet/sass/_bootstrap.scss')) {
            unlink($file);
        }
         
        $files = glob(DIR_CATALOG  . 'view/theme/*/stylesheet/sass/_bootstrap.scss');
         
        foreach ($files as $file) {
            $file = substr($file, 0, -21) . '/bootstrap.css';
            
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    public function clearAll(){
        $this->clearCache();
        $this->clearTwig();
        $this->clearSass();
        return true;
    }
}