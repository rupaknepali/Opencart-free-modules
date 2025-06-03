<?php
class ModelExtensionModuleCsvReviewImport extends Model {
    
    public function install() {
        // No additional tables needed for this extension
    }
    
    public function importReviews($file) {
        $this->load->model('catalog/product');
        
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        
        // Expected CSV format:
        // product_model, author, text, rating, date_added, status
        
        $required_columns = ['product_model', 'author', 'text', 'rating'];
        $column_indexes = [];
        
        // Map column indexes from header
        foreach ($required_columns as $required) {
            $index = array_search($required, array_map('strtolower', $header));
            if ($index === false) {
                throw new Exception('Missing required column: ' . $required);
            }
            $column_indexes[$required] = $index;
        }
        
        // Optional columns with defaults
        $date_index = array_search('date_added', array_map('strtolower', $header));
        $status_index = array_search('status', array_map('strtolower', $header));
        
        $imported = 0;
        $skipped = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty($row) || count($row) < 4) {
                $skipped++;
                continue;
            }
            
            $product_model = $row[$column_indexes['product_model']];
            $author = $row[$column_indexes['author']];
            $text = $row[$column_indexes['text']];
            $rating = (int)$row[$column_indexes['rating']];
            
            // Validate rating (1-5)
            if ($rating < 1 || $rating > 5) {
                $skipped++;
                continue;
            }
            
            // Get date or use current date
            $date = ($date_index !== false && !empty($row[$date_index])) 
                ? $row[$date_index] 
                : date('Y-m-d H:i:s');
                
            // Get status or default to enabled (1)
            $status = ($status_index !== false && isset($row[$status_index])) 
                ? (int)$row[$status_index] 
                : 1;
            
            // Find product by model
            $product_id = $this->getProductIdByModel($product_model);
            
            if ($product_id) {
                $this->addReview($product_id, $author, $text, $rating, $date, $status);
                $imported++;
            } else {
                $skipped++;
            }
        }
        
        fclose($handle);
        
        $this->session->data['import_results'] = [
            'imported' => $imported,
            'skipped' => $skipped
        ];
        
        return true;
    }
    
    private function getProductIdByModel($model) {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($model) . "'");
        
        if ($query->num_rows) {
            return $query->row['product_id'];
        }
        
        return false;
    }
    
    private function addReview($product_id, $author, $text, $rating, $date, $status) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "review SET 
            product_id = '" . (int)$product_id . "', 
            customer_id = '0', 
            author = '" . $this->db->escape($author) . "', 
            text = '" . $this->db->escape($text) . "', 
            rating = '" . (int)$rating . "', 
            status = '" . (int)$status . "', 
            date_added = '" . $this->db->escape($date) . "', 
            date_modified = NOW()");
            
        return $this->db->getLastId();
    }
}
