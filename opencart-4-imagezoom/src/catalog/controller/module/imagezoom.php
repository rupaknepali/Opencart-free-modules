<?php
namespace Opencart\Catalog\Controller\Extension\Imagezoom\Module;
/**
 * Product Image Zoom: hover zoom on the main product image, thumbnails that switch the main image,
 * and a popup that shows the large image.
 */
class Imagezoom extends \Opencart\System\Engine\Controller {
	/**
	 * The zoom version of an image: the original when it is small enough, otherwise a cached copy
	 * scaled down (keeping its shape) so the longest side is the configured size.
	 */
	private function zoomImage(string $path, int $max): ?array {
		$file = DIR_IMAGE . html_entity_decode($path, ENT_QUOTES, 'UTF-8');

		if (!$path || !is_file($file) || !($info = @getimagesize($file))) {
			return null;
		}

		[$width, $height] = $info;

		if (max($width, $height) > $max) {
			$scale = $max / max($width, $height);
			$width = (int)round($width * $scale);
			$height = (int)round($height * $scale);

			$url = $this->model_tool_image->resize(html_entity_decode($path, ENT_QUOTES, 'UTF-8'), $width, $height);
		} else {
			$url = $this->config->get('config_url') . 'image/' . str_replace(' ', '%20', html_entity_decode($path, ENT_QUOTES, 'UTF-8'));
		}

		return ['url' => $url, 'w' => $width, 'h' => $height];
	}

	/**
	 * Event: catalog/view/product/product/after
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_imagezoom_status')) {
			return;
		}

		$product_id = (int)($args['product_id'] ?? 0);

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$product = $this->model_catalog_product->getProduct($product_id);

		if (!$product || !$product['image']) {
			return;
		}

		$max = (int)$this->config->get('module_imagezoom_size') ?: 1600;
		$thumb_w = (int)$this->config->get('config_image_thumb_width');
		$thumb_h = (int)$this->config->get('config_image_thumb_height');
		$popup_w = (int)$this->config->get('config_image_popup_width');
		$popup_h = (int)$this->config->get('config_image_popup_height');
		$small_w = (int)$this->config->get('config_image_additional_width');
		$small_h = (int)$this->config->get('config_image_additional_height');

		// Same order as the template: the main image, then the additional images.
		$paths = [$product['image']];

		foreach ($this->model_catalog_product->getImages($product_id) as $image) {
			$paths[] = $image['image'];
		}

		$items = [];

		foreach ($paths as $path) {
			$zoom = $this->zoomImage($path, $max);

			if (!$zoom) {
				// Keep the positions in line with the links on the page.
				$items[] = null;

				continue;
			}

			$items[] = [
				'main'  => $this->model_tool_image->resize(html_entity_decode($path, ENT_QUOTES, 'UTF-8'), $thumb_w, $thumb_h),
				'popup' => $this->model_tool_image->resize(html_entity_decode($path, ENT_QUOTES, 'UTF-8'), $popup_w, $popup_h),
				'small' => $this->model_tool_image->resize(html_entity_decode($path, ENT_QUOTES, 'UTF-8'), $small_w, $small_h),
				'zoom'  => $zoom['url'],
				'zw'    => $zoom['w'],
				'zh'    => $zoom['h']
			];
		}

		$data['config'] = json_encode([
			'items'    => $items,
			'hover'    => (bool)$this->config->get('module_imagezoom_hover'),
			'swap'     => (bool)$this->config->get('module_imagezoom_swap'),
			'lightbox' => (bool)$this->config->get('module_imagezoom_lightbox'),
			'min'      => max(1, (float)$this->config->get('module_imagezoom_min'))
		], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

		$html = $this->load->view('extension/imagezoom/module/imagezoom', $data);

		if (($position = strripos($output, '</body>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}
}
