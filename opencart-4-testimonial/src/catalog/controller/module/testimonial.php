<?php
namespace Opencart\Catalog\Controller\Extension\Testimonial\Module;
class Testimonial extends \Opencart\System\Engine\Controller {
	/**
	 * @param array $setting the slider's settings, passed by the layout
	 */
	public function index(array $setting = []): string {
		static $instance = 0;

		$this->load->model('extension/testimonial/module/testimonial');
		$this->load->model('tool/image');

		$results = $this->model_extension_testimonial_module_testimonial->getTestimonials((int)($setting['limit'] ?? 10));

		if (!$results) {
			return '';
		}

		$width = max(1, (int)($setting['width'] ?? 100));
		$height = max(1, (int)($setting['height'] ?? 100));

		$data['testimonials'] = [];

		foreach ($results as $result) {
			$image = $result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $this->model_tool_image->resize(html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'), $width, $height) : '';

			$data['testimonials'][] = [
				'name'        => $result['name'],
				// Stored HTML-encoded by OpenCart: shown as text with line breaks, never as HTML.
				'description' => nl2br($result['description']),
				'rating'      => (int)$result['rating'],
				'thumb'       => $image
			];
		}

		$data['heading_title'] = $setting['name'] ?? '';
		$data['module'] = $instance++;
		$data['interval'] = (int)($setting['interval'] ?? 6) > 0 ? (int)$setting['interval'] * 1000 : 'false';
		$data['width'] = $width;
		$data['height'] = $height;

		$this->load->language('extension/testimonial/module/testimonial');

		return $this->load->view('extension/testimonial/module/testimonial', $data);
	}
}
