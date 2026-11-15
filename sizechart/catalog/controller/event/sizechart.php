<?php
namespace Opencart\Catalog\Controller\Extension\Sizechart\Event;
/**
 * Size Chart: adds a "Size guide" link and popup to product pages that have a chart.
 */
class Sizechart extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/product/after
	 */
	public function index(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_sizechart_status') || empty($args['product_id'])) {
			return;
		}

		$this->load->model('extension/sizechart/module/sizechart');

		$chart = $this->model_extension_sizechart_module_sizechart->getChartByProduct((int)$args['product_id']);

		if (!$chart) {
			return;
		}

		$data['label'] = $this->config->get('module_sizechart_label') ?: 'Size guide';
		$data['title'] = $data['label'];
		$data['rows'] = $this->parse($chart['content']);
		$data['note'] = nl2br($chart['note']);

		$html = $this->load->view('extension/sizechart/module/sizechart', $data);

		// Just above the options and Add to Cart form.
		$position = strpos($output, '<div id="product">');

		if ($position !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * Turns the pasted text into rows of cells. Tabs (from a spreadsheet) or | separate cells.
	 * The text was HTML-encoded when it was saved, so the cells are safe to print.
	 */
	private function parse(string $content): array {
		$rows = [];

		foreach (preg_split('/\R/', trim($content)) as $line) {
			if (trim($line) === '') {
				continue;
			}

			$separator = str_contains($line, "\t") ? "\t" : '|';

			$rows[] = array_map('trim', explode($separator, trim($line, " |")));
		}

		return $rows;
	}
}
