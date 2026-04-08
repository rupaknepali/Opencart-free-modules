<?php
namespace Opencart\Catalog\Controller\Extension\ImageContent\Module;
/**
 * Class ImageContent
 *
 * @package Opencart\Catalog\Controller\Extension\ImageContent\Module
 */
class ImageContent extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $setting array of data
	 *
	 * @return string
	 */
	public function index(array $setting): string {
		if (isset($setting['module_description'][$this->config->get('config_language_id')])) {
			$data['heading_title'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['title'], ENT_QUOTES, 'UTF-8');

			$data['description'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['description'], ENT_QUOTES, 'UTF-8');

			// Image
			if (isset($setting['image']) && is_file(DIR_IMAGE . $setting['image'])) {
				$this->load->model('tool/image');
				$data['image'] = $this->model_tool_image->resize($setting['image'], 400, 300);
			} else {
				$data['image'] = '';
			}

			// Image Position
			$data['image_position'] = isset($setting['image_position']) ? $setting['image_position'] : 'left';
		// Image Styling
		$data['image_width'] = isset($setting['image_width']) ? $setting['image_width'] : 95;
		$data['image_border_radius'] = isset($setting['image_border_radius']) ? $setting['image_border_radius'] : '5%';
		$data['image_box_shadow'] = isset($setting['image_box_shadow']) ? $setting['image_box_shadow'] : '0 10px 30px rgba(0, 0, 0, 0.15)';
		$data['image_transition'] = isset($setting['image_transition']) ? $setting['image_transition'] : 'transform 0.3s ease, box-shadow 0.3s ease';		$data['image_hover_transform'] = isset($setting['image_hover_transform']) ? $setting['image_hover_transform'] : 'translateY(-5px)';
			// CTA Button
		if (isset($setting['cta_text'])) {
			if (is_array($setting['cta_text'])) {
				$data['cta_text'] = html_entity_decode($setting['cta_text'][$this->config->get('config_language_id')] ?? '', ENT_QUOTES, 'UTF-8');
			} else {
				$data['cta_text'] = html_entity_decode($setting['cta_text'], ENT_QUOTES, 'UTF-8');
			}
		} else {
			$data['cta_text'] = '';
		}

			$data['cta_url'] = $setting['cta_url'] ?? '';
			$data['cta_style'] = $setting['cta_style'] ?? '#0d6efd';

			// Handle array format for CTA settings (for multilingual support)
			if (is_array($setting['cta_url'])) {
				$data['cta_url'] = $setting['cta_url'][$this->config->get('config_language_id')] ?? '';
			}
			if (is_array($setting['cta_style'])) {
				$data['cta_style'] = $setting['cta_style'][$this->config->get('config_language_id')] ?? '#0d6efd';
			}

			if (isset($setting['cta_text_color'])) {
				$data['cta_text_color'] = $setting['cta_text_color'];
				if (is_array($setting['cta_text_color'])) {
				$data['cta_text_color'] = $setting['cta_text_color'][$this->config->get('config_language_id')] ?? '#ffffff';
			}
			} else {
				$data['cta_text_color'] = '#ffffff';
			}

			

			if (isset($setting['cta_border_color'])) {
				$data['cta_border_color'] = $setting['cta_border_color'];
				if (is_array($setting['cta_border_color'])) {
					$data['cta_border_color'] = $setting['cta_border_color'][$this->config->get('config_language_id')] ?? $data['cta_style'];
				}
			} else {
				$data['cta_border_color'] = $data['cta_style'];
			}

			$data['title_color'] = $setting['title_color'] ?? '#1a1a1a';
			$data['description_color'] = $setting['description_color'] ?? '#555555';

			$data['cta_border_radius'] = $setting['cta_border_radius'] ?? '8px';
			if (is_array($setting['cta_border_radius'])) {
				$data['cta_border_radius'] = $setting['cta_border_radius'][$this->config->get('config_language_id')] ?? '8px';
			}

			$data['cta_padding'] = $setting['cta_padding'] ?? '12px 32px';
			if (is_array($setting['cta_padding'])) {
				$data['cta_padding'] = $setting['cta_padding'][$this->config->get('config_language_id')] ?? '12px 32px';
			}

			$data['cta_hover_background'] = $setting['cta_hover_background'] ?? '#0b5ed7';
			if (is_array($setting['cta_hover_background'])) {
				$data['cta_hover_background'] = $setting['cta_hover_background'][$this->config->get('config_language_id')] ?? '#0b5ed7';
			}
			$data['cta_hover_text_color'] = $setting['cta_hover_text_color'] ?? '#ffffff';
			if (is_array($setting['cta_hover_text_color'])) {
				$data['cta_hover_text_color'] = $setting['cta_hover_text_color'][$this->config->get('config_language_id')] ?? '#ffffff';
			}
			$data['cta_open_in_new_tab'] = isset($setting['cta_open_in_new_tab']) ? (bool)$setting['cta_open_in_new_tab'] : false;

			// Allow legacy Bootstrap class names, but prefer a hex color picker value
			if (!preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $data['cta_style'])) {
				switch ($data['cta_style']) {
					case 'secondary':
						$data['cta_style'] = '#6c757d';
						break;
					case 'success':
						$data['cta_style'] = '#198754';
						break;
					case 'danger':
						$data['cta_style'] = '#dc3545';
						break;
					case 'warning':
						$data['cta_style'] = '#ffc107';
						break;
					case 'info':
						$data['cta_style'] = '#0dcaf0';
						break;
					case 'light':
						$data['cta_style'] = '#f8f9fa';
						break;
					case 'dark':
						$data['cta_style'] = '#212529';
						break;
					case 'primary':
					default:
						$data['cta_style'] = '#0d6efd';
						break;
				}
			}
		// Full Width
		$data['full_width'] = isset($setting['full_width']) ? (bool)$setting['full_width'] : false;

		// Background Color
		$data['background_color'] = $setting['background_color'] ?? '#c3cfe2';

		// Margin
		$data['margin'] = $setting['margin'] ?? '20px 0px';

		// Padding
		$data['padding'] = $setting['padding'] ?? '40px 20px';

		return $this->load->view('extension/image_content/module/image_content', $data);
		} else {
			return '';
		}
	}
}