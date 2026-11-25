<?php
namespace Opencart\Admin\Controller\Extension\Reviewreply\Module;
/**
 * Review Replies: settings, a reply box on the review form and an email to the reviewer.
 */
class Reviewreply extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/reviewreply/module/reviewreply';

	/**
	 * The extension's texts, loaded with a prefix so they never overwrite the texts of the review form.
	 */
	private function lang(): array {
		$text = [];

		foreach ($this->load->language($this->route, 'reviewreply') as $key => $value) {
			$text[str_starts_with($key, 'reviewreply_') ? substr($key, 12) : $key] = $value;
		}

		return $text;
	}

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

		$defaults = [
			'status'  => 0,
			'author'  => '',
			'heading' => $this->language->get('text_default_heading'),
			'notify'  => 1
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_reviewreply');

		foreach ($defaults as $key => $value) {
			$data['module_reviewreply_' . $key] = array_key_exists('module_reviewreply_' . $key, $saved) ? $saved['module_reviewreply_' . $key] : $value;
		}

		$data['store_name'] = $this->config->get('config_name');

		// Approved reviews that still need an answer, lowest rating first.
		$data['waiting'] = [];

		$query = $this->db->query("SELECT r.`review_id`, r.`author`, r.`rating`, r.`text`, r.`date_added`, pd.`name` FROM `" . DB_PREFIX . "review` r LEFT JOIN `" . DB_PREFIX . "reviewreply` rr ON (rr.`review_id` = r.`review_id`) LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = r.`product_id` AND pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE r.`status` = '1' AND (rr.`review_id` IS NULL OR rr.`reply` = '') ORDER BY r.`rating` ASC, r.`date_added` DESC LIMIT 25");

		foreach ($query->rows as $row) {
			$data['waiting'][] = [
				'product' => $row['name'],
				'author'  => $row['author'],
				'rating'  => (int)$row['rating'],
				'text'    => oc_substr($row['text'], 0, 120) . (oc_strlen($row['text']) > 120 ? '…' : ''),
				'date'    => date($this->language->get('date_format_short'), strtotime($row['date_added'])),
				'edit'    => $this->url->link('catalog/review.form', $token . '&review_id=' . $row['review_id'])
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_reviewreply', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: admin/view/catalog/review_form/after. Adds the reply box under the review.
	 */
	public function reviewForm(string &$route, array &$args, string &$output): void {
		$review_id = (int)($this->request->get['review_id'] ?? 0);

		$row = $review_id ? $this->db->query("SELECT rr.*, r.`customer_id` FROM `" . DB_PREFIX . "review` r LEFT JOIN `" . DB_PREFIX . "reviewreply` rr ON (rr.`review_id` = r.`review_id`) WHERE r.`review_id` = '" . $review_id . "'")->row : [];

		$text = $this->lang();

		$can_email = !empty($row['customer_id']);
		$notify = $can_email && !empty($this->config->get('module_reviewreply_notify')) && empty($row['reply']);

		$html = '';

		if (!$this->config->get('module_reviewreply_status')) {
			$html .= '<div class="alert alert-warning">' . $text['text_module_off'] . '</div>';
		}

		$html .= '<div class="row mb-3"><label for="input-reviewreply" class="col-sm-2 col-form-label">' . $text['entry_reply'] . '</label><div class="col-sm-10">';
		$html .= '<textarea name="reviewreply[reply]" rows="4" id="input-reviewreply" class="form-control">' . htmlspecialchars((string)($row['reply'] ?? ''), ENT_QUOTES, 'UTF-8') . '</textarea>';
		$html .= '<div class="form-text">' . $text['help_reply'];

		if (!empty($row['date_modified'])) {
			$html .= ' ' . sprintf($text['text_replied'], date($this->language->get('datetime_format'), strtotime($row['date_modified'])));
		}

		$html .= '</div>';

		if ($can_email) {
			$html .= '<div class="form-check mt-2"><input type="hidden" name="reviewreply[notify]" value="0"/><input type="checkbox" name="reviewreply[notify]" value="1" id="input-reviewreply-notify" class="form-check-input"' . ($notify ? ' checked' : '') . '/><label for="input-reviewreply-notify" class="form-check-label">' . $text['entry_reply_notify'] . '</label></div>';
		} elseif ($review_id) {
			$html .= '<div class="form-text">' . $text['text_no_email'] . '</div>';
		}

		$html .= '</div></div>';

		if (($position = strpos($output, '<input type="hidden" name="review_id"')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * Events: admin/model/catalog/review.addReview/after and review.editReview/after.
	 */
	public function reviewSave(string &$route, array &$args, mixed &$output): void {
		if (str_ends_with($route, 'addReview')) {
			$review_id = (int)$output;
			$data = $args[0] ?? [];
		} else {
			$review_id = (int)($args[0] ?? 0);
			$data = $args[1] ?? [];
		}

		if (!$review_id || !isset($data['reviewreply'])) {
			return;
		}

		// Request data arrives HTML-encoded: store plain text, escape when it's shown.
		$reply = trim(html_entity_decode((string)($data['reviewreply']['reply'] ?? ''), ENT_QUOTES, 'UTF-8'));
		$reply = strip_tags($reply);

		$old = $this->db->query("SELECT `reply` FROM `" . DB_PREFIX . "reviewreply` WHERE `review_id` = '" . $review_id . "'")->row;

		if ($reply === '') {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "reviewreply` WHERE `review_id` = '" . $review_id . "'");

			return;
		}

		if (($old['reply'] ?? null) === $reply) {
			return;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "reviewreply` SET `review_id` = '" . $review_id . "', `reply` = '" . $this->db->escape($reply) . "', `user_id` = '" . (int)$this->user->getId() . "', `date_added` = NOW(), `date_modified` = NOW() ON DUPLICATE KEY UPDATE `reply` = VALUES(`reply`), `user_id` = VALUES(`user_id`), `date_modified` = NOW()");

		if (!empty($data['reviewreply']['notify'])) {
			$this->notify($review_id, $reply);
		}
	}

	/**
	 * Event: admin/model/catalog/review.deleteReview/after
	 */
	public function reviewDelete(string &$route, array &$args, mixed &$output): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "reviewreply` WHERE `review_id` = '" . (int)($args[0] ?? 0) . "'");
	}

	/**
	 * Emails the reviewer (customers only; guests leave no email address on a review).
	 */
	private function notify(int $review_id, string $reply): void {
		if (!$this->config->get('config_mail_engine')) {
			return;
		}

		$review = $this->db->query("SELECT r.*, c.`email`, c.`firstname`, pd.`name` AS `product` FROM `" . DB_PREFIX . "review` r LEFT JOIN `" . DB_PREFIX . "customer` c ON (c.`customer_id` = r.`customer_id`) LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = r.`product_id` AND pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE r.`review_id` = '" . $review_id . "'")->row;

		if (!$review || empty($review['email']) || !filter_var($review['email'], FILTER_VALIDATE_EMAIL)) {
			return;
		}

		$text = $this->lang();
		$store = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$product = html_entity_decode((string)$review['product'], ENT_QUOTES, 'UTF-8');

		$data = [
			'intro'       => sprintf($text['text_mail_intro'], html_entity_decode($review['firstname'], ENT_QUOTES, 'UTF-8'), $product),
			'reply'       => $reply,
			'review'      => html_entity_decode($review['text'], ENT_QUOTES, 'UTF-8'),
			'text_review' => $text['text_mail_review'],
			'text_link'   => $text['text_mail_link'],
			'link'        => HTTP_CATALOG . 'index.php?route=product/product&product_id=' . (int)$review['product_id'],
			'store'       => $store
		];

		$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), [
			'parameter'     => $this->config->get('config_mail_parameter'),
			'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'     => $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
		]);

		$mail->setTo($review['email']);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($store);
		$mail->setSubject(sprintf($text['text_mail_subject'], $store, $product));
		$mail->setHtml($this->load->view('extension/reviewreply/module/reviewreply_mail', $data));

		try {
			$mail->send();
		} catch (\Exception $e) {
			// The reply is saved either way; a mail problem only goes to the error log.
			$this->log->write('Review Replies: ' . $e->getMessage());
		}
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "reviewreply` (
			`review_id` INT(11) NOT NULL,
			`reply` TEXT NOT NULL,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`review_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Review Replies: ' . $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach ($this->events() as $event) {
			$this->model_setting_event->deleteEventByCode($event[0]);
		}

		// The table is kept, so replies come back after a reinstall.
	}

	private function events(): array {
		return [
			['reviewreply_form', 'admin/view/catalog/review_form/after', 'extension/reviewreply/module/reviewreply.reviewForm', 'reply box on the review form'],
			['reviewreply_add', 'admin/model/catalog/review.addReview/after', 'extension/reviewreply/module/reviewreply.reviewSave', 'save the reply of a new review'],
			['reviewreply_edit', 'admin/model/catalog/review.editReview/after', 'extension/reviewreply/module/reviewreply.reviewSave', 'save the reply of a review'],
			['reviewreply_delete', 'admin/model/catalog/review.deleteReview/after', 'extension/reviewreply/module/reviewreply.reviewDelete', 'remove the reply of a deleted review'],
			['reviewreply_reviews', 'catalog/model/catalog/review.getReviewsByProductId/after', 'extension/reviewreply/module/reviewreply.reviews', 'add review IDs and replies to product reviews'],
			['reviewreply_list', 'catalog/view/product/review_list/after', 'extension/reviewreply/module/reviewreply.reviewList', 'replies under product reviews']
		];
	}
}
