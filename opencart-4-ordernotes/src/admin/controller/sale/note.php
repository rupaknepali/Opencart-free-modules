<?php
namespace Opencart\Admin\Controller\Extension\Ordernotes\Sale;
/**
 * Staff Order Notes on the order pages. A route of its own, so order staff can be given
 * access to notes without access to the extension settings.
 */
class Note extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/ordernotes/sale/note';
	private string $model = 'extension/ordernotes/module/ordernotes';

	/**
	 * The notes list (also returned after every add, pin and delete).
	 */
	private function renderList(int $order_id): string {
		$this->load->language($this->model, 'on');
		$this->load->model($this->model);

		$data['notes'] = [];

		foreach ($this->model_extension_ordernotes_module_ordernotes->getNotes($order_id) as $note) {
			$data['notes'][] = [
				'ordernote_id' => (int)$note['ordernote_id'],
				'note'         => $note['note'],
				'pinned'       => (int)$note['pinned'],
				'by'           => sprintf($this->language->get('on_text_by'), $note['username'], date($this->language->get('datetime_format'), strtotime($note['date_added']))),
				// Authors delete their own notes; users who can change the extension settings can delete any note.
				'delete'       => (int)$note['user_id'] == (int)$this->user->getId() || $this->user->hasPermission('modify', $this->model)
			];
		}

		$data['can_modify'] = $this->user->hasPermission('modify', $this->route);
		$data['text_none'] = $this->language->get('on_text_none');
		$data['text_pinned'] = $this->language->get('on_text_pinned');
		$data['text_pin'] = $this->language->get('on_text_pin');
		$data['text_unpin'] = $this->language->get('on_text_unpin');
		$data['button_delete'] = $this->language->get('button_delete');

		return $this->load->view('extension/ordernotes/module/ordernotes_list', $data);
	}

	/**
	 * Event: admin/view/sale/order_info/after. Adds the notes card above the history card
	 * and the pinned note above the first card.
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		$this->load->model($this->model);

		$settings = $this->model_extension_ordernotes_module_ordernotes->getSettings();
		$order_id = (int)($args['order_id'] ?? 0);

		if (!$settings['status'] || !$order_id) {
			return;
		}

		$text = $this->load->language($this->model, 'on');

		$token = 'user_token=' . $this->session->data['user_token'];

		$data = [
			'order_id'     => $order_id,
			'list'         => $this->renderList($order_id),
			'can_modify'   => $this->user->hasPermission('modify', $this->route),
			'add'          => html_entity_decode($this->url->link($this->route . '.add', $token), ENT_QUOTES, 'UTF-8'),
			'pin'          => html_entity_decode($this->url->link($this->route . '.pin', $token), ENT_QUOTES, 'UTF-8'),
			'delete'       => html_entity_decode($this->url->link($this->route . '.delete', $token), ENT_QUOTES, 'UTF-8'),
			'text_notes'   => $text['on_text_notes'],
			'text_private' => $text['on_text_private'],
			'text_pin'     => $text['on_text_pin'],
			'text_confirm' => $text['on_text_confirm'],
			'entry_note'   => $text['on_entry_note'],
			'button_add'   => $text['on_button_add']
		];

		$card = $this->load->view('extension/ordernotes/module/ordernotes_card', $data);

		// Put the card above the History card; if a theme changed the page, add it at the top of the content.
		$marker = strpos($output, 'fa-solid fa-comment"></i> ');
		$start = $marker !== false ? strrpos(substr($output, 0, $marker), '<div class="card mb-3">') : false;

		if ($start !== false) {
			$output = substr_replace($output, $card, $start, 0);
		} else {
			$output = str_replace('<div id="content">', '<div id="content">' . $card, $output);
		}

		if ($settings['pinned']) {
			foreach ($this->model_extension_ordernotes_module_ordernotes->getNotes($order_id) as $note) {
				if ($note['pinned']) {
					$alert = $this->load->view('extension/ordernotes/module/ordernotes_pinned', [
						'note'        => $note['note'],
						'by'          => sprintf($text['on_text_by'], $note['username'], date($this->language->get('datetime_format'), strtotime($note['date_added']))),
						'text_pinned' => $text['on_text_pinned']
					]);

					$first = strpos($output, '<div class="card mb-3">');

					if ($first !== false) {
						$output = substr_replace($output, $alert, $first, 0);
					}

					break;
				}
			}
		}
	}

	/**
	 * Event: admin/view/sale/order_list/after. A note badge next to the order ID.
	 */
	public function orderList(string &$route, array &$args, string &$output): void {
		$this->load->model($this->model);

		$settings = $this->model_extension_ordernotes_module_ordernotes->getSettings();

		if (!$settings['status'] || !$settings['list'] || empty($args['orders'])) {
			return;
		}

		$summaries = $this->model_extension_ordernotes_module_ordernotes->getSummaries(array_column($args['orders'], 'order_id'));

		if (!$summaries) {
			return;
		}

		$text = $this->load->language($this->model, 'on');

		$badges = [];

		foreach ($summaries as $order_id => $row) {
			$latest = oc_strlen($row['note']) > 120 ? oc_substr($row['note'], 0, 117) . '...' : $row['note'];

			$badges[$order_id] = [
				'total' => (int)$row['total'],
				'title' => sprintf($text['on_text_count'], (int)$row['total'], $row['username'] . ': ' . $latest)
			];
		}

		$output .= $this->load->view('extension/ordernotes/module/ordernotes_badges', ['badges' => json_encode($badges)]);
	}

	private function json(array $json): void {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * POST order_id, note, pinned.
	 */
	public function add(): void {
		$this->load->language($this->model);
		$this->load->model($this->model);
		$this->load->model('sale/order');

		$order_id = (int)($this->request->post['order_id'] ?? 0);
		// Request data arrives HTML-encoded; store the plain text and escape it when it is shown.
		$note = trim(html_entity_decode((string)($this->request->post['note'] ?? ''), ENT_QUOTES, 'UTF-8'));

		if (!$this->user->hasPermission('modify', $this->route) || !$this->model_extension_ordernotes_module_ordernotes->getSettings()['status'] || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$this->json(['error' => $this->language->get('error_permission')]);

			return;
		}

		if (!$this->model_sale_order->getOrder($order_id)) {
			$this->json(['error' => $this->language->get('error_order')]);

			return;
		}

		if (oc_strlen($note) < 1 || oc_strlen($note) > 2000) {
			$this->json(['error' => $this->language->get('error_note')]);

			return;
		}

		$this->model_extension_ordernotes_module_ordernotes->addNote($order_id, (int)$this->user->getId(), (string)$this->user->getUserName(), $note, !empty($this->request->post['pinned']));

		$this->json(['success' => $this->language->get('text_added'), 'list' => $this->renderList($order_id)]);
	}

	/**
	 * POST ordernote_id, pinned (1 or 0).
	 */
	public function pin(): void {
		$this->load->language($this->model);
		$this->load->model($this->model);

		$note = $this->model_extension_ordernotes_module_ordernotes->getNote((int)($this->request->post['ordernote_id'] ?? 0));

		if (!$this->user->hasPermission('modify', $this->route) || !$note || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$this->json(['error' => $this->language->get('error_permission')]);

			return;
		}

		$this->model_extension_ordernotes_module_ordernotes->setPinned((int)$note['ordernote_id'], (int)$note['order_id'], !empty($this->request->post['pinned']));

		$this->json(['success' => $this->language->get('text_success'), 'list' => $this->renderList((int)$note['order_id'])]);
	}

	/**
	 * POST ordernote_id. Authors can delete their own notes; users who can change the extension settings can delete any note.
	 */
	public function delete(): void {
		$this->load->language($this->model);
		$this->load->model($this->model);

		$note = $this->model_extension_ordernotes_module_ordernotes->getNote((int)($this->request->post['ordernote_id'] ?? 0));

		if (!$this->user->hasPermission('modify', $this->route) || !$note || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$this->json(['error' => $this->language->get('error_permission')]);

			return;
		}

		if ((int)$note['user_id'] != (int)$this->user->getId() && !$this->user->hasPermission('modify', $this->model)) {
			$this->json(['error' => $this->language->get('error_note_owner')]);

			return;
		}

		$this->model_extension_ordernotes_module_ordernotes->deleteNote((int)$note['ordernote_id']);

		$this->json(['success' => $this->language->get('text_deleted'), 'list' => $this->renderList((int)$note['order_id'])]);
	}
}
