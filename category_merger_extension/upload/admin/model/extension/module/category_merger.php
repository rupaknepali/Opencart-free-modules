<?php
class ModelExtensionModuleCategoryMerger extends Model {
    
    /**
     * Merge source category into target category
     * 
     * @param int $source_category_id The category to be merged (will be deleted)
     * @param int $target_category_id The category to merge into (will remain)
     * @return bool Success status
     */
    public function mergeCategories($source_category_id, $target_category_id) {
        $this->db->query("START TRANSACTION");
        
        try {
            // 1. Get source category details for reference
            $source_category = $this->getCategory($source_category_id);
            if (!$source_category) {
                throw new Exception("Source category not found");
            }
            
            // 2. Get target category details for reference
            $target_category = $this->getCategory($target_category_id);
            if (!$target_category) {
                throw new Exception("Target category not found");
            }
            
            // 3. Move all products from source to target category
            $this->moveProductsToTargetCategory($source_category_id, $target_category_id);
            
            // 4. If source category has children, handle them
            $this->handleChildCategories($source_category_id, $target_category_id);
            
            // 5. Delete the source category
            $this->deleteCategory($source_category_id);
            
            // 6. Rebuild the category path table
            $this->repairCategories();
            
            $this->db->query("COMMIT");
            
            // Clear cache
            $this->cache->delete('category');
            
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return false;
        }
    }
    
    /**
     * Get category details
     * 
     * @param int $category_id
     * @return array
     */
    protected function getCategory($category_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'");
        return $query->row;
    }
    
    /**
     * Move all products from source category to target category
     * 
     * @param int $source_category_id
     * @param int $target_category_id
     */
    protected function moveProductsToTargetCategory($source_category_id, $target_category_id) {
        // Get all products in the source category
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$source_category_id . "'");
        
        foreach ($query->rows as $product) {
            $product_id = $product['product_id'];
            
            // Check if product is already in target category
            $exists_query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_to_category 
                WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$target_category_id . "'");
            
            // If product is not already in target category, add it
            if ($exists_query->row['total'] == 0) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET 
                    product_id = '" . (int)$product_id . "', 
                    category_id = '" . (int)$target_category_id . "'");
            }
            
            // Remove product from source category
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category 
                WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$source_category_id . "'");
        }
    }
    
    /**
     * Handle child categories when merging
     * 
     * @param int $source_category_id
     * @param int $target_category_id
     */
    protected function handleChildCategories($source_category_id, $target_category_id) {
        // Get all child categories of the source category
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category 
            WHERE parent_id = '" . (int)$source_category_id . "'");
        
        foreach ($query->rows as $child) {
            $child_id = $child['category_id'];
            
            // Update parent_id to point to target category
            $this->db->query("UPDATE " . DB_PREFIX . "category 
                SET parent_id = '" . (int)$target_category_id . "' 
                WHERE category_id = '" . (int)$child_id . "'");
        }
    }
    
    /**
     * Delete a category
     * 
     * @param int $category_id
     */
    protected function deleteCategory($category_id) {
        // Delete category
        $this->db->query("DELETE FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'");
        
        // Delete category description
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");
        
        // Delete category path entries
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");
        
        // Delete category filters
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");
        
        // Delete category to store relation
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");
        
        // Delete category to layout relation
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");
        
        // Delete SEO URLs
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category_id . "'");
    }
    
    /**
     * Rebuild the category path table
     */
    protected function repairCategories() {
        // Clear the path table
        $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "category_path");
        
        // Get all categories
        $query = $this->db->query("SELECT category_id, parent_id FROM " . DB_PREFIX . "category ORDER BY sort_order");
        
        foreach ($query->rows as $category) {
            $this->repairCategoryPath($category['category_id'], $category['parent_id']);
        }
    }
    
    /**
     * Rebuild the path for a specific category
     * 
     * @param int $category_id
     * @param int $parent_id
     */
    private function repairCategoryPath($category_id, $parent_id) {
        // Delete existing path entries
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");
        
        // If parent_id = 0 then this is a root category
        if ($parent_id == 0) {
            $level = 0;
            $path = $category_id;
        } else {
            // Get the path entries for the parent
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$parent_id . "' ORDER BY level ASC");
            
            foreach ($query->rows as $result) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_path SET category_id = '" . (int)$category_id . "', path_id = '" . (int)$result['path_id'] . "', level = '" . (int)$result['level'] . "'");
            }
            
            $level = $query->num_rows;
            $path = $category_id;
        }
        
        // Add the category's own path entry
        $this->db->query("INSERT INTO " . DB_PREFIX . "category_path SET category_id = '" . (int)$category_id . "', path_id = '" . (int)$path . "', level = '" . (int)$level . "'");
        
        // Repair children
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE parent_id = '" . (int)$category_id . "'");
        
        foreach ($query->rows as $child) {
            $this->repairCategoryPath($child['category_id'], $category_id);
        }
    }
}
