<?php
namespace Opencart\Catalog\Controller\Extension\CustomMenuLinks\Module;

class CustomMenuLinks extends \Opencart\System\Engine\Controller {
    public function menu(string &$route, array &$data, mixed &$output): void {
        if ($this->config->get('module_custom_menu_links_status')) {
            $menu_items = $this->config->get('module_custom_menu_links_items');

            if ($menu_items) {
                // Sort menu items by sort order
                usort($menu_items, function($a, $b) {
                    return $a['sort_order'] <=> $b['sort_order'];
                });

                foreach ($menu_items as $item) {
                    $data['categories'][] = [
                        'name'     => $item['title'],
                        'children' => [],
                        'column'   => 1,
                        'href'     => $item['link']
                    ];
                }
            }
        }
    }
}
