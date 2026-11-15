<?php
namespace Opencart\Admin\Controller\Extension\Productqa\Event;
/**
 * Product Questions & Answers: adds Catalog > Product Questions to the admin menu.
 */
class Productqa extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: admin/view/common/column_left/before
	 */
	public function menu(string &$route, array &$args): void {
		if (!$this->user->hasPermission('access', 'extension/productqa/module/productqa') || empty($args['menus'])) {
			return;
		}

		$this->load->language('extension/productqa/module/productqa');
		$this->load->model('extension/productqa/module/productqa');

		$unanswered = $this->model_extension_productqa_module_productqa->getTotalQuestions(['filter' => 'unanswered']);

		$name = $this->language->get('pqa_menu');

		if ($unanswered) {
			$name .= ' <span class="badge bg-danger">' . $unanswered . '</span>';
		}

		foreach ($args['menus'] as &$menu) {
			if (($menu['id'] ?? '') == 'menu-catalog') {
				$menu['children'][] = [
					'name'     => $name,
					'href'     => $this->url->link('extension/productqa/module/productqa', 'user_token=' . $this->session->data['user_token'] . ($unanswered ? '&filter=unanswered' : '')),
					'children' => []
				];

				break;
			}
		}
	}
}
