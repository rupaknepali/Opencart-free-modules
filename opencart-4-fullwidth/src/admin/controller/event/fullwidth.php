<?php
namespace Opencart\Admin\Controller\Extension\Fullwidth\Event;
/**
 * Full-Width Layout Position: a "Content Full Width" table in Design > Layouts > (layout).
 */
class Fullwidth extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: admin/view/design/layout_form/after
	 */
	public function layout(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_fullwidth_status')) {
			return;
		}

		$this->load->language('extension/fullwidth/module/fullwidth');

		$data = [
			'extensions'    => $args['extensions'] ?? [],
			'user_token'    => $this->session->data['user_token'],
			'layout_modules' => [],
			'fw_title'      => $this->language->get('fw_title'),
			'fw_help'       => $this->language->get('fw_help'),
			'button_edit'   => $this->language->get('button_edit'),
			'button_remove' => $this->language->get('button_remove'),
			'button_module_add' => $this->language->get('button_module_add')
		];

		// Row numbers well above the ones the core form uses, so the field names never clash.
		$row = 1000;

		foreach ($args['layout_modules'] ?? [] as $layout_module) {
			if ($layout_module['position'] == 'content_full') {
				$data['layout_modules'][] = $layout_module + ['row' => $row++];
			}
		}

		$html = $this->load->view('extension/fullwidth/module/fullwidth_layout', $data);

		// Above the four standard positions.
		if (preg_match('#<div class="row">\s*<div class="col-lg-3 col-md-4 col-sm-12">\s*<table id="module-column-left"#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1], 0);
		}
	}
}
