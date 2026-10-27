<?php
namespace Opencart\Admin\Controller\Extension\AiShopAssistant\Module;
/**
 * Class AiShopAssistant
 *
 * AI Shop Assistant for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\AiShopAssistant\Module
 */
class AiShopAssistant extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/ai_shop_assistant/module/ai_shop_assistant');

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
			'href' => $this->url->link('extension/ai_shop_assistant/module/ai_shop_assistant', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/ai_shop_assistant/module/ai_shop_assistant.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$settings = [
			'status'        => 0,
			'api_key'       => '',
			'model'         => \Opencart\System\Library\Extension\AiShopAssistant\Claude::MODELS[0],
			'title'         => '',
			'welcome'       => '',
			'color'         => '#1f6feb',
			'facts'         => '',
			'session_limit' => 20,
			'ip_limit'      => 60,
			'retention'     => 90
		];

		foreach ($settings as $key => $default) {
			$value = $this->config->get('module_ai_shop_assistant_' . $key);

			$data['module_ai_shop_assistant_' . $key] = ($value === null || $value === '') ? $default : $value;
		}

		$data['models'] = \Opencart\System\Library\Extension\AiShopAssistant\Claude::MODELS;

		$this->load->model('extension/ai_shop_assistant/module/ai_shop_assistant');

		$data['statistics'] = $this->model_extension_ai_shop_assistant_module_ai_shop_assistant->getStatistics(30);

		$data['list'] = $this->getList();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ai_shop_assistant/module/ai_shop_assistant', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('extension/ai_shop_assistant/module/ai_shop_assistant');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		$page = max(1, (int)($this->request->get['page'] ?? 1));

		$limit = 25;

		$this->load->model('extension/ai_shop_assistant/module/ai_shop_assistant');

		$data['logs'] = [];

		foreach ($this->model_extension_ai_shop_assistant_module_ai_shop_assistant->getLogs(($page - 1) * $limit, $limit) as $log) {
			$data['logs'][] = [
				'question'     => $log['question'],
				'products'     => $log['products'],
				'conversation' => substr($log['session'], 0, 8),
				'tokens'       => (int)$log['input_tokens'] + (int)$log['output_tokens'],
				'date_added'   => date($this->language->get('datetime_format'), strtotime($log['date_added']))
			];
		}

		$total = $this->model_extension_ai_shop_assistant_module_ai_shop_assistant->getTotalLogs();

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/ai_shop_assistant/module/ai_shop_assistant.list', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		return $this->load->view('extension/ai_shop_assistant/module/ai_shop_assistant_list', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/ai_shop_assistant/module/ai_shop_assistant');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ai_shop_assistant/module/ai_shop_assistant')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		$post['module_ai_shop_assistant_api_key'] = trim((string)($post['module_ai_shop_assistant_api_key'] ?? ''));

		if ($post['module_ai_shop_assistant_api_key'] !== '' && !str_starts_with($post['module_ai_shop_assistant_api_key'], 'sk-ant-')) {
			$json['error']['api-key'] = $this->language->get('error_api_key');
		}

		if (!empty($post['module_ai_shop_assistant_status']) && $post['module_ai_shop_assistant_api_key'] === '') {
			$json['error']['api-key'] = $this->language->get('error_no_key');
		}

		if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)($post['module_ai_shop_assistant_color'] ?? ''))) {
			$post['module_ai_shop_assistant_color'] = '#1f6feb';
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_ai_shop_assistant_session_limit'] = max(1, (int)($post['module_ai_shop_assistant_session_limit'] ?? 20));
			$post['module_ai_shop_assistant_ip_limit'] = max(1, (int)($post['module_ai_shop_assistant_ip_limit'] ?? 60));
			$post['module_ai_shop_assistant_retention'] = max(1, (int)($post['module_ai_shop_assistant_retention'] ?? 90));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ai_shop_assistant', $post);

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

		$this->load->model('extension/ai_shop_assistant/module/ai_shop_assistant');

		$this->model_extension_ai_shop_assistant_module_ai_shop_assistant->install();

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('ai_shop_assistant_footer');

		$this->model_setting_event->addEvent([
			'code'        => 'ai_shop_assistant_footer',
			'description' => 'AI Shop Assistant',
			'trigger'     => 'catalog/view/common/footer/after',
			'action'      => 'extension/ai_shop_assistant/module/ai_shop_assistant.footer',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('ai_shop_assistant');
		$this->model_setting_cron->addCron('ai_shop_assistant', 'Deletes old AI Shop Assistant questions.', 'day', 'extension/ai_shop_assistant/module/ai_shop_assistant.cron', true);
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

		$this->model_setting_event->deleteEventByCode('ai_shop_assistant_footer');

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('ai_shop_assistant');

		$this->load->model('extension/ai_shop_assistant/module/ai_shop_assistant');

		$this->model_extension_ai_shop_assistant_module_ai_shop_assistant->uninstall();
	}
}
