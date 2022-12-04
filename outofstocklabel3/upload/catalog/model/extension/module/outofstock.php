<?php

class ModelExtensionModuleOutOfStock extends Model
{
    public function getQuantity($data)
    {
        if (isset($data['has_option']) && $data['has_option'] == 1) {
            $product_id = $data['product_id'];
            $q = $this->db->query("SELECT stock FROM `" . DB_PREFIX . "product_option_variant` WHERE product_id = $product_id AND active = 1");
            $stock = 0;
            foreach ($q->rows as $row) {
                $stock += (int) $row['stock'];
            }
            return $stock;
        } else {
            return $data['quantity'];
        }
    }
}