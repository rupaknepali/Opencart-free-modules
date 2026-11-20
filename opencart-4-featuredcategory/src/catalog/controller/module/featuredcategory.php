<?php
namespace Opencart\Catalog\Controller\Extension\Featuredcategory\Module;
class Featuredcategory extends \Opencart\System\Engine\Controller {
	/**
	 * @param array $setting the module's settings, passed by the layout
	 */
	public function index(array $setting = []): string {
		if (empty($setting['category'])) {
			return '';
		}

		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$width = max(1, (int)($setting['width'] ?? 300));
		$height = max(1, (int)($setting['height'] ?? 300));
		$limit = max(1, (int)($setting['limit'] ?? 4));

		$data['categories'] = [];

		foreach ((array)$setting['category'] as $category_id) {
			if (count($data['categories']) >= $limit) {
				break;
			}

			// getCategory() only returns enabled categories assigned to this store.
			$category_info = $this->model_catalog_category->getCategory((int)$category_id);

			if (!$category_info) {
				continue;
			}

			$image = $category_info['image'] && is_file(DIR_IMAGE . html_entity_decode($category_info['image'], ENT_QUOTES, 'UTF-8')) ? $category_info['image'] : 'placeholder.png';

			$data['categories'][] = [
				'name'  => $category_info['name'],
				'thumb' => $this->model_tool_image->resize(html_entity_decode($image, ENT_QUOTES, 'UTF-8'), $width, $height),
				'href'  => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'])
			];
		}

		if (!$data['categories']) {
			return '';
		}

		$data['heading_title'] = $setting['name'] ?? '';
		$data['columns'] = in_array((int)($setting['columns'] ?? 4), [2, 3, 4, 6]) ? (int)$setting['columns'] : 4;
		$data['width'] = $width;
		$data['height'] = $height;

		return $this->load->view('extension/featuredcategory/module/featuredcategory', $data);
	}
}
