<?php
namespace Opencart\Admin\Controller\Extension\Productvideo\Module;
/**
 * Product Video: the video list and settings.
 */
class Productvideo extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/productvideo/module/productvideo';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');
		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model($this->route);

		$data['videos'] = [];

		foreach ($this->model_extension_productvideo_module_productvideo->getVideos() as $video) {
			$data['videos'][] = [
				'product_id' => $video['product_id'],
				'product'    => $video['product'] ?: '#' . $video['product_id'],
				'url'        => $video['provider'] == 'vimeo' ? 'https://vimeo.com/' . $video['video_id'] : 'https://www.youtube.com/watch?v=' . $video['video_id'],
				'title'      => $video['title'],
				'sort_order' => $video['sort_order']
			];
		}

		$data['module_productvideo_position'] = $this->config->get('module_productvideo_position') ?: 'tab';
		$data['module_productvideo_status'] = $this->config->get('module_productvideo_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];
		$videos = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		foreach ((array)($this->request->post['video'] ?? []) as $row => $video) {
			$parsed = self::parse(html_entity_decode(trim((string)($video['url'] ?? '')), ENT_QUOTES, 'UTF-8'));

			if (empty($video['product_id'])) {
				$json['error']['video-' . $row . '-product'] = $this->language->get('error_product');
			}

			if (!$parsed) {
				$json['error']['video-' . $row . '-url'] = $this->language->get('error_url');
			}

			if ($parsed && !empty($video['product_id'])) {
				$videos[] = $parsed + [
					'product_id' => (int)$video['product_id'],
					'title'      => oc_substr(trim((string)($video['title'] ?? '')), 0, 128),
					'sort_order' => (int)($video['sort_order'] ?? 0)
				];
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model($this->route);
			$this->model_extension_productvideo_module_productvideo->saveVideos($videos);

			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('module_productvideo', [
				'module_productvideo_position' => ($this->request->post['module_productvideo_position'] ?? '') == 'image' ? 'image' : 'tab',
				'module_productvideo_status'   => !empty($this->request->post['module_productvideo_status'])
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Extracts the provider and video ID from a YouTube or Vimeo URL. Only IDs are stored, never embed code.
	 *
	 * @return array{provider: string, video_id: string}|array{}
	 */
	public static function parse(string $url): array {
		// youtube.com/watch?v=ID, youtu.be/ID, youtube.com/shorts/ID, youtube.com/embed/ID, youtube-nocookie.com/embed/ID
		if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $match)) {
			return ['provider' => 'youtube', 'video_id' => $match[1]];
		}

		// vimeo.com/123456789, player.vimeo.com/video/123456789
		if (preg_match('~vimeo\.com/(?:video/)?(\d{6,12})~', $url, $match)) {
			return ['provider' => 'vimeo', 'video_id' => $match[1]];
		}

		return [];
	}

	public function install(): void {
		$this->load->model($this->route);
		$this->model_extension_productvideo_module_productvideo->install();

		$this->load->model('setting/event');
		$this->model_setting_event->addEvent([
			'code'        => 'productvideo_product',
			'description' => 'Product Video: add videos to product pages',
			'trigger'     => 'catalog/view/product/product/after',
			'action'      => 'extension/productvideo/event/productvideo',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		// The video table is kept on purpose, so reinstalling doesn't lose your links.
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('productvideo_product');
	}
}
