<?php
namespace Opencart\Catalog\Controller\Extension\Faqschema\Event;
/**
 * FAQ with Schema: FAQ accordion and FAQPage JSON-LD on product, category and information pages.
 */
class Faqschema extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/product/after. Adds an "FAQ (n)" tab next to Description and Reviews.
	 */
	public function product(string &$route, array &$args, string &$output): void {
		$faqs = $this->faqs('product', (int)($this->request->get['product_id'] ?? 0));

		if (!$faqs) {
			return;
		}

		$this->load->language('extension/faqschema/module/faqschema');

		$tab = '<li class="nav-item"><a href="#tab-faq" data-bs-toggle="tab" class="nav-link">' . sprintf($this->language->get('faq_tab'), count($faqs)) . '</a></li>';

		if (preg_match('#<ul class="nav nav-tabs">.*?</ul>#s', $output, $match, PREG_OFFSET_CAPTURE) && ($pane = strpos($output, '<div class="tab-content">')) !== false) {
			// Pane first (it comes later in the page), then the tab, so the offsets stay right.
			$output = substr_replace($output, '<div id="tab-faq" class="tab-pane fade mb-4">' . $this->render($faqs, false) . '</div>', $pane + strlen('<div class="tab-content">'), 0);
			$output = substr_replace($output, $tab, $match[0][1] + strlen($match[0][0]) - strlen('</ul>'), 0);
		} else {
			$this->append($output, $this->render($faqs, true));
		}
	}

	/**
	 * Trigger: catalog/view/product/category/after
	 */
	public function category(string &$route, array &$args, string &$output): void {
		$parts = explode('_', (string)($this->request->get['path'] ?? ''));

		$faqs = $this->faqs('category', (int)end($parts));

		if ($faqs) {
			$this->append($output, $this->render($faqs, true));
		}
	}

	/**
	 * Trigger: catalog/view/information/information/after
	 */
	public function information(string &$route, array &$args, string &$output): void {
		$faqs = $this->faqs('information', (int)($this->request->get['information_id'] ?? 0));

		if ($faqs) {
			$this->append($output, $this->render($faqs, true));
		}
	}

	private function faqs(string $type, int $target_id): array {
		if (!$this->config->get('module_faqschema_status') || !$target_id) {
			return [];
		}

		$this->load->model('extension/faqschema/module/faqschema');

		return $this->model_extension_faqschema_module_faqschema->getFaqs($type, $target_id);
	}

	private function heading(): string {
		$this->load->language('extension/faqschema/module/faqschema');

		// Settings are stored HTML-encoded, which is what we want inside HTML.
		return (string)($this->config->get('module_faqschema_heading') ?: $this->language->get('faq_heading'));
	}

	private function render(array $faqs, bool $show_heading): string {
		$allowed = '<p><br><a><strong><b><em><i><ul><ol><li><h3><h4>';

		$data['heading'] = $show_heading ? $this->heading() : '';
		$data['faqs'] = [];

		$entities = [];

		foreach ($faqs as $faq) {
			// Questions and answers are stored HTML-encoded (OpenCart encodes form input).
			$question = html_entity_decode($faq['question'], ENT_QUOTES, 'UTF-8');
			$answer = strip_tags(html_entity_decode($faq['answer'], ENT_QUOTES, 'UTF-8'), $allowed);

			// Plain text that contains no tags gets paragraphs and line breaks.
			if ($answer === strip_tags($answer)) {
				$answer = '<p>' . nl2br(htmlspecialchars($answer, ENT_QUOTES, 'UTF-8'), false) . '</p>';
			}

			$data['faqs'][] = [
				'question' => htmlspecialchars($question, ENT_QUOTES, 'UTF-8'),
				'answer'   => $answer
			];

			$entities[] = [
				'@type'          => 'Question',
				'name'           => $question,
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => trim($answer)
				]
			];
		}

		$data['schema'] = '';

		if ($this->config->get('module_faqschema_schema')) {
			// JSON_HEX_TAG turns < and > into \u003C and \u003E, so an answer can never close the script tag.
			$data['schema'] = json_encode([
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities
			], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		}

		return $this->load->view('extension/faqschema/module/faqschema', $data);
	}

	/**
	 * Inserts HTML at the end of <div id="content">, wherever its closing tag is.
	 */
	private function append(string &$output, string $html): void {
		if (!preg_match('#<div[^>]*\bid="content"[^>]*>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			return;
		}

		$offset = $match[0][1] + strlen($match[0][0]);
		$depth = 1;

		while ($depth > 0 && preg_match('#<div\b|</div>#i', $output, $tag, PREG_OFFSET_CAPTURE, $offset)) {
			$depth += (strtolower($tag[0][0]) == '</div>') ? -1 : 1;
			$offset = $tag[0][1] + strlen($tag[0][0]);

			if ($depth == 0) {
				$output = substr_replace($output, $html, $tag[0][1], 0);
			}
		}
	}
}
