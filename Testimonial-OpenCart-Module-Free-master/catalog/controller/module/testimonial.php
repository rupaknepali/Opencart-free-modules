<?php
class ControllerModuleTestimonial extends Controller {
	public function index($setting) {
		static $module = 0;

		$this->load->model('design/testimonial');
		$this->load->model('tool/image');

		$this->document->addStyle('catalog/view/javascript/jquery/owl-carousel/owl.carousel.css');
		$this->document->addStyle('catalog/view/javascript/jquery/owl-carousel/owl.transitions.css');
		$this->document->addScript('catalog/view/javascript/jquery/owl-carousel/owl.carousel.min.js');

		$data['testimonials'] = array();

		$results = $this->model_design_testimonial->getTestimonial($setting['testimonial_id']);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$data['testimonials'][] = array(
					'title' => $result['title'],
					'message' => $result['message'],
					'name' => $result['name'],
					'position' => $result['position'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize($result['image'], 65, 65)
				);
			}
		}

		$data['module'] = $module++;

		return $this->load->view('module/testimonial', $data);
	}
}