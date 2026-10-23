<?php
namespace Opencart\Admin\Controller\Extension\WebpImages\Module;
/**
 * Class WebpImages
 *
 * WebP Images for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\WebpImages\Module
 */
class WebpImages extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/webp_images/module/webp_images');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/webp_images/module/webp_images', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/webp_images/module/webp_images.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['scan'] = $this->url->link('extension/webp_images/module/webp_images.scan', 'user_token=' . $this->session->data['user_token']);
		$data['clear'] = $this->url->link('extension/webp_images/module/webp_images.clear', 'user_token=' . $this->session->data['user_token']);

		$data['module_webp_images_status'] = $this->config->get('module_webp_images_status');
		$data['module_webp_images_format'] = $this->config->get('module_webp_images_format') ?: 'webp';
		$data['module_webp_images_quality'] = (int)$this->config->get('module_webp_images_quality') ?: 80;

		// Server support
		$gd = function_exists('gd_info') ? gd_info() : [];

		$data['support_gd'] = (bool)$gd;
		$data['support_webp'] = function_exists('imagewebp') && !empty($gd['WebP Support']);
		$data['support_avif'] = function_exists('imageavif') && !empty($gd['AVIF Support']);
		$data['gd_version'] = $gd['GD Version'] ?? '';
		$data['php_version'] = PHP_VERSION;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/webp_images/module/webp_images', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/webp_images/module/webp_images');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/webp_images/module/webp_images')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		$quality = (int)($post['module_webp_images_quality'] ?? 0);

		if ($quality < 30 || $quality > 100) {
			$json['error']['quality'] = $this->language->get('error_quality');
		}

		$format = ($post['module_webp_images_format'] ?? '') == 'avif' ? 'avif' : 'webp';

		if (!empty($post['module_webp_images_status'])) {
			if ($format == 'webp' && !function_exists('imagewebp')) {
				$json['error']['format'] = $this->language->get('error_webp');
			}

			if ($format == 'avif' && !function_exists('imageavif')) {
				$json['error']['format'] = $this->language->get('error_avif');
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_webp_images', [
				'module_webp_images_status'  => (int)!empty($post['module_webp_images_status']),
				'module_webp_images_format'  => $format,
				'module_webp_images_quality' => $quality
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Scan
	 *
	 * Counts the generated files and how many bytes they save.
	 *
	 * @return void
	 */
	public function scan(): void {
		$this->load->language('extension/webp_images/module/webp_images');

		$json = [];

		if (!$this->user->hasPermission('access', 'extension/webp_images/module/webp_images')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$files = 0;
			$original = 0;
			$converted = 0;

			foreach ($this->getGeneratedFiles() as $file) {
				$source = substr($file, 0, strrpos($file, '.'));

				if (is_file($source)) {
					$files++;
					$original += filesize($source);
					$converted += min(filesize($file), filesize($source));
				}
			}

			$json['success'] = sprintf($this->language->get('text_scan'), $files, $this->formatBytes($original), $this->formatBytes($converted), $original ? round((1 - $converted / $original) * 100) : 0);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Clear
	 *
	 * Deletes all generated WebP/AVIF files. They are created again on the next page view.
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->load->language('extension/webp_images/module/webp_images');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/webp_images/module/webp_images')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$total = $this->deleteGeneratedFiles();

			$json['success'] = sprintf($this->language->get('text_clear'), $total);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('webp_images');

		$this->model_setting_event->addEvent([
			'code'        => 'webp_images',
			'description' => 'WebP Images',
			'trigger'     => 'catalog/model/tool/image.resize/after',
			'action'      => 'extension/webp_images/module/webp_images.resize',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_webp_images', [
			'module_webp_images_status'  => 0,
			'module_webp_images_format'  => 'webp',
			'module_webp_images_quality' => 80
		]);
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('webp_images');

		$this->deleteGeneratedFiles();
	}

	/**
	 * Get Generated Files
	 *
	 * Only files this extension made: image/cache/**\/*.{jpg,jpeg,png}.{webp,avif}
	 *
	 * @return \Generator<string>
	 */
	private function getGeneratedFiles(): \Generator {
		$directory = DIR_IMAGE . 'cache/';

		if (!is_dir($directory)) {
			return;
		}

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

		foreach ($iterator as $file) {
			if ($file->isFile() && preg_match('/\.(jpe?g|png)\.(webp|avif)$/i', $file->getFilename())) {
				yield $file->getPathname();
			}
		}
	}

	/**
	 * Delete Generated Files
	 *
	 * @return int
	 */
	private function deleteGeneratedFiles(): int {
		$total = 0;

		foreach (iterator_to_array($this->getGeneratedFiles(), false) as $file) {
			if (@unlink($file)) {
				$total++;
			}
		}

		return $total;
	}

	/**
	 * Format Bytes
	 *
	 * @param int $bytes
	 *
	 * @return string
	 */
	private function formatBytes(int $bytes): string {
		if ($bytes >= 1048576) {
			return round($bytes / 1048576, 1) . ' MB';
		}

		return round($bytes / 1024) . ' KB';
	}
}
