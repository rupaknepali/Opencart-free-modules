<?php
class ModelExtensionModuleTestimonial extends Model
{
    /** getTestimonials method is to retrieve the testimonials which is called from controller like $results = $this->model_extension_module_testimonial->getTestimonials($filter_data);. $data is the filtering parameter. Multiple testimonials are returned  ***/
    public function getTestimonials($data = array())
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "testimonial c1 LEFT JOIN " . DB_PREFIX . "testimonial_description cd2 ON (c1.testimonial_id = cd2.testimonial_id) WHERE cd2.language_id ='" . (int) $this->config->get('config_language_id') . "'";
        $sort_data = array(
            'name',
            'sort_order'
        );
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sort_order";
        }
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }
        $query = $this->db->query($sql);
        return $query->rows;
    }
    /** getTestimonialDescriptions method is to retrieve the testimonials' description as per the language ***/
    public function getTestimonialDescriptions($testimonial_id)
    {
        $testimonial_description_data = array();
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "testimonial_description WHERE testimonial_id = '" . (int) $testimonial_id . "'");
        foreach ($query->rows as $result) {
            $testimonial_description_data[$result['language_id']] = array(
                'name'             => $result['name'],
                'description'      => $result['description']
            );
        }
        return $testimonial_description_data;
    }
}