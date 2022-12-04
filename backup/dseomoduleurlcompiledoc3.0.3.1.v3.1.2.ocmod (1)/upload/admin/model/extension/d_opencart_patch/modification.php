<?php
/*
 *  location: admin/model/extension/d_opencart_patch/modification.php
 *
 */

class ModelExtensionDOpencartPatchModification extends Model {

    /*
    *   Ocmod: turn on or off
    */

    public function setModification($xml, $status = 1){
        //finding file

        //by full path.
        $file = str_replace("system/", "", DIR_SYSTEM).$xml;
        if (!file_exists($file)) {
            $file =  DIR_SYSTEM.'library/d_shopunity/install/'.$xml;
        }

        //old format - depricated
        if (!file_exists($file)) {
            $file =  DIR_SYSTEM.'mbooth/install/'.$xml;
        }

        if (!file_exists($file)) {
            return false;
        }

        $json = array();
        if(VERSION >= '3.0.0.0'){
            $this->load->model('setting/modification');
        }else{
            $this->load->model('extension/modification');
        }
        
        if($status){
           

            // If xml file just put it straight into the DB
            $xml = file_get_contents($file);

            if ($xml) {
                try {
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->loadXml($xml);

                    $name = $dom->getElementsByTagName('name')->item(0);

                    if ($name) {
                        $name = $name->nodeValue;
                    } else {
                        $name = '';
                    }

                    $code = $dom->getElementsByTagName('code')->item(0);

                    if ($code) {
                        $code = $code->nodeValue;

                        // Check to see if the modification is already installed or not.
                        if(VERSION <= '2.0.0.0'){
                            $modification_info = $this->getModificationByName($name);
                        }else{
                            $modification_info = $this->getModificationByCode($code);
                        }

                        if ($modification_info) {
                            $json['error'] = sprintf($this->language->get('error_exists'), $modification_info['name']);
                        }
                    } else {
                        $json['error'] = $this->language->get('error_code');
                    }

                    $author = $dom->getElementsByTagName('author')->item(0);

                    if ($author) {
                        $author = $author->nodeValue;
                    } else {
                        $author = '';
                    }

                    $version = $dom->getElementsByTagName('version')->item(0);

                    if ($version) {
                        $version = $version->nodeValue;
                    } else {
                        $version = '';
                    }

                    $link = $dom->getElementsByTagName('link')->item(0);

                    if ($link) {
                        $link = $link->nodeValue;
                    } else {
                        $link = '';
                    }

                    $modification_data = array(
                        'name'    => $name,
                        'code'    => $code,
                        'author'  => $author,
                        'version' => $version,
                        'link'    => $link,
                        'xml'     => $xml,
                        'status'  => 1
                    );

                    if (!$json) {
                        $this->addModification($modification_data);
                    }
                } catch(Exception $exception) {
                    return false;
                }
            }
        }else{
            $modification_id = $this->getModificationId($xml);
            if($modification_id){
                if(VERSION >= '3.0.0.0'){
                    $this->model_setting_modification->deleteModification($modification_id);
                }else{
                    $this->model_extension_modification->deleteModification($modification_id);
                }
            }
        }
        return false;
    }

    public function refreshCache(){
        if(VERSION >= '3.0.0.0'){
            $this->load->language('marketplace/modification');
        }else{
            $this->load->language('extension/modification');
        }
        

        if(VERSION >= '3.0.0.0'){
            $this->load->model('setting/modification');
        }else{
            $this->load->model('extension/modification');
        }

        //remove conflict with third-pary extensions;
        if(file_exists(DIR_MODIFICATION.'admin/controller/extension/modification.php')){
            unlink(DIR_MODIFICATION.'admin/controller/extension/modification.php');
        }

            // Just before files are deleted, if config settings say maintenance mode is off then turn it on
            // $maintenance = $this->config->get('config_maintenance');

            // $this->load->model('setting/setting');

            // $this->model_setting_setting->editSettingValue('config', 'config_maintenance', true);

            //Log
            $log = array();

            // Clear all modification files
            $files = array();

            // Make path into an array
            $path = array(DIR_MODIFICATION . '*');

            // While the path array is still populated keep looping through
            while (count($path) != 0) {
                $next = array_shift($path);

                foreach (glob($next) as $file) {
                    // If directory add to path array
                    if (is_dir($file)) {
                        $path[] = $file . '/*';
                    }

                    // Add the file to the files to be deleted array
                    $files[] = $file;
                }
            }

            // Reverse sort the file array
            rsort($files);

            // Clear all modification files
            foreach ($files as $file) {
                if ($file != DIR_MODIFICATION . 'index.html') {
                    // If file just delete
                    if (is_file($file)) {
                        unlink($file);

                    // If directory use the remove directory function
                    } elseif (is_dir($file)) {
                        rmdir($file);
                    }
                }
            }

            // Begin
            $xml = array();

            // Load the default modification XML
            $xml[] = file_get_contents(DIR_SYSTEM . 'modification.xml');

            // This is purly for developers so they can run mods directly and have them run without upload sfter each change.
            $files = glob(DIR_SYSTEM . '*.ocmod.xml');

            if ($files) {
                foreach ($files as $file) {
                    $xml[] = file_get_contents($file);
                }
            }

            // Get the default modification file
            if(VERSION >= '3.0.0.0'){
                $results = $this->model_setting_modification->getModifications();
            }else{
                $results = $this->model_extension_modification->getModifications();
            }

            foreach ($results as $result) {
                if ($result['status']) {
                    if(VERSION <= '2.0.0.0'){
                        $xml[] = $result['code'];
                    }else{
                        $xml[] = $result['xml'];
                    }
                    
                }
            }

            $modification = array();

            foreach ($xml as $xml) {
                if(empty($xml)){
                    continue;
                }
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->preserveWhiteSpace = false;
                $dom->loadXml($xml);

                // Log
                $log[] = 'MOD: ' . $dom->getElementsByTagName('name')->item(0)->textContent;

                // Wipe the past modification store in the backup array
                $recovery = array();

                // Set the a recovery of the modification code in case we need to use it if an abort attribute is used.
                if (isset($modification)) {
                    $recovery = $modification;
                }

                $files = $dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file');

                foreach ($files as $file) {
                    $operations = $file->getElementsByTagName('operation');
                    if(VERSION >= "2.0.2.0" && VERSION < "2.1.0.0" ){
                        $files = explode(',', $file->getAttribute('path'));
                    }else{
                        $files = explode('|', $file->getAttribute('path'));
                    }

                    foreach ($files as $file) {
                        $path = '';

                        // Get the full path of the files that are going to be used for modification
                        if (substr($file, 0, 7) == 'catalog') {
                            $path = DIR_CATALOG . str_replace('../', '', substr($file, 8));
                        }

                        if (substr($file, 0, 5) == 'admin') {
                            $path = DIR_APPLICATION . str_replace('../', '', substr($file, 6));
                        }

                        if (substr($file, 0, 6) == 'system') {
                            $path = DIR_SYSTEM . str_replace('../', '', substr($file, 7));
                        }

                        if ($path) {
                            $files = glob($path, GLOB_BRACE);

                            if ($files) {
                                foreach ($files as $file) {
                                    // Get the key to be used for the modification cache filename.
                                    if (substr($file, 0, strlen(DIR_CATALOG)) == DIR_CATALOG) {
                                        $key = 'catalog/' . substr($file, strlen(DIR_CATALOG));
                                    }

                                    if (substr($file, 0, strlen(DIR_APPLICATION)) == DIR_APPLICATION) {
                                        $key = 'admin/' . substr($file, strlen(DIR_APPLICATION));
                                    }

                                    if (substr($file, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
                                        $key = 'system/' . substr($file, strlen(DIR_SYSTEM));
                                    }

                                    // If file contents is not already in the modification array we need to load it.
                                    if (!isset($modification[$key])) {
                                        $content = file_get_contents($file);

                                        $modification[$key] = preg_replace('~\r?\n~', "\n", $content);
                                        $original[$key] = preg_replace('~\r?\n~', "\n", $content);

                                        // Log
                                        $log[] = 'FILE: ' . $key;
                                    }

                                    foreach ($operations as $operation) {
                                        $error = $operation->getAttribute('error');

                                        // Ignoreif
                                        $ignoreif = $operation->getElementsByTagName('ignoreif')->item(0);

                                        if ($ignoreif) {
                                            if ($ignoreif->getAttribute('regex') != 'true') {
                                                if (strpos($modification[$key], $ignoreif->textContent) !== false) {
                                                    continue;
                                                }
                                            } else {
                                                if (preg_match($ignoreif->textContent, $modification[$key])) {
                                                    continue;
                                                }
                                            }
                                        }

                                        $status = false;

                                        // Search and replace
                                        if ($operation->getElementsByTagName('search')->item(0)->getAttribute('regex') != 'true') {
                                            // Search
                                            $search = $operation->getElementsByTagName('search')->item(0)->textContent;
                                            $trim = $operation->getElementsByTagName('search')->item(0)->getAttribute('trim');
                                            $index = $operation->getElementsByTagName('search')->item(0)->getAttribute('index');

                                            // Trim line if no trim attribute is set or is set to true.
                                            if (!$trim || $trim == 'true') {
                                                $search = trim($search);
                                            }

                                            // Add
                                            $add = $operation->getElementsByTagName('add')->item(0)->textContent;
                                            $trim = $operation->getElementsByTagName('add')->item(0)->getAttribute('trim');
                                            $position = $operation->getElementsByTagName('add')->item(0)->getAttribute('position');
                                            $offset = $operation->getElementsByTagName('add')->item(0)->getAttribute('offset');

                                            if ($offset == '') {
                                                $offset = 0;
                                            }

                                            // Trim line if is set to true.
                                            if ($trim == 'true') {
                                                $add = trim($add);
                                            }

                                            // Log
                                            $log[] = 'CODE: ' . $search;

                                            // Check if using indexes
                                            if ($index !== '') {
                                                $indexes = explode(',', $index);
                                            } else {
                                                $indexes = array();
                                            }

                                            // Get all the matches
                                            $i = 0;

                                            $lines = explode("\n", $modification[$key]);

                                            for ($line_id = 0; $line_id < count($lines); $line_id++) {
                                                $line = $lines[$line_id];

                                                // Status
                                                $match = false;

                                                // Check to see if the line matches the search code.
                                                if (stripos($line, $search) !== false) {
                                                    // If indexes are not used then just set the found status to true.
                                                    if (!$indexes) {
                                                        $match = true;
                                                    } elseif (in_array($i, $indexes)) {
                                                        $match = true;
                                                    }

                                                    $i++;
                                                }

                                                // Now for replacing or adding to the matched elements
                                                if ($match) {
                                                    switch ($position) {
                                                        default:
                                                        case 'replace':
                                                            $new_lines = explode("\n", $add);

                                                            if ($offset < 0) {
                                                                array_splice($lines, $line_id + $offset, abs($offset) + 1, array(str_replace($search, $add, $line)));

                                                                $line_id -= $offset;
                                                            } else {
                                                                array_splice($lines, $line_id, $offset + 1, array(str_replace($search, $add, $line)));
                                                            }

                                                            break;
                                                        case 'before':
                                                            $new_lines = explode("\n", $add);

                                                            array_splice($lines, $line_id - $offset, 0, $new_lines);

                                                            $line_id += count($new_lines);
                                                            break;
                                                        case 'after':
                                                            $new_lines = explode("\n", $add);

                                                            array_splice($lines, ($line_id + 1) + $offset, 0, $new_lines);

                                                            $line_id += count($new_lines);
                                                            break;
                                                    }

                                                    // Log
                                                    $log[] = 'LINE: ' . $line_id;

                                                    $status = true;
                                                }
                                            }

                                            $modification[$key] = implode("\n", $lines);
                                        } else {
                                            $search = trim($operation->getElementsByTagName('search')->item(0)->textContent);
                                            $limit = $operation->getElementsByTagName('search')->item(0)->getAttribute('limit');
                                            $replace = trim($operation->getElementsByTagName('add')->item(0)->textContent);

                                            // Limit
                                            if (!$limit) {
                                                $limit = -1;
                                            }

                                            // Log
                                            $match = array();

                                            preg_match_all($search, $modification[$key], $match, PREG_OFFSET_CAPTURE);

                                            // Remove part of the the result if a limit is set.
                                            if ($limit > 0) {
                                                $match[0] = array_slice($match[0], 0, $limit);
                                            }

                                            if ($match[0]) {
                                                $log[] = 'REGEX: ' . $search;

                                                for ($i = 0; $i < count($match[0]); $i++) {
                                                    $log[] = 'LINE: ' . (substr_count(substr($modification[$key], 0, $match[0][$i][1]), "\n") + 1);
                                                }

                                                $status = true;
                                            }

                                            // Make the modification
                                            $modification[$key] = preg_replace($search, $replace, $modification[$key], $limit);
                                        }

                                        if (!$status) {
                                            // Log
                                            $log[] = 'NOT FOUND!';

                                            // Abort applying this modification completely.
                                            if ($error == 'abort') {
                                                $modification = $recovery;

                                                // Log
                                                $log[] = 'ABORTING!';

                                                break 5;
                                            }

                                            // Skip current operation or break
                                            if ($error == 'skip') {
                                                continue;
                                            } else {
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Log
                $log[] = '----------------------------------------------------------------';
            }

            // Log
            $ocmod = new Log('ocmod.log');
            $ocmod->write(implode("\n", $log));

            // Write all modification files
            foreach ($modification as $key => $value) {
                // Only create a file if there are changes
                if ($original[$key] != $value) {
                    $path = '';

                    $directories = explode('/', dirname($key));

                    foreach ($directories as $directory) {
                        $path = $path . '/' . $directory;

                        if (!is_dir(DIR_MODIFICATION . $path)) {
                            @mkdir(DIR_MODIFICATION . $path, 0777);
                        }
                    }

                    $handle = fopen(DIR_MODIFICATION . $key, 'w');

                    fwrite($handle, $value);

                    fclose($handle);
                }
            }

            // Maintance mode back to original settings
            //$this->model_setting_setting->editSettingValue('config', 'config_maintenance', $maintenance);

        return false;
    }

    public function getModificationId($xml){
        //by full path.
        $file = str_replace("system/", "", DIR_SYSTEM).$xml;
        if (!file_exists($file)) {
            $file =  DIR_SYSTEM.'library/d_shopunity/install/'.$xml;
        }

        //old format - depricated
        if (!file_exists($file)) {
            $file =  DIR_SYSTEM.'mbooth/install/'.$xml;
        }

        if (!file_exists($file)) {
            return false;
        }

        $xml = file_get_contents($file);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXml($xml);

        $code = $dom->getElementsByTagName('code')->item(0);
        $name = $dom->getElementsByTagName('name')->item(0);
        if ($name) {
            $name = $name->nodeValue;
        } else {
            $name = '';
        }

        if ($code) {
            $code = $code->nodeValue;
        } else {
            $code = '';
        }

        if ($code || $name) {
            if(VERSION <= '2.0.0.0'){
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "modification WHERE name = '" . $this->db->escape($name) . "'");
            }else{
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "modification WHERE code = '" . $this->db->escape($code) . "'");
            }
                
            if(isset($query->row['modification_id'])){
                return $query->row['modification_id'];
            }
        }
    }

    public function getModificationByCode($code) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "modification WHERE code = '" . $this->db->escape($code) . "'");

        return $query->row;
    }

    public function getModificationByName($name) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "modification WHERE name = '" . $this->db->escape($name) . "'");

        return $query->row;
    }

    public function addModification($data) {

        if(VERSION <= '2.0.0.0'){
            $this->db->query("INSERT INTO " . DB_PREFIX . "modification SET name = '" . $this->db->escape($data['name']) . "', author = '" . $this->db->escape($data['author']) . "', version = '" . $this->db->escape($data['version']) . "', link = '" . $this->db->escape($data['link']) . "', code = '" . $this->db->escape($data['xml']) . "', status = '" . (int)$data['status'] . "', date_added = NOW()");
        }else{
            $this->db->query("INSERT INTO " . DB_PREFIX . "modification SET code = '" . $this->db->escape($data['code']) . "', name = '" . $this->db->escape($data['name']) . "', author = '" . $this->db->escape($data['author']) . "', version = '" . $this->db->escape($data['version']) . "', link = '" . $this->db->escape($data['link']) . "', xml = '" . $this->db->escape($data['xml']) . "', status = '" . (int)$data['status'] . "', date_added = NOW()");
        }
    }
}