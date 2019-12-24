<?php

class ModelExtensionModuleHoveroverImage extends Model
{

    public function installTableBackImage()
    {

        $this->db->query("CREATE TABLE `" . DB_PREFIX ."product_to_image_back` ( `id` INT NOT NULL AUTO_INCREMENT , `image_back` VARCHAR(255) NOT NULL , `product_id` INT NOT NULL , PRIMARY KEY (`id`))");
    }

    public function dropTableBackImage()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_to_image_back`");
    }

}
