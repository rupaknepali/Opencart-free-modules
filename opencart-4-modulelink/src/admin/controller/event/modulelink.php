<?php
namespace Opencart\Admin\Controller\Extension\Modulelink\Event;
/**
 * Module Link in Admin Menu: Extensions > Modules, one click to the module list.
 */
class Modulelink extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: admin/view/common/column_left/before
	 */
	public function menu(string &$route, array &$args): void {
		if (!$this->config->get('module_modulelink_status') || empty($args['menus']) || !$this->user->hasPermission('access', 'marketplace/extension')) {
			return;
		}

		$this->load->language('extension/modulelink/module/modulelink');

		$link = [
			'name'     => $this->language->get('ml_menu'),
			'href'     => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'),
			'children' => []
		];

		foreach ($args['menus'] as &$menu) {
			if (($menu['id'] ?? '') != 'menu-extension') {
				continue;
			}

			// Right after Extensions > Extensions, or at the end if a theme changed the menu.
			$position = count($menu['children']);

			foreach ($menu['children'] as $key => $child) {
				if (str_contains(html_entity_decode((string)$child['href'], ENT_QUOTES, 'UTF-8'), 'route=marketplace/extension&') && !str_contains((string)$child['href'], 'type=')) {
					$position = $key + 1;
				}
			}

			array_splice($menu['children'], $position, 0, [$link]);

			break;
		}
	}
}
