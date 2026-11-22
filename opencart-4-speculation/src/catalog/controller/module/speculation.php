<?php
namespace Opencart\Catalog\Controller\Extension\Speculation\Module;
/**
 * Instant Pages: Speculation Rules tell Chrome and Edge to prerender (or prefetch) the page a visitor is about to
 * open, so it appears instantly when they click.
 */
class Speculation extends \Opencart\System\Engine\Controller {
	public function index(): string {
		return '';
	}

	/**
	 * catalog/view/common/header/after: add the rules before </head>.
	 */
	public function rules(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_speculation_status') || ($pos = stripos($output, '</head>')) === false) {
			return;
		}

		if ($this->customer->isLogged() && !$this->config->get('module_speculation_logged')) {
			return;
		}

		// Not on checkout and account pages: links there lead to actions, not pages to read.
		$page = (string)($this->request->get['route'] ?? '');

		if (preg_match('#^(checkout|account|affiliate)/#', $page)) {
			return;
		}

		$output = substr_replace($output, $this->script(), $pos, 0);
	}

	private function script(): string {
		$mode = $this->config->get('module_speculation_mode') == 'prefetch' ? 'prefetch' : 'prerender';
		$eagerness = in_array($this->config->get('module_speculation_eagerness'), ['conservative', 'moderate', 'eager']) ? $this->config->get('module_speculation_eagerness') : 'moderate';

		$exclude = [];

		foreach (preg_split('/\R/', (string)$this->config->get('module_speculation_exclude')) as $pattern) {
			$pattern = trim($pattern);

			if ($pattern !== '') {
				$exclude[] = $pattern;
			}
		}

		$where = [['href_matches' => '/*']];

		if ($exclude) {
			$where[] = ['not' => ['href_matches' => $exclude]];
		}

		// Links marked as not for prerendering, new tabs and downloads.
		$where[] = ['not' => ['selector_matches' => '[rel~=nofollow], .no-prerender, [target=_blank], [download]']];

		$rules = [$mode => [['source' => 'document', 'where' => ['and' => $where], 'eagerness' => $eagerness]]];

		// With prerender, also prefetch on hover so browsers that limit prerenders still get a head start.
		if ($mode == 'prerender') {
			$rules['prefetch'] = [['source' => 'document', 'where' => ['and' => $where], 'eagerness' => 'moderate']];
		}

		return '<script type="speculationrules">' . json_encode($rules, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>' . "\n";
	}
}
