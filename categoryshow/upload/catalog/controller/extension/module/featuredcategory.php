<?php
class ControllerExtensionModuleFeaturedCategory extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/featuredcategory');

		$this->load->model('catalog/category');

		$this->load->model('tool/image');

		$data['categories'] = array();

		if (!$setting['limit']) {
			$setting['limit'] = 4;
		}

		if (!empty($setting['categoriesadded'])) {

            $data['box_title'] = $setting['name'];

            $categories_data = array();

			foreach ($setting['categoriesadded'] as $category_id) {
				$category_info = $this->model_catalog_category->getCategory($category_id);

				if ($category_info) {
                    $categories_data[] = $category_info;
				}
			}

			$categories = array_slice($categories_data, 0, (int)$setting['limit']);

			foreach ($categories as $category) {
				if ($category['image']) {
					$image = $this->model_tool_image->resize($category['image'], $setting['width'], $setting['height']);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
				}

				$data['categories'][] = array(
					'category_id'  => $category['category_id'],
					'thumb'       => $image,
					'name'        => $category['name'],
					'href'        => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category['category_id'])
				);
			}
		}

		$data['language'] = $this->config->get('config_language');

		if ($data['categories']) {
			return $this->load->view('extension/module/featuredcategory', $data);
		}
	}
}