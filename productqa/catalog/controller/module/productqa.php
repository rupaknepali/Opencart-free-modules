<?php
namespace Opencart\Catalog\Controller\Extension\Productqa\Module;
/**
 * Product Questions & Answers: the block on product pages, and the "ask" endpoint.
 */
class Productqa extends \Opencart\System\Engine\Controller {
	public function index(): string {
		if (!$this->config->get('module_productqa_status') || ($this->request->get['route'] ?? '') != 'product/product' || empty($this->request->get['product_id'])) {
			return '';
		}

		$product_id = (int)$this->request->get['product_id'];

		$this->load->language('extension/productqa/module/productqa');
		$this->load->model('extension/productqa/module/productqa');

		$data['questions'] = [];

		foreach ($this->model_extension_productqa_module_productqa->getQuestions($product_id) as $result) {
			// Stored HTML-encoded, like everything OpenCart saves from a form: safe to print as is.
			$data['questions'][] = [
				'name'     => $result['name'],
				'question' => nl2br($result['question']),
				'answer'   => nl2br($result['answer']),
				'date'     => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			];
		}

		$data['limit'] = max(1, (int)($this->config->get('module_productqa_limit') ?: 5));
		$data['product_id'] = $product_id;
		$data['ask'] = $this->url->link('extension/productqa/module/productqa.ask', 'language=' . $this->config->get('config_language'));

		// Logged-in customers don't type their name.
		$data['logged'] = $this->customer->isLogged();
		$data['name'] = $data['logged'] ? $this->customer->getFirstName() . ' ' . oc_substr($this->customer->getLastName(), 0, 1) . '.' : '';

		if ($this->config->get('module_productqa_login') && !$data['logged']) {
			$data['login'] = sprintf($this->language->get('pqa_text_login'), $this->url->link('account/login', 'language=' . $this->config->get('config_language')));
		} else {
			$data['login'] = '';
		}

		return $this->load->view('extension/productqa/module/productqa', $data);
	}

	public function ask(): void {
		$this->load->language('extension/productqa/module/productqa');

		$json = [];

		$post = $this->request->post;

		if (!$this->config->get('module_productqa_status') || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error']['warning'] = $this->language->get('pqa_error_closed');
		}

		if ($this->config->get('module_productqa_login') && !$this->customer->isLogged()) {
			$json['error']['warning'] = $this->language->get('pqa_error_login');
		}

		// Bots fill in every field, including this one, which people never see.
		if (!empty($post['website'])) {
			$json['error']['warning'] = $this->language->get('pqa_error_closed');
		}

		// One question every 30 seconds per visitor.
		if (!empty($this->session->data['productqa_time']) && time() - $this->session->data['productqa_time'] < 30) {
			$json['error']['warning'] = $this->language->get('pqa_error_wait');
		}

		$this->load->model('catalog/product');

		if (!$this->model_catalog_product->getProduct((int)($post['product_id'] ?? 0))) {
			$json['error']['warning'] = $this->language->get('pqa_error_closed');
		}

		$name = $this->customer->isLogged() ? $this->customer->getFirstName() . ' ' . oc_substr($this->customer->getLastName(), 0, 1) . '.' : trim((string)($post['name'] ?? ''));

		if (oc_strlen($name) < 2 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('pqa_error_name');
		}

		$question = trim((string)($post['question'] ?? ''));

		if (oc_strlen($question) < 10 || oc_strlen($question) > 1000) {
			$json['error']['question'] = $this->language->get('pqa_error_question');
		}

		if (!$json) {
			$this->load->model('extension/productqa/module/productqa');

			$this->model_extension_productqa_module_productqa->addQuestion((int)$post['product_id'], $name, $question);

			$this->session->data['productqa_time'] = time();

			$json['success'] = $this->language->get('pqa_text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
