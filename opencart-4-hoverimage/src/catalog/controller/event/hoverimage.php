<?php
namespace Opencart\Catalog\Controller\Extension\Hoverimage\Event;
/**
 * Swap Image on Hover: the product's first additional image on hover.
 */
class Hoverimage extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/thumb/after
	 * Used by category, search, special, manufacturer pages and product modules alike.
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_hoverimage_status') || empty($args['product_id'])) {
			return;
		}

		$this->load->model('catalog/product');

		$images = $this->model_catalog_product->getImages((int)$args['product_id']);

		if (!$images || !is_file(DIR_IMAGE . html_entity_decode($images[0]['image'], ENT_QUOTES, 'UTF-8'))) {
			return;
		}

		// Same size as the card's main image: modules use their own sizes, so read it from the thumbnail URL.
		if (preg_match('/-(\d+)x(\d+)\.[a-z]+$/i', (string)($args['thumb'] ?? ''), $size)) {
			[$width, $height] = [(int)$size[1], (int)$size[2]];
		} else {
			$width = (int)$this->config->get('config_image_product_width');
			$height = (int)$this->config->get('config_image_product_height');
		}

		$this->load->model('tool/image');

		$src = $this->model_tool_image->resize(html_entity_decode($images[0]['image'], ENT_QUOTES, 'UTF-8'), $width, $height);

		$img = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="" aria-hidden="true" loading="lazy" class="img-fluid oc-hover-img"/>';

		// After the main image, inside the card's image link.
		if (preg_match('#<div class="image[^"]*">\s*<a [^>]*>\s*<img [^>]*>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $img, $match[0][1] + strlen($match[0][0]), 0);
		}
	}

	/**
	 * Trigger: catalog/view/common/header/after
	 */
	public function header(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_hoverimage_status')) {
			return;
		}

		$transition = $this->config->get('module_hoverimage_effect') == 'instant' ? 'none' : 'opacity .35s ease';

		// Only on devices that can hover, so a tap on a phone opens the product as usual.
		$css = '<style>.product-thumb .image a{position:relative;display:block}.oc-hover-img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;background:#fff;opacity:0;pointer-events:none;transition:' . $transition . '}@media (hover:hover){.product-thumb:hover .oc-hover-img{opacity:1}}</style>';

		$output = str_replace('</head>', $css . '</head>', $output);
	}
}
