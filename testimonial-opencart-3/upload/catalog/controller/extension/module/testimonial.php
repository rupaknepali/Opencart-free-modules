<?php

class ControllerExtensionModuleTestimonial extends Controller
{
	public function index($setting)
	{
		$this->load->language('extension/module/testimonial');
		$this->load->model('extension/module/testimonial');

		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
		$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.js');

		$this->load->model('tool/image');

		$data['testimonials'] = array();

		$filter_data = array(
			'sort'  => 'p.date_added',
			'order' => 'DESC',
			'start' => 0,
			'limit' => $setting['limit']
		);

		$results = $this->model_extension_module_testimonial->getTestimonials($filter_data);

		if ($results) {
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
				}

				$data['testimonials'][] = array(
					'testimonial_id'  => $result['testimonial_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')
				);
			}

			return $this->load->view('extension/module/testimonial', $data);
		}
	}
}