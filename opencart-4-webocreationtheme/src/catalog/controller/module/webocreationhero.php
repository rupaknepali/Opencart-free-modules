<?php
namespace Opencart\Catalog\Controller\Extension\Webocreationtheme\Module;
/**
 * Webocreation Hero: a slideshow from a banner, with each slide's title as a tab underneath, and a side panel of
 * blog articles, specials or new products.
 */
class Webocreationhero extends \Opencart\System\Engine\Controller {
	private static int $count = 0;

	public function index(array $setting): string {
		$this->load->model('design/banner');
		$this->load->model('tool/image');

		$width = max(200, (int)($setting['width'] ?? 1140));
		$height = max(100, (int)($setting['height'] ?? 500));

		$data['id'] = 'wt-hero-' . ++self::$count;
		$data['slides'] = [];

		foreach ($this->model_design_banner->getBanner((int)($setting['banner_id'] ?? 0)) as $slide) {
			if ($slide['image'] && is_file(DIR_IMAGE . html_entity_decode($slide['image'], ENT_QUOTES, 'UTF-8'))) {
				$data['slides'][] = [
					'title' => $slide['title'],
					'link'  => $slide['link'],
					'image' => $this->model_tool_image->resize(html_entity_decode($slide['image'], ENT_QUOTES, 'UTF-8'), $width, $height)
				];
			}
		}

		$data['width'] = $width;
		$data['height'] = $height;
		$data['tabs'] = !empty($setting['tabs']) && count($data['slides']) > 1;

		$data['panel'] = $this->panel($setting);
		$data['panel_title'] = (string)($setting['panel_title'][(int)$this->config->get('config_language_id')] ?? '');
		$data['panel_more'] = $data['panel'] ? $this->more((string)$setting['panel']) : '';

		$this->load->language('extension/webocreationtheme/module/webocreationhero');

		$data['text_more'] = $this->language->get('text_more');
		$data['text_previous'] = $this->language->get('text_previous');
		$data['text_next'] = $this->language->get('text_next');

		return $data['slides'] ? $this->load->view('extension/webocreationtheme/module/webocreationhero', $data) : '';
	}

	private function panel(array $setting): array {
		$source = (string)($setting['panel'] ?? 'none');
		$limit = max(1, min(8, (int)($setting['panel_limit'] ?? 4)));
		$language = 'language=' . $this->config->get('config_language');
		$items = [];

		if ($source == 'articles') {
			$this->load->model('cms/article');

			foreach ($this->model_cms_article->getArticles(['sort' => 'date_added', 'order' => 'DESC', 'start' => 0, 'limit' => $limit]) as $article) {
				$items[] = [
					'name'  => $article['name'],
					'meta'  => date($this->language->get('date_format_short'), strtotime($article['date_added'])),
					'image' => !empty($article['image']) && is_file(DIR_IMAGE . $article['image']) ? $this->model_tool_image->resize($article['image'], 168, 112) : '',
					'href'  => $this->url->link('cms/blog.info', $language . '&article_id=' . (int)$article['article_id'])
				];
			}
		} elseif (in_array($source, ['specials', 'latest'])) {
			$this->load->model('catalog/product');

			$products = $source == 'specials'
				? $this->model_catalog_product->getSpecials(['sort' => 'pd.name', 'order' => 'ASC', 'start' => 0, 'limit' => $limit])
				: $this->model_catalog_product->getProducts(['sort' => 'p.date_added', 'order' => 'DESC', 'start' => 0, 'limit' => $limit]);

			$show_price = $this->customer->isLogged() || !$this->config->get('config_customer_price');

			foreach ($products as $product) {
				$meta = '';

				if ($show_price) {
					$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

					if ((float)$product['special']) {
						$meta = '<span class="wt-special">' . $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) . '</span><s>' . $price . '</s>';
					} else {
						$meta = $price;
					}
				}

				$items[] = [
					'name'  => $product['name'],
					'meta'  => $meta,
					'image' => $this->model_tool_image->resize(html_entity_decode($product['image'] ?: 'placeholder.png', ENT_QUOTES, 'UTF-8'), 168, 112),
					'href'  => $this->url->link('product/product', $language . '&product_id=' . (int)$product['product_id'])
				];
			}
		}

		return $items;
	}

	private function more(string $source): string {
		$language = 'language=' . $this->config->get('config_language');

		return match ($source) {
			'articles' => $this->url->link('cms/blog', $language),
			'specials' => $this->url->link('product/special', $language),
			default    => ''
		};
	}
}
