<?php
namespace Opencart\Catalog\Controller\Extension\Ageverify\Event;
/**
 * Age Verification: an age confirmation popup on the first visit.
 */
class Ageverify extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/common/footer/after
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_ageverify_status')) {
			return;
		}

		// Already confirmed on this device: nothing to add, so no flash of the popup.
		if (($this->request->cookie['oc_age_ok'] ?? '') === '1') {
			return;
		}

		$this->load->language('extension/ageverify/module/ageverify');

		$age = max(1, (int)$this->config->get('module_ageverify_age'));
		$exit = html_entity_decode((string)$this->config->get('module_ageverify_exit_url'), ENT_QUOTES, 'UTF-8');

		$data['title'] = sprintf($this->config->get('module_ageverify_title') ?: $this->language->get('av_title'), $age);
		$data['message'] = nl2br((string)($this->config->get('module_ageverify_message') ?: $this->language->get('av_message')));
		$data['yes'] = $this->config->get('module_ageverify_yes') ?: $this->language->get('av_yes');
		$data['no'] = $this->config->get('module_ageverify_no') ?: $this->language->get('av_no');
		$data['exit_url'] = preg_match('~^https?://~i', $exit) ? $exit : 'https://www.google.com/';
		$data['days'] = max(0, (int)$this->config->get('module_ageverify_days'));
		$data['logo'] = $this->config->get('config_logo') && is_file(DIR_IMAGE . $this->config->get('config_logo')) ? $this->config->get('config_url') . 'image/' . $this->config->get('config_logo') : '';
		$data['store'] = $this->config->get('config_name');

		$html = $this->load->view('extension/ageverify/module/ageverify', $data);

		$position = strripos($output, '</body>');

		$output = $position !== false ? substr_replace($output, $html, $position, 0) : $output . $html;
	}
}
