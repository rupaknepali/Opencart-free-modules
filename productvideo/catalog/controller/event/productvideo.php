<?php
namespace Opencart\Catalog\Controller\Extension\Productvideo\Event;
/**
 * Product Video: YouTube and Vimeo videos on product pages, loaded only when the shopper presses play.
 */
class Productvideo extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/product/after
	 */
	public function index(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_productvideo_status') || empty($args['product_id'])) {
			return;
		}

		$this->load->model('extension/productvideo/module/productvideo');

		$videos = [];

		foreach ($this->model_extension_productvideo_module_productvideo->getVideos((int)$args['product_id']) as $video) {
			// Only IDs are stored, and they were validated on save, so building the URLs here is safe.
			if ($video['provider'] == 'vimeo') {
				$embed = 'https://player.vimeo.com/video/' . rawurlencode($video['video_id']) . '?autoplay=1&dnt=1';
				$thumb = '';
			} else {
				$embed = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($video['video_id']) . '?autoplay=1&rel=0';
				$thumb = 'https://i.ytimg.com/vi/' . rawurlencode($video['video_id']) . '/hqdefault.jpg';
			}

			$videos[] = [
				'provider' => $video['provider'],
				'embed'    => $embed,
				'thumb'    => $thumb,
				'title'    => $video['title'] ?: $args['heading_title']
			];
		}

		if (!$videos) {
			return;
		}

		$this->load->language('extension/productvideo/module/productvideo');

		$html = $this->load->view('extension/productvideo/module/productvideo', ['videos' => $videos]);

		if ($this->config->get('module_productvideo_position') == 'image') {
			// Under the image column's thumbnails: before the second column starts.
			if (preg_match('/<div class="image magnific-popup[^"]*">/', $output, $match, PREG_OFFSET_CAPTURE)) {
				$column = strpos($output, '<div class="col-sm">', $match[0][1]);

				if ($column !== false) {
					$close = strrpos(substr($output, 0, $column), '</div>');
					$output = substr_replace($output, '<div class="mt-3">' . $html . '</div>', $close, 0);

					return;
				}
			}
		}

		// Default: a "Video" tab after the Description tab.
		$tab = '<li class="nav-item"><a href="#tab-video" data-bs-toggle="tab" class="nav-link">' . $this->language->get('pv_tab') . '</a></li>';
		$pane = '<div id="tab-video" class="tab-pane fade mb-4">' . $html . '</div>';

		$output = preg_replace('/(<ul class="nav nav-tabs">\s*<li class="nav-item">.*?<\/li>)/s', '$1' . $tab, $output, 1, $count);

		if ($count) {
			$output = preg_replace('/(<div class="tab-content">)/', '$1' . $pane, $output, 1);
		}
	}
}
