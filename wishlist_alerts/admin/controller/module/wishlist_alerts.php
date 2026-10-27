<?php
namespace Opencart\Admin\Controller\Extension\WishlistAlerts\Module;
/**
 * Class WishlistAlerts
 *
 * Wishlist Alerts for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\WishlistAlerts\Module
 */
class WishlistAlerts extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'wishlist_alerts_reward' => ['catalog/model/checkout/order.addHistory/after', 'reward']
	];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/wishlist_alerts/module/wishlist_alerts');

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
			'href' => $this->url->link('extension/wishlist_alerts/module/wishlist_alerts', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/wishlist_alerts/module/wishlist_alerts.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['cron'] = $this->url->link('marketplace/cron', 'user_token=' . $this->session->data['user_token']);

		$data['module_wishlist_alerts_status'] = $this->config->get('module_wishlist_alerts_status');
		$data['module_wishlist_alerts_price_drop'] = $this->config->get('module_wishlist_alerts_price_drop');
		$data['module_wishlist_alerts_min_drop'] = (int)$this->config->get('module_wishlist_alerts_min_drop') ?: 10;
		$data['module_wishlist_alerts_restock'] = $this->config->get('module_wishlist_alerts_restock');
		$data['module_wishlist_alerts_newsletter'] = $this->config->get('module_wishlist_alerts_newsletter');
		$data['module_wishlist_alerts_reward'] = $this->config->get('module_wishlist_alerts_reward');
		$data['module_wishlist_alerts_cooldown'] = (int)$this->config->get('module_wishlist_alerts_cooldown') ?: 7;
		$data['module_wishlist_alerts_max_emails'] = (int)$this->config->get('module_wishlist_alerts_max_emails') ?: 100;
		$data['module_wishlist_alerts_subject'] = $this->config->get('module_wishlist_alerts_subject');
		$data['module_wishlist_alerts_message'] = $this->config->get('module_wishlist_alerts_message');

		$this->load->model('extension/wishlist_alerts/module/wishlist_alerts');

		$data['statistics'] = $this->model_extension_wishlist_alerts_module_wishlist_alerts->getStatistics();

		$data['wished'] = [];

		foreach ($this->model_extension_wishlist_alerts_module_wishlist_alerts->getMostWished(20) as $result) {
			$data['wished'][] = [
				'name'     => $result['name'],
				'model'    => $result['model'],
				'price'    => $this->currency->format((float)$result['price'], $this->config->get('config_currency')),
				'quantity' => (int)$result['quantity'],
				'total'    => (int)$result['total'],
				'edit'     => $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'])
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/wishlist_alerts/module/wishlist_alerts', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/wishlist_alerts/module/wishlist_alerts');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/wishlist_alerts/module/wishlist_alerts')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (oc_strlen(trim($post['module_wishlist_alerts_subject'] ?? '')) < 3) {
			$json['error']['subject'] = $this->language->get('error_subject');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_wishlist_alerts_min_drop'] = min(90, max(1, (int)($post['module_wishlist_alerts_min_drop'] ?? 10)));
			$post['module_wishlist_alerts_cooldown'] = max(1, (int)($post['module_wishlist_alerts_cooldown'] ?? 7));
			$post['module_wishlist_alerts_max_emails'] = max(1, (int)($post['module_wishlist_alerts_max_emails'] ?? 100));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_wishlist_alerts', $post);

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

		$this->load->model('extension/wishlist_alerts/module/wishlist_alerts');

		$this->model_extension_wishlist_alerts_module_wishlist_alerts->install();

		$this->load->model('setting/event');

		foreach ($this->events as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Wishlist Alerts & Reward Points',
				'trigger'     => $event[0],
				'action'      => 'extension/wishlist_alerts/module/wishlist_alerts.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('wishlist_alerts');
		$this->model_setting_cron->addCron('wishlist_alerts', 'Emails customers when wishlist products drop in price or come back in stock.', 'day', 'extension/wishlist_alerts/module/wishlist_alerts.cron', true);

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_wishlist_alerts', [
			'module_wishlist_alerts_status'     => 0,
			'module_wishlist_alerts_price_drop' => 1,
			'module_wishlist_alerts_min_drop'   => 10,
			'module_wishlist_alerts_restock'    => 1,
			'module_wishlist_alerts_newsletter' => 1,
			'module_wishlist_alerts_reward'     => 0,
			'module_wishlist_alerts_cooldown'   => 7,
			'module_wishlist_alerts_max_emails' => 100,
			'module_wishlist_alerts_subject'    => htmlspecialchars('Good news about your wishlist at {store_name}', ENT_COMPAT, 'UTF-8'),
			'module_wishlist_alerts_message'    => htmlspecialchars('Hi {firstname}, some products you saved are now cheaper or back in stock.', ENT_COMPAT, 'UTF-8')
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

		foreach (array_keys($this->events) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('wishlist_alerts');

		$this->load->model('extension/wishlist_alerts/module/wishlist_alerts');

		$this->model_extension_wishlist_alerts_module_wishlist_alerts->uninstall();
	}
}
