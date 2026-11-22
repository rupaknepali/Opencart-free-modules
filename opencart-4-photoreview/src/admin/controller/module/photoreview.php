<?php
namespace Opencart\Admin\Controller\Extension\Photoreview\Module;
/**
 * Photo Reviews: customers attach photos to product reviews. Photos appear when you approve the review.
 */
class Photoreview extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/photoreview/module/photoreview';

	private array $defaults = [
		'module_photoreview_status'  => 0,
		'module_photoreview_max'     => 3,
		'module_photoreview_size'    => 5,
		'module_photoreview_thumb'   => 100,
		'module_photoreview_gallery' => 1
	];

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
		$data['reviews'] = $this->url->link('catalog/review', $token);

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_photoreview');

		foreach ($this->defaults as $key => $value) {
			$data[$key] = array_key_exists($key, $saved) ? $saved[$key] : $value;
		}

		$data['review_status'] = (bool)$this->config->get('config_review_status');

		// Latest photos, so you can see what customers send.
		$this->load->model('tool/image');

		$data['photos'] = [];

		$query = $this->db->query("SELECT p.`filename`, p.`review_id`, r.`author`, r.`status`, pd.`name` FROM `" . DB_PREFIX . "photoreview` p INNER JOIN `" . DB_PREFIX . "review` r ON (r.`review_id` = p.`review_id`) LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = p.`product_id` AND pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') ORDER BY p.`photo_id` DESC LIMIT 24");

		foreach ($query->rows as $row) {
			$data['photos'][] = [
				'thumb'   => $this->model_tool_image->resize($row['filename'], 120, 120),
				'author'  => $row['author'],
				'product' => $row['name'],
				'status'  => (int)$row['status'],
				'edit'    => $this->url->link('catalog/review.form', $token . '&review_id=' . (int)$row['review_id'])
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
			$post = $this->request->post;

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_photoreview', [
				'module_photoreview_status'  => (int)!empty($post['module_photoreview_status']),
				'module_photoreview_max'     => max(1, min(10, (int)($post['module_photoreview_max'] ?? 3))),
				'module_photoreview_size'    => max(1, min(20, (int)($post['module_photoreview_size'] ?? 5))),
				'module_photoreview_thumb'   => max(40, min(300, (int)($post['module_photoreview_thumb'] ?? 100))),
				'module_photoreview_gallery' => (int)!empty($post['module_photoreview_gallery'])
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// ---------------------------------------------------------------- review form

	/**
	 * admin/view/catalog/review_form/after: the review's photos, with a delete button each.
	 */
	public function form(string &$route, array &$args, mixed &$output): void {
		$review_id = (int)($this->request->get['review_id'] ?? 0);

		if (!$review_id) {
			return;
		}

		$query = $this->db->query("SELECT `photo_id`, `filename` FROM `" . DB_PREFIX . "photoreview` WHERE `review_id` = '" . $review_id . "' ORDER BY `photo_id`");

		if (!$query->num_rows) {
			return;
		}

		$this->load->language($this->route, 'photoreview');
		$this->load->model('tool/image');

		$delete = html_entity_decode($this->url->link($this->route . '.delete', 'user_token=' . $this->session->data['user_token']), ENT_QUOTES, 'UTF-8');

		$html = '<div class="row mb-3" id="photoreview-photos"><label class="col-sm-2 col-form-label">' . $this->language->get('photoreview_entry_photos') . '</label><div class="col-sm-10"><div class="d-flex flex-wrap gap-2">';

		foreach ($query->rows as $row) {
			$html .= '<div class="position-relative"><a href="' . HTTP_CATALOG . 'image/' . htmlspecialchars($row['filename']) . '" target="_blank"><img src="' . $this->model_tool_image->resize($row['filename'], 120, 120) . '" alt="" class="img-thumbnail"/></a>'
				. '<button type="button" data-photo="' . (int)$row['photo_id'] . '" class="btn btn-sm btn-danger position-absolute top-0 end-0" title="' . $this->language->get('photoreview_button_delete') . '"><i class="fa-solid fa-trash-can"></i></button></div>';
		}

		$html .= '</div><div class="form-text">' . $this->language->get('photoreview_help_photos') . '</div></div></div>';

		$html .= '<script type="text/javascript"><!--
$("#photoreview-photos").on("click", "button[data-photo]", function() {
    if (!confirm(' . json_encode($this->language->get('photoreview_text_confirm')) . ')) return;
    var button = $(this);
    $.post(' . json_encode($delete) . ', {photo_id: button.attr("data-photo")}, function(json) {
        if (json["success"]) button.parent().remove();
        if (json["error"]) alert(json["error"]);
    }, "json");
});
//--></script>';

		// Into the form, after the last field.
		$pos = strripos($output, '</form>');

		if ($pos !== false) {
			$output = substr_replace($output, $html, $pos, 0);
		}
	}

	public function delete(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', 'catalog/review')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->deletePhotos("`photo_id` = '" . (int)($this->request->post['photo_id'] ?? 0) . "'");

			$json['success'] = true;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * admin/model/catalog/review.deleteReview/after: a deleted review takes its photos with it.
	 */
	public function reviewDeleted(string &$route, array &$args, mixed &$output): void {
		$this->deletePhotos("`review_id` = '" . (int)($args[0] ?? 0) . "' AND `review_id` > 0");
	}

	private function deletePhotos(string $where): void {
		foreach ($this->db->query("SELECT `photo_id`, `filename` FROM `" . DB_PREFIX . "photoreview` WHERE " . $where)->rows as $row) {
			if (str_starts_with($row['filename'], 'catalog/photoreview/') && is_file(DIR_IMAGE . $row['filename'])) {
				@unlink(DIR_IMAGE . $row['filename']);
			}

			$this->db->query("DELETE FROM `" . DB_PREFIX . "photoreview` WHERE `photo_id` = '" . (int)$row['photo_id'] . "'");
		}
	}

	// ---------------------------------------------------------------- install

	private function events(): array {
		return [
			'photoreview_form'    => ['catalog/view/product/review/before', 'form'],
			'photoreview_attach'  => ['catalog/model/catalog/review.addReview/after', 'attach'],
			'photoreview_collect' => ['catalog/model/catalog/review.getReviewsByProductId/after', 'collect'],
			'photoreview_show'    => ['catalog/view/product/review_list/before', 'show'],
			'photoreview_admin'   => ['admin/view/catalog/review_form/after', 'form'],
			'photoreview_delete'  => ['admin/model/catalog/review.deleteReview/after', 'reviewDeleted']
		];
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "photoreview` (
			`photo_id` INT(11) NOT NULL AUTO_INCREMENT,
			`review_id` INT(11) NOT NULL,
			`product_id` INT(11) NOT NULL,
			`code` VARCHAR(32) NOT NULL,
			`filename` VARCHAR(255) NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`photo_id`),
			KEY `review_id` (`review_id`),
			KEY `product_id` (`product_id`),
			KEY `code` (`code`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as $code => [$trigger, $method]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Photo Reviews',
				'trigger'     => $trigger,
				'action'      => $this->route . '.' . $method,
				'status'      => 1,
				// After event/theme (500), which loads Theme Editor overrides into $code.
				'sort_order'  => str_contains($trigger, '/view/') && str_ends_with($trigger, '/before') ? 600 : 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_photoreview', $this->defaults);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (array_keys($this->events()) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		// The photos and their table stay: they're your customers' content, and come back if you reinstall.
	}
}
