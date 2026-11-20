<?php
namespace Opencart\Catalog\Controller\Extension\Fullwidth\Event;
/**
 * Full-Width Layout Position: renders the modules in "Content Full Width" right under the menu.
 */
class Fullwidth extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/common/header/after
	 * The default header ends with <main> and the menu, so anything appended here sits above the
	 * page's container: full width, on every page whose layout has modules in this position.
	 */
	public function header(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_fullwidth_status')) {
			return;
		}

		$modules = $this->modules($this->layoutId());

		if ($modules) {
			$output .= '<div id="content-full" class="oc-content-full">' . implode('', $modules) . '</div>';
		}
	}

	/**
	 * The same layout lookup as OpenCart's common/content_top.
	 */
	private function layoutId(): int {
		$route = (string)($this->request->get['route'] ?? 'common/home');
		$route = str_replace('|', '.', $route);

		$layout_id = 0;

		if ($route == 'product/category' && isset($this->request->get['path'])) {
			$this->load->model('catalog/category');

			$path = explode('_', (string)$this->request->get['path']);

			$layout_id = $this->model_catalog_category->getLayoutId((int)end($path));
		} elseif ($route == 'product/product' && isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$layout_id = $this->model_catalog_product->getLayoutId((int)$this->request->get['product_id']);
		} elseif ($route == 'product/manufacturer.info' && isset($this->request->get['manufacturer_id'])) {
			$this->load->model('catalog/manufacturer');

			$layout_id = $this->model_catalog_manufacturer->getLayoutId((int)$this->request->get['manufacturer_id']);
		} elseif ($route == 'information/information' && isset($this->request->get['information_id'])) {
			$this->load->model('catalog/information');

			$layout_id = $this->model_catalog_information->getLayoutId((int)$this->request->get['information_id']);
		}

		$this->load->model('design/layout');

		if (!$layout_id) {
			$layout_id = $this->model_design_layout->getLayout($route);
		}

		if (!$layout_id) {
			$layout_id = (int)$this->config->get('config_layout_id');
		}

		return (int)$layout_id;
	}

	private function modules(int $layout_id): array {
		$this->load->model('design/layout');
		$this->load->model('setting/module');

		$output = [];

		foreach ($this->model_design_layout->getModules($layout_id, 'content_full') as $module) {
			$part = explode('.', $module['code']);

			if (isset($part[1]) && !isset($part[2]) && $this->config->get('module_' . $part[1] . '_status')) {
				$html = $this->load->controller('extension/' . $part[0] . '/module/' . $part[1]);
			} elseif (isset($part[2])) {
				$setting_info = $this->model_setting_module->getModule((int)$part[2]);

				$html = ($setting_info && $setting_info['status']) ? $this->load->controller('extension/' . $part[0] . '/module/' . $part[1], $setting_info) : '';
			} else {
				$html = '';
			}

			if (is_string($html) && $html !== '') {
				$output[] = $html;
			}
		}

		return $output;
	}
}
