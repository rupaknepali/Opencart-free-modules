<?php
namespace Opencart\Admin\Controller\Extension\AiProductFeed\Module;
/**
 * Class AiProductFeed
 *
 * AI Shopping Product Feed for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\AiProductFeed\Module
 */
class AiProductFeed extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/ai_product_feed/module/ai_product_feed');

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
			'href' => $this->url->link('extension/ai_product_feed/module/ai_product_feed', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/ai_product_feed/module/ai_product_feed.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['cron'] = $this->url->link('marketplace/cron', 'user_token=' . $this->session->data['user_token']);

		$data['feed_url'] = HTTP_CATALOG . 'index.php?route=extension/ai_product_feed/module/ai_product_feed';
		$data['preview_url'] = $data['feed_url'] . '&preview=1';

		$data['module_ai_product_feed_status'] = $this->config->get('module_ai_product_feed_status');
		$data['module_ai_product_feed_item_id'] = $this->config->get('module_ai_product_feed_item_id') ?: 'product_id';
		$data['module_ai_product_feed_currency'] = $this->config->get('module_ai_product_feed_currency') ?: $this->config->get('config_currency');
		$data['module_ai_product_feed_tax'] = $this->config->get('module_ai_product_feed_tax');
		$data['module_ai_product_feed_out_of_stock'] = $this->config->get('module_ai_product_feed_out_of_stock');
		$data['module_ai_product_feed_brand_fallback'] = $this->config->get('module_ai_product_feed_brand_fallback');
		$data['module_ai_product_feed_return'] = (int)$this->config->get('module_ai_product_feed_return');
		$data['module_ai_product_feed_privacy'] = (int)$this->config->get('module_ai_product_feed_privacy');
		$data['module_ai_product_feed_terms'] = (int)$this->config->get('module_ai_product_feed_terms');

		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$this->load->model('catalog/information');

		$data['informations'] = $this->model_catalog_information->getInformations();

		// Written by the catalog side each time the feed is built.
		$status_file = DIR_STORAGE . 'ai_product_feed/feed-0.json';

		$data['generated'] = '';

		if (is_file($status_file)) {
			$status = json_decode((string)file_get_contents($status_file), true);

			if (is_array($status)) {
				$data['generated'] = sprintf($this->language->get('text_generated'), $status['generated'], (int)$status['items'], round((int)$status['size'] / 1024, 1));
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ai_product_feed/module/ai_product_feed', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/ai_product_feed/module/ai_product_feed');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ai_product_feed/module/ai_product_feed')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!in_array($post['module_ai_product_feed_item_id'] ?? '', ['product_id', 'model', 'sku'])) {
			$post['module_ai_product_feed_item_id'] = 'product_id';
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ai_product_feed', $post);

			$json['success'] = $this->language->get('text_success');
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

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('ai_product_feed');
		$this->model_setting_cron->addCron('ai_product_feed', 'Rebuilds the AI shopping product feed (OpenAI product feed format).', 'hour', 'extension/ai_product_feed/module/ai_product_feed.cron', true);

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_ai_product_feed', [
			'module_ai_product_feed_status'         => 0,
			'module_ai_product_feed_item_id'        => 'product_id',
			'module_ai_product_feed_currency'       => $this->config->get('config_currency'),
			'module_ai_product_feed_tax'            => 0,
			'module_ai_product_feed_out_of_stock'   => 1,
			'module_ai_product_feed_brand_fallback' => 1,
			'module_ai_product_feed_return'         => 0,
			'module_ai_product_feed_privacy'        => 0,
			'module_ai_product_feed_terms'          => 0
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

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('ai_product_feed');

		foreach (glob(DIR_STORAGE . 'ai_product_feed/*') ?: [] as $file) {
			unlink($file);
		}

		if (is_dir(DIR_STORAGE . 'ai_product_feed')) {
			rmdir(DIR_STORAGE . 'ai_product_feed');
		}
	}
}
