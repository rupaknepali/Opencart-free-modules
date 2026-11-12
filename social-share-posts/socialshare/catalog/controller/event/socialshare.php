<?php
namespace Opencart\Catalog\Controller\Extension\Socialshare\Event;

class Socialshare extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/common/header/after
	 *
	 * Adds Open Graph and Twitter Card tags before </head>, so shared links show
	 * the right title, description and image on Facebook, LinkedIn, WhatsApp, X...
	 *
	 * @param string $route  'common/header'
	 * @param array  $args   the header template data
	 * @param string $output the rendered header HTML
	 */
	public function index(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_socialshare_status') || !$this->config->get('module_socialshare_og')) {
			return;
		}

		// The theme or another extension already prints Open Graph tags.
		if (str_contains($output, 'property="og:title"')) {
			return;
		}

		$pos = stripos($output, '</head>');

		if ($pos === false) {
			return;
		}

		$this->load->model('extension/socialshare/module/socialshare');

		$page = $this->model_extension_socialshare_module_socialshare->getPage();

		$tags = [
			'og:type'        => $page['type'],
			'og:site_name'   => $page['site_name'],
			'og:title'       => $page['title'],
			'og:description' => $page['description'],
			'og:url'         => $page['url'],
			'og:image'       => $page['image']
		];

		$html = '';

		foreach ($tags as $property => $content) {
			if ($content !== '') {
				$html .= '  <meta property="' . $property . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '"/>' . "\n";
			}
		}

		$html .= '  <meta name="twitter:card" content="' . ($page['image'] ? 'summary_large_image' : 'summary') . '"/>' . "\n";

		$output = substr_replace($output, $html, $pos, 0);
	}
}
