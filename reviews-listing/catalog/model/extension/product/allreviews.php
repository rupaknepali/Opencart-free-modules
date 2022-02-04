<?php
class ModelExtensionProductAllReviews extends Model
{
    public function getReviews($data = array())
    {
        $sql = "SELECT r.product_id, r.text, r.author, r.rating, r.status, r.date_added FROM " . DB_PREFIX . "review r";


        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND r.status = '" . (int) $data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        $sort_data = array(
            'r.author',
            'r.rating',
            'r.status',
            'r.date_added'
        );

        // if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
        //     $sql .= " ORDER BY " . $data['sort'];
        // } else {

        // }
        $sql .= " ORDER BY r.review_id";
        $sql .= " DESC";
        // if (isset($data['order']) && ($data['order'] == 'ASC')) {
        //     $sql .= " ASC";
        // } else {
        //     $sql .= " DESC";
        // }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }
        //echo $sql;
        //SELECT r.review_id, p.product_id, p.image, p.price, pd.name, pd.description, r.author, r.rating, r.status, s.name, r.date_added FROM oc_review r LEFT JOIN oc_product p on (r.product_id = p.product_id) LEFT JOIN oc_product_description pd ON (r.product_id = pd.product_id) LEFT JOIN oc_stock_status s on (p.stock_status_id = s.stock_status_id) WHERE pd.language_id = '1' ORDER BY r.date_added ASC LIMIT 0,15
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalReviews($data = array())
    {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r LEFT JOIN " . DB_PREFIX . "product_description pd ON (r.product_id = pd.product_id) WHERE pd.language_id = '" . (int) $this->config->get('config_language_id') . "'";

        if (!empty($data['filter_product'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_product']) . "%'";
        }

        if (!empty($data['filter_author'])) {
            $sql .= " AND r.author LIKE '" . $this->db->escape($data['filter_author']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND r.status = '" . (int) $data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getTotalReviewsAwaitingApproval()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review WHERE status = '0'");

        return $query->row['total'];
    }
    public function getReviewsByProductId($product_id, $start = 0, $limit = 20)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 20;
        }

        $query = $this->db->query("SELECT r.review_id, r.author, r.rating, r.text, p.product_id, pd.name, p.price, p.image, r.date_added FROM " . DB_PREFIX . "review r LEFT JOIN " . DB_PREFIX . "product p ON (r.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int) $product_id . "' AND p.date_available <= NOW() AND p.status = '1' AND r.status = '1' AND pd.language_id = '" . (int) $this->config->get('config_language_id') . "' ORDER BY r.date_added DESC LIMIT " . (int) $start . "," . (int) $limit);

        return $query->rows;
    }

    public function getTotalReviewsByProductId($product_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r LEFT JOIN " . DB_PREFIX . "product p ON (r.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int) $product_id . "' AND p.date_available <= NOW() AND p.status = '1' AND r.status = '1' AND pd.language_id = '" . (int) $this->config->get('config_language_id') . "'");

        return $query->row['total'];
    }
}
