<?php
namespace Opencart\Admin\Controller\Extension\Requestquote\Event;
/**
 * Request a Quote: Sales > Quote Requests in the admin menu, with the number of new requests.
 */
class Requestquote extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: admin/view/common/column_left/before
	 */
	public function menu(string &$route, array &$args): void {
		if (!$this->user->hasPermission('access', 'extension/requestquote/module/requestquote') || empty($args['menus'])) {
			return;
		}

		$this->load->language('extension/requestquote/module/requestquote');
		$this->load->model('extension/requestquote/module/requestquote');

		$new = $this->model_extension_requestquote_module_requestquote->getTotalRequests('new');

		$name = $this->language->get('rq_menu') . ($new ? ' <span class="badge bg-danger">' . $new . '</span>' : '');

		foreach ($args['menus'] as &$menu) {
			if (($menu['id'] ?? '') == 'menu-sale') {
				$menu['children'][] = [
					'name'     => $name,
					'href'     => $this->url->link('extension/requestquote/module/requestquote', 'user_token=' . $this->session->data['user_token'] . ($new ? '&filter=new' : '')),
					'children' => []
				];

				break;
			}
		}
	}
}
