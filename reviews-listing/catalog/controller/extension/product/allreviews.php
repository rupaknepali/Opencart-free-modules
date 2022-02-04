<?php
class ControllerExtensionProductAllReviews extends Controller
{
    public function index()
    {
        if ($this->config->get('module_allreviews_status')) {

            $this->load->language('extension/product/allreviews');

            $this->load->model('extension/product/allreviews');

            $this->load->model('tool/image');

            $this->load->model('catalog/product');

            if (isset($this->request->get['sort'])) {
                $sort = $this->request->get['sort'];
            } else {
                $sort = 'p.sort_order';
            }

            if (isset($this->request->get['order'])) {
                $order = $this->request->get['order'];
            } else {
                $order = 'DESC';
            }

            if (isset($this->request->get['page'])) {
                $page = $this->request->get['page'];
            } else {
                $page = 1;
            }

            if (isset($this->request->get['limit'])) {
                $limit = (int) $this->request->get['limit'];
            } else {
                $limit = $this->config->get('module_allreviews_limit');
            }

            $this->document->setTitle($this->config->get('module_allreviews_name'));
            $this->document->setDescription($this->config->get('module_allreviews_description'));

            $data['heading_title'] = $this->config->get('module_allreviews_name');
            $data['decription'] = $this->config->get('module_allreviews_description');

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home')
            );

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            $data['breadcrumbs'][] = array(
                'text' => $this->config->get('module_allreviews_name'),
                'href' => $this->url->link('extension/product/allreviews', $url)
            );

            $data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

            $data['compare'] = $this->url->link('product/compare');

            $data['products'] = array();

            $filter_data = array(
                'start' => ($page - 1) * $limit,
                'limit' => $limit
            );

            $review_total = $this->model_extension_product_allreviews->getTotalReviews();

            $reviews = $this->model_extension_product_allreviews->getReviews($filter_data);
            // print_r($reviews);
            if ($reviews) {
                foreach ($reviews as $review) {
                    $product = $this->model_catalog_product->getProduct($review['product_id']);

                    if ($product['image']) {
                        $image = $this->model_tool_image->resize($product['image'], $this->config->get('module_allreviews_width'), $this->config->get('module_allreviews_height'));
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('module_allreviews_width'), $this->config->get('module_allreviews_height'));
                    }


                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }

                    if ((float) $product['special']) {
                        $special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special = false;
                    }

                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format((float) $product['special'] ? $product['special'] : $product['price'], $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }

                    if ($this->config->get('config_review_status')) {
                        $rating = $product['rating'];
                    } else {
                        $rating = false;
                    }

                    $data['reviews'][] = array(
                        'product_id'  => $product['product_id'],
                        'thumb'       => $image,
                        'name'        => $product['name'],
                        'description' => utf8_substr(trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'totalreviews' => $product['reviews'],
                        'author'      => $review['author'],
                        'text'        => $review['text'],
                        'date_added'  => $review['date_added'],
                        'href'        => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                    );
                }
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            $data['sorts'] = array();

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_default'),
                'value' => 'p.sort_order-ASC',
                'href'  => $this->url->link('extension/product/allreviews', 'sort=p.sort_order&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_name_asc'),
                'value' => 'pd.name-ASC',
                'href'  => $this->url->link('extension/product/allreviews', 'sort=pd.name&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_name_desc'),
                'value' => 'pd.name-DESC',
                'href'  => $this->url->link('extension/product/allreviews', 'sort=pd.name&order=DESC' . $url)
            );

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_price_asc'),
                'value' => 'ps.price-ASC',
                'href'  => $this->url->link('extension/product/allreviews', 'sort=ps.price&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_price_desc'),
                'value' => 'ps.price-DESC',
                'href'  => $this->url->link('extension/product/allreviews', 'sort=ps.price&order=DESC' . $url)
            );

            if ($this->config->get('config_review_status')) {
                $data['sorts'][] = array(
                    'text'  => $this->language->get('text_rating_desc'),
                    'value' => 'rating-DESC',
                    'href'  => $this->url->link('extension/product/allreviews', 'sort=rating&order=DESC' . $url)
                );

                $data['sorts'][] = array(
                    'text'  => $this->language->get('text_rating_asc'),
                    'value' => 'rating-ASC',
                    'href'  => $this->url->link('extension/product/allreviews', 'sort=rating&order=ASC' . $url)
                );
            }

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_model_asc'),
                'value' => 'p.model-ASC',
                'href'  => $this->url->link('extension/product/allreviews', 'sort=p.model&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_model_desc'),
                'value' => 'p.model-DESC',
                'href'  => $this->url->link('extension/product/allreviews', 'sort=p.model&order=DESC' . $url)
            );

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            $data['limits'] = array();

            $limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

            sort($limits);

            foreach ($limits as $value) {
                $data['limits'][] = array(
                    'text'  => $value,
                    'value' => $value,
                    'href'  => $this->url->link('extension/product/allreviews', $url . '&limit=' . $value)
                );
            }

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            $pagination = new Pagination();
            $pagination->total = $review_total;
            $pagination->page = $page;
            $pagination->limit = $limit;
            $pagination->url = $this->url->link('extension/product/allreviews', $url . '&page={page}');

            $data['pagination'] = $pagination->render();

            $data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($review_total - $limit)) ? $review_total : ((($page - 1) * $limit) + $limit), $review_total, ceil($review_total / $limit));

            // http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
            if ($page == 1) {
                $this->document->addLink($this->url->link('extension/product/allreviews', '', true), 'canonical');
            } else {
                $this->document->addLink($this->url->link('extension/product/allreviews', 'page=' . $page, true), 'canonical');
            }

            if ($page > 1) {
                $this->document->addLink($this->url->link('extension/product/allreviews', (($page - 2) ? '&page=' . ($page - 1) : ''), true), 'prev');
            }

            if ($limit && ceil($review_total / $limit) > $page) {
                $this->document->addLink($this->url->link('extension/product/allreviews', 'page=' . ($page + 1), true), 'next');
            }

            $data['sort'] = $sort;
            $data['order'] = $order;
            $data['limit'] = $limit;

            $data['continue'] = $this->url->link('common/home');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('extension/product/allreviews', $data));
        } else {
            echo "Please activate the All Reviews module first";
        }
    }
}
