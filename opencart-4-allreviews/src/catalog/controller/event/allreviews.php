<?php
namespace Opencart\Catalog\Controller\Extension\Allreviews\Event;
class Allreviews extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/common/footer/after. A link under Extras, after Specials.
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_allreviews_status') || !$this->config->get('module_allreviews_footer') || !$this->config->get('config_review_status')) {
			return;
		}

		$title = (string)$this->config->get('module_allreviews_name') ?: 'Customer Reviews';
		$link = '<li><a href="' . $this->url->link('extension/allreviews/product/allreviews', 'language=' . $this->config->get('config_language')) . '">' . $title . '</a></li>';

		if (preg_match('#<li><a href="[^"]*route=product/special[^"]*">[^<]*</a></li>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $link, $match[0][1] + strlen($match[0][0]), 0);
		}
	}
}
