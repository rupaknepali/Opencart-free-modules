<?php
namespace Opencart\Admin\Controller\Extension\Webocreationtheme\Theme;
/**
 * Webocreation theme settings: colors, typography, layout, header, footer and custom CSS, per store, with a live preview.
 */
class Webocreationtheme extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/webocreationtheme/theme/webocreationtheme';

	public static function defaults(): array {
		return [
			'status' => 0, 'scheme' => 'light', 'preset' => 'opencart',
			'color_primary' => '#229ac8', 'color_primary_text' => '#ffffff', 'color_secondary' => '#f0ad4e',
			'color_background' => '#ffffff', 'color_surface' => '#ffffff', 'color_text' => '#333333', 'color_muted' => '#6c757d',
			'color_link' => '#229ac8', 'color_link_hover' => '#1a7ba1', 'color_border' => '#dddddd',
			'color_topbar_bg' => '#eeeeee', 'color_topbar_text' => '#555555', 'color_header_bg' => '#ffffff', 'color_header_text' => '#333333',
			'color_menu_bg' => '#229ac8', 'color_menu_text' => '#ffffff', 'color_footer_bg' => '#303030', 'color_footer_text' => '#e2e2e2',
			'color_catbar_bg' => '#ffffff', 'color_catbar_text' => '#333333',
			'color_price' => '#333333', 'color_special' => '#dc3545',
			'font_source' => 'google', 'font_body' => 'Inter', 'font_heading' => 'Inter', 'font_size' => 15, 'line_height' => '1.6',
			'heading_weight' => 700, 'heading_spacing' => '0', 'heading_case' => 'none', 'button_case' => 'none',
			'container' => '1320', 'radius' => 6, 'button_radius' => 6, 'card_style' => 'border', 'card_hover' => 1,
			'sticky' => 'off', 'logo' => 'left', 'topbar' => 1, 'wishlist' => 1, 'compare' => 1,
			'menu_style' => 'text', 'icon_source' => 'icons', 'cat_icons' => [], 'heading_style' => 'plain', 'badge_style' => 'rounded',
			'tabs_style' => 'default', 'breadcrumb_band' => 0, 'qty_stepper' => 0, 'wide_cart' => 0, 'logo_white' => 0, 'card_compact' => 0, 'header_compact' => 0,
			'footer_layout' => 'classic', 'footer_about' => '', 'footer_newsletter' => 1,
			'footer_text' => '', 'social_facebook' => '', 'social_instagram' => '', 'social_x' => '', 'social_youtube' => '',
			'social_tiktok' => '', 'social_pinterest' => '', 'social_linkedin' => '', 'back_to_top' => 1, 'custom_css' => ''
		];
	}

	/**
	 * Same list as the storefront (catalog/controller/startup/webocreationtheme.php): icon => words that pick it.
	 */
	private function iconList(): array {
		return [
			'fa-mobile-screen-button' => 'phone|mobile|smartphone|pda', 'fa-tablet-screen-button' => 'tablet|ipad',
			'fa-laptop' => 'laptop|notebook|macbook', 'fa-desktop' => 'desktop|computer|\\bpc\\b|monitor',
			'fa-camera' => 'camera|photo', 'fa-tv' => 'tv|television', 'fa-print' => 'print', 'fa-volume-high' => 'speaker|audio|sound',
			'fa-headphones' => 'headphone|mp3|music|earbud', 'fa-clock' => 'watch|clock', 'fa-microchip' => 'component|hardware|chip|part',
			'fa-compact-disc' => 'software|disc|media', 'fa-plug' => 'accessor|cable|charger', 'fa-gamepad' => 'game|console',
			'fa-book' => 'book', 'fa-shirt' => 'cloth|fashion|shirt|apparel', 'fa-shoe-prints' => 'shoe|footwear',
			'fa-couch' => 'furniture|home|living', 'fa-kitchen-set' => 'kitchen|cook', 'fa-puzzle-piece' => 'toy|kid|baby',
			'fa-futbol' => 'sport|fitness|outdoor', 'fa-spa' => 'beauty|health|care', 'fa-gem' => 'jewel|ring', 'fa-tag' => ''
		];
	}

	private function autoIcon(string $name): string {
		foreach ($this->iconList() as $icon => $words) {
			if ($words !== '' && preg_match('/' . $words . '/i', $name)) {
				return $icon;
			}
		}

		return 'fa-tag';
	}

	private function fonts(): array {
		return ['system' => 'System UI (no download)', 'georgia' => 'Georgia (no download)', 'Inter' => 'Inter', 'Roboto' => 'Roboto', 'Open Sans' => 'Open Sans', 'Lato' => 'Lato', 'Montserrat' => 'Montserrat', 'Poppins' => 'Poppins', 'Nunito' => 'Nunito', 'Raleway' => 'Raleway', 'Work Sans' => 'Work Sans', 'DM Sans' => 'DM Sans', 'Manrope' => 'Manrope', 'Plus Jakarta Sans' => 'Plus Jakarta Sans', 'Outfit' => 'Outfit', 'Rubik' => 'Rubik', 'Oswald' => 'Oswald', 'Playfair Display' => 'Playfair Display (serif)', 'Merriweather' => 'Merriweather (serif)', 'Lora' => 'Lora (serif)', 'Libre Baskerville' => 'Libre Baskerville (serif)'];
	}

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$store_id = (int)($this->request->get['store_id'] ?? 0);
		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=theme')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token . '&store_id=' . $store_id)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token . '&store_id=' . $store_id);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=theme');
		$data['settings'] = $this->url->link('setting/setting', $token);

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('theme_webocreationtheme', $store_id);

		$data['s'] = [];

		foreach (self::defaults() as $key => $value) {
			$data['s'][$key] = array_key_exists('theme_webocreationtheme_' . $key, $saved) ? $saved['theme_webocreationtheme_' . $key] : $value;
		}

		$data['fonts'] = $this->fonts();

		// Top-level categories for the icon bar, each with its automatic or chosen icon.
		$data['icons'] = array_keys($this->iconList());
		$data['categories'] = [];

		$query = $this->db->query("SELECT c.`category_id`, cd.`name` FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (cd.`category_id` = c.`category_id` AND cd.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE c.`parent_id` = '0' AND c.`status` = '1' ORDER BY c.`sort_order`, LCASE(cd.`name`)");

		foreach ($query->rows as $row) {
			$data['categories'][] = [
				'category_id' => (int)$row['category_id'],
				'name'        => $row['name'],
				'auto'        => $this->autoIcon(html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8')),
				'icon'        => (string)($data['s']['cat_icons'][$row['category_id']] ?? '')
			];
		}
		$data['defaults'] = json_encode(self::defaults());
		$data['store_id'] = $store_id;

		// Is Webocreation Theme the store's theme yet?
		$data['active'] = $this->model_setting_setting->getValue('config_theme', $store_id) == 'webocreationtheme';

		$this->load->model('setting/store');

		$data['store_name'] = $store_id ? ($this->model_setting_store->getStore($store_id)['name'] ?? '') : $this->config->get('config_name');

		$data['color_groups'] = [
			'text_colors_brand'   => ['primary', 'primary_text', 'secondary', 'link', 'link_hover'],
			'text_colors_page'    => ['background', 'surface', 'text', 'muted', 'border', 'price', 'special'],
			'text_colors_regions' => ['topbar_bg', 'topbar_text', 'header_bg', 'header_text', 'menu_bg', 'menu_text', 'catbar_bg', 'catbar_text', 'footer_bg', 'footer_text']
		];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		$store_id = (int)($this->request->get['store_id'] ?? 0);

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;
		$settings = [];

		foreach (self::defaults() as $key => $default) {
			if ($key == 'cat_icons') {
				$icons = [];

				foreach ((array)($post['cat_icons'] ?? []) as $category_id => $icon) {
					if (array_key_exists((string)$icon, $this->iconList())) {
						$icons[(int)$category_id] = (string)$icon;
					}
				}

				$settings['theme_webocreationtheme_cat_icons'] = $icons;

				continue;
			}

			$value = html_entity_decode(trim((string)($post[$key] ?? $default)), ENT_QUOTES, 'UTF-8');

			if (str_starts_with($key, 'color_')) {
				if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
					$json['error'][$key] = $this->language->get('error_color');
				}

				$value = strtolower($value);
			} elseif (str_starts_with($key, 'social_')) {
				if ($value !== '' && !preg_match('#^https://[^\s"<>]+$#', $value)) {
					$json['error'][$key] = $this->language->get('error_url');
				}
			} elseif (in_array($key, ['font_body', 'font_heading'])) {
				$value = isset($this->fonts()[$value]) ? $value : $default;
			} elseif (in_array($key, ['status', 'card_hover', 'topbar', 'wishlist', 'compare', 'back_to_top', 'breadcrumb_band', 'qty_stepper', 'wide_cart', 'logo_white', 'card_compact', 'header_compact', 'footer_newsletter'])) {
				$value = (int)!empty($value);
			} elseif ($key == 'custom_css') {
				$value = oc_substr($value, 0, 20000);
			} elseif ($key == 'footer_about') {
				$value = oc_substr(strip_tags($value), 0, 500);
			} elseif ($key == 'footer_text') {
				$value = oc_substr(strip_tags($value), 0, 255);
			}

			$settings['theme_webocreationtheme_' . $key] = $value;
		}

		$allowed = [
			'scheme' => ['light', 'dark'], 'font_source' => ['google', 'system'], 'heading_case' => ['none', 'uppercase'],
			'button_case' => ['none', 'uppercase'], 'container' => ['1140', '1320', '1500', 'fluid'],
			'card_style' => ['border', 'shadow', 'flat'], 'sticky' => ['off', 'header', 'menu', 'both'], 'logo' => ['left', 'center'],
			'heading_weight' => ['300', '400', '500', '600', '700', '800'], 'menu_style' => ['text', 'icons'], 'icon_source' => ['icons', 'images'],
			'heading_style' => ['plain', 'underline', 'accent', 'centered', 'boxed'], 'badge_style' => ['rounded', 'ribbon'], 'tabs_style' => ['default', 'boxed'], 'footer_layout' => ['classic', 'store']
		];

		foreach ($allowed as $key => $values) {
			if (!in_array((string)$settings['theme_webocreationtheme_' . $key], $values, true)) {
				$settings['theme_webocreationtheme_' . $key] = (string)self::defaults()[$key];
			}
		}

		$settings['theme_webocreationtheme_font_size'] = max(13, min(20, (int)$settings['theme_webocreationtheme_font_size']));
		$settings['theme_webocreationtheme_line_height'] = (string)max(1.2, min(2.0, round((float)$settings['theme_webocreationtheme_line_height'], 2)));
		$settings['theme_webocreationtheme_heading_spacing'] = (string)max(-0.05, min(0.2, round((float)$settings['theme_webocreationtheme_heading_spacing'], 3)));
		$settings['theme_webocreationtheme_radius'] = max(0, min(24, (int)$settings['theme_webocreationtheme_radius']));
		$settings['theme_webocreationtheme_button_radius'] = max(0, min(999, (int)$settings['theme_webocreationtheme_button_radius']));

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('theme_webocreationtheme', $settings, $store_id);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('webocreationtheme');

		$this->model_setting_startup->addStartup([
			'code'        => 'webocreationtheme',
			'description' => 'Webocreation theme: colors, fonts and layout from the theme settings',
			'action'      => 'catalog/extension/webocreationtheme/startup/webocreationtheme',
			'status'      => 1,
			'sort_order'  => 2
		]);

		$this->load->model('setting/setting');

		$settings = [];

		foreach (self::defaults() as $key => $value) {
			$settings['theme_webocreationtheme_' . $key] = $value;
		}

		$this->model_setting_setting->editSetting('theme_webocreationtheme', $settings, 0);
	}

	public function uninstall(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('webocreationtheme');
	}
}
