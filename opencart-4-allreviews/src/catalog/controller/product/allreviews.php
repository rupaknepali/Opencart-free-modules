<?php
namespace Opencart\Catalog\Controller\Extension\Allreviews\Product;
/**
 * All Reviews Page: index.php?route=extension/allreviews/product/allreviews
 */
class Allreviews extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/allreviews/product/allreviews';

	public function index(): void {
		if (!$this->config->get('module_allreviews_status') || !$this->config->get('config_review_status')) {
			// Behave like any missing page.
			$this->response->redirect($this->url->link('error/not_found', 'language=' . $this->config->get('config_language')));
		}

		$this->load->language($this->route);
		$this->load->model($this->route);
		$this->load->model('tool/image');

		$language = 'language=' . $this->config->get('config_language');
		$page = max(1, (int)($this->request->get['page'] ?? 1));
		$limit = max(1, (int)$this->config->get('module_allreviews_limit') ?: 10);
		$size = (int)$this->config->get('module_allreviews_image') ?: 80;

		// Settings are stored HTML-encoded, which is what the page needs.
		$title = (string)$this->config->get('module_allreviews_name') ?: $this->language->get('heading_title');
		$description = (string)$this->config->get('module_allreviews_description');

		$this->document->setTitle(html_entity_decode($title, ENT_QUOTES, 'UTF-8') . ($page > 1 ? ' - ' . sprintf($this->language->get('ar_page'), $page) : ''));
		$this->document->setDescription(html_entity_decode($description, ENT_QUOTES, 'UTF-8'));
		$this->document->addLink($this->url->link($this->route, $language . ($page > 1 ? '&page=' . $page : '')), 'canonical');

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/home', $language)],
			['text' => $title, 'href' => $this->url->link($this->route, $language)]
		];

		$data['heading_title'] = $title;
		$data['description'] = nl2br($description);

		$data['reviews'] = [];

		foreach ($this->model_extension_allreviews_product_allreviews->getReviews(($page - 1) * $limit, $limit) as $review) {
			$image = $review['image'] && is_file(DIR_IMAGE . html_entity_decode($review['image'], ENT_QUOTES, 'UTF-8')) ? $review['image'] : 'placeholder.png';

			$data['reviews'][] = [
				'name'       => $review['name'],
				'thumb'      => $this->model_tool_image->resize(html_entity_decode($image, ENT_QUOTES, 'UTF-8'), $size, $size),
				'href'       => $this->url->link('product/product', $language . '&product_id=' . $review['product_id']),
				'author'     => $review['author'],
				// Review text is stored HTML-encoded by OpenCart; keep it encoded, keep line breaks.
				'text'       => nl2br($review['text']),
				'rating'     => (int)$review['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($review['date_added']))
			];
		}

		$total = $this->model_extension_allreviews_product_allreviews->getTotalReviews();
		$average = $this->model_extension_allreviews_product_allreviews->getAverage();

		$data['summary'] = $total ? sprintf($this->language->get('ar_summary'), number_format((float)$average['average'], 1), $total) : '';
		$data['average'] = (float)$average['average'];

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link($this->route, $language . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($total, $page * $limit), $total, ceil($total / $limit));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view($this->route, $data));
	}
}
