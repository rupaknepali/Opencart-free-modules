<?php
namespace Opencart\Catalog\Controller\Extension\Announcebar\Event;
/**
 * Announcement Bar: a message bar at the top of every storefront page.
 */
class Announcebar extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/common/header/after
	 */
	public function header(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_announcebar_status')) {
			return;
		}

		$message = trim(html_entity_decode((string)$this->config->get('module_announcebar_message'), ENT_QUOTES, 'UTF-8'));

		if ($message === '') {
			return;
		}

		// Scheduling, in the store's time zone. The end date is inclusive: the bar shows all of that day.
		$today = date('Y-m-d');
		$start = (string)$this->config->get('module_announcebar_date_start');
		$end = (string)$this->config->get('module_announcebar_date_end');

		if (($start && $today < $start) || ($end && $today > $end)) {
			return;
		}

		// A visitor who closed this exact message doesn't see it again. A new message gets a new ID.
		$id = substr(md5($message . '|' . $this->config->get('module_announcebar_link_url')), 0, 12);

		if ($this->config->get('module_announcebar_dismiss') && ($this->request->cookie['oc_announce'] ?? '') === $id) {
			return;
		}

		$link = html_entity_decode((string)$this->config->get('module_announcebar_link_url'), ENT_QUOTES, 'UTF-8');

		// Only web links and site paths, never javascript: or other schemes.
		if ($link !== '' && !preg_match('~^(https?://|/)~i', $link)) {
			$link = '';
		}

		$data['id'] = $id;
		$data['message'] = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
		$data['link_text'] = htmlspecialchars(html_entity_decode((string)$this->config->get('module_announcebar_link_text'), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
		$data['link_url'] = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
		$data['background'] = preg_match('/^#[0-9a-f]{6}$/i', (string)$this->config->get('module_announcebar_background')) ? $this->config->get('module_announcebar_background') : '#111827';
		$data['color'] = preg_match('/^#[0-9a-f]{6}$/i', (string)$this->config->get('module_announcebar_color')) ? $this->config->get('module_announcebar_color') : '#ffffff';
		$data['dismiss'] = (bool)$this->config->get('module_announcebar_dismiss');

		$this->load->language('extension/announcebar/module/announcebar');

		$html = $this->load->view('extension/announcebar/module/announcebar', $data);

		// Straight after the opening <body> tag, so it's the first thing on the page.
		if (preg_match('/<body[^>]*>/i', $output, $match, PREG_OFFSET_CAPTURE)) {
			$position = $match[0][1] + strlen($match[0][0]);
			$output = substr_replace($output, $html, $position, 0);
		}
	}
}
