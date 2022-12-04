<?php
// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

require_once(DIR_SYSTEM . 'library/config.php');
require_once(DIR_SYSTEM . 'library/db.php');
require_once(DIR_SYSTEM . 'library/db/mysqli.php');
require_once(DIR_SYSTEM . 'engine/registry.php');

// Registry
$registry = new Registry();

// Config
$config = new Config();
$config->load('default');
$config->load('catalog');
$registry->set('config', $config);
// Database
$db =  new DB($config->get('db_engine'), $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port'));



$pi=0;
$pc=0;
$limit="";
if(isset($_GET['limit'])){
    $limit= " limit 0, ".$_GET['limit'];
}

$query = $db->query("select * from " . DB_PREFIX . "product_description" );
$products = $query->rows;

foreach ($products as $product) {
    $pi++;
    $name = $product['name'];
    $name = str_replace("'", '-', strtolower($name));
    $seoname = preg_replace('/s/', '-', $name);
    $seoname = str_replace([':', '\\', '/', '*', ' ','&', "'"], '-', $seoname);

    
    $seourl = "product_id=" . $product['product_id'];
    $query12 = $db->query("select * from " . DB_PREFIX . "seo_url where query='" . $seourl . "'");
    //echo "<pre>";
    //print_r($query12);
    if (!$query12->row) {
        $db->query("insert into " . DB_PREFIX . "seo_url set query='" . $seourl . "', keyword='" . $seoname . "'");
        echo "<br>Inserted " . $seoname;
        $pc++;
    }
}
echo "<br>Total products ".$pi;
echo "<br>Number of products seo title changed- ".$pc;