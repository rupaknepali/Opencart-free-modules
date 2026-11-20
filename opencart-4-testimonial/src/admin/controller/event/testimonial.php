<?php
namespace Opencart\Admin\Controller\Extension\Testimonial\Event;
class Testimonial extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: admin/view/common/column_left/before. Catalog > Testimonials.
	 */
	public function menu(string &$route, array &$args): void {
		if (empty($args['menus']) || !$this->user->hasPermission('access', 'extension/testimonial/module/testimonial')) {
			return;
		}

		$this->load->language('extension/testimonial/module/testimonial');

		foreach ($args['menus'] as &$menu) {
			if (($menu['id'] ?? '') == 'menu-catalog') {
				$menu['children'][] = [
					'name'     => $this->language->get('text_testimonials'),
					'href'     => $this->url->link('extension/testimonial/module/testimonial.manage', 'user_token=' . $this->session->data['user_token']),
					'children' => []
				];

				break;
			}
		}
	}
}
