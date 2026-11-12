<?php
namespace Opencart\Catalog\Controller\Extension\Socialshare\Module;

class Socialshare extends \Opencart\System\Engine\Controller {
	/**
	 * Layout module: Design -> Layouts -> Product (or any layout) -> a position.
	 *
	 * Plain share links, no third-party scripts: nothing loads from Facebook, X or
	 * Pinterest until the visitor clicks, so there is no tracking and no slowdown.
	 */
	public function index(): string {
		$selected = (array)$this->config->get('module_socialshare_networks');

		if (!$selected) {
			return '';
		}

		$this->load->language('extension/socialshare/module/socialshare');

		$this->load->model('extension/socialshare/module/socialshare');

		$page = $this->model_extension_socialshare_module_socialshare->getPage();

		$url = rawurlencode($page['url']);
		$title = rawurlencode($page['title']);
		$image = rawurlencode($page['image']);

		// code => [label, icon, share URL]. An empty URL means a button handled by JavaScript.
		$all = [
			'facebook'  => ['Facebook', 'fa-brands fa-facebook-f', 'https://www.facebook.com/sharer/sharer.php?u=' . $url],
			'x'         => ['X', 'fa-brands fa-x-twitter', 'https://x.com/intent/tweet?url=' . $url . '&text=' . $title],
			'linkedin'  => ['LinkedIn', 'fa-brands fa-linkedin-in', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url],
			'pinterest' => ['Pinterest', 'fa-brands fa-pinterest-p', 'https://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $image . '&description=' . $title],
			'whatsapp'  => ['WhatsApp', 'fa-brands fa-whatsapp', 'https://wa.me/?text=' . $title . '%20' . $url],
			'telegram'  => ['Telegram', 'fa-brands fa-telegram', 'https://t.me/share/url?url=' . $url . '&text=' . $title],
			'reddit'    => ['Reddit', 'fa-brands fa-reddit-alien', 'https://www.reddit.com/submit?url=' . $url . '&title=' . $title],
			'bluesky'   => ['Bluesky', 'fa-brands fa-bluesky', 'https://bsky.app/intent/compose?text=' . $title . '%20' . $url],
			'email'     => [$this->language->get('text_email'), 'fa-solid fa-envelope', 'mailto:?subject=' . $title . '&body=' . $url],
			'copy'      => [$this->language->get('text_copy'), 'fa-solid fa-link', ''],
			'native'    => [$this->language->get('text_share'), 'fa-solid fa-share-nodes', '']
		];

		$data['networks'] = [];

		foreach ($all as $code => [$label, $icon, $href]) {
			if (!in_array($code, $selected)) {
				continue;
			}

			// Pinterest needs an image to pin.
			if ($code == 'pinterest' && !$page['image']) {
				continue;
			}

			$data['networks'][] = [
				'code'  => $code,
				'label' => $label,
				'icon'  => $icon,
				'href'  => $href,
				'popup' => str_starts_with($href, 'https://')
			];
		}

		if (!$data['networks']) {
			return '';
		}

		$position = (string)$this->config->get('module_socialshare_position');

		if (!in_array($position, ['inline', 'left', 'right', 'top', 'bottom'])) {
			$position = 'inline';
		}

		$data['position'] = $position;

		// Headings only make sense in the page; side bars have room for icons only.
		$data['share_heading'] = $position == 'inline' ? (string)$this->config->get('module_socialshare_heading') : '';
		$data['show_label'] = !in_array($position, ['left', 'right']) && $this->config->get('module_socialshare_label');
		$data['url'] = $page['url'];
		$data['title'] = $page['title'];

		return $this->load->view('extension/socialshare/module/socialshare', $data);
	}
}
