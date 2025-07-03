<?php
namespace Opencart\Catalog\Controller\Extension\Allproductslisting\Module;

class Allproductslisting extends \Opencart\System\Engine\Controller {
   
    public function index(): ?\Opencart\System\Engine\Action {
        $this->load->language('extension/allproductslisting/module/allproductslisting');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/allproductslisting/module/allproductslisting', 'language=' . $this->config->get('config_language'))
        ];

        $data['load_more_url'] = $this->url->link('extension/allproductslisting/module/allproductslisting|loadMore', 'language=' . $this->config->get('config_language'));

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/allproductslisting/module/allproductslisting', $data));

        return null;
    }

    public function loadMore(): void {
        $this->load->language('extension/allproductslisting/module/allproductslisting');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = $this->config->get('config_pagination');

        $filter_data = [
            'sort'  => 'p.sort_order',
            'order' => 'ASC',
            'start' => ($page - 1) * $limit,
            'limit' => $limit
        ];

        $products = [];
        $results = $this->model_catalog_product->getProducts($filter_data);

        foreach ($results as $result) {
            $description = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));

            if (oc_strlen($description) > $this->config->get('config_product_description_length')) {
                $description = oc_substr($description, 0, $this->config->get('config_product_description_length')) . '..';
            }

            if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
                $image = $result['image'];
            } else {
                $image = 'placeholder.png';
            }

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            if ((float)$result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }

            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
            } else {
                $tax = false;
            }

            $product_data = [
                'description' => $description,
                'thumb'       => $this->model_tool_image->resize($image, $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height')),
                'price'       => $price,
                'special'     => $special,
                'tax'         => $tax,
                'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
                'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id'])
            ] + $result;

            $products[] = $this->load->controller('product/thumb', $product_data);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($products));
    }

    public function menu(string &$route, array &$data, mixed &$output): void {
        if ($this->config->get('module_allproductslisting_status')) {
            $this->load->language('extension/allproductslisting/module/allproductslisting');
            $allproductslisting_menu = [
                'name'     => $this->language->get('heading_title'),
                'children' => [],
                'column'   => 1,
                'href'     => $this->url->link('extension/allproductslisting/module/allproductslisting', 'language=' . $this->config->get('config_language'))
            ];

            $data['categories'][] = $allproductslisting_menu;
        }
    }
}
