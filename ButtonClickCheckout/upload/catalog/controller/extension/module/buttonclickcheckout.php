<?php
class ControllerExtensionModuleButtonClickCheckout extends Controller
{

    public function add()
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(0);

        $this->load->language('checkout/cart');

        $json = array();

        if (isset($this->request->get['product_id'])) {
            $product_id = (int) $this->request->get['product_id'];
        } else {
            $product_id = 0;
        }

        $this->load->model('catalog/product');

        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            if (isset($this->request->get['quantity']) && ((int) $this->request->get['quantity'] >= $product_info['minimum'])) {
                $quantity = (int) $this->request->get['quantity'];
            } else {
                $quantity = $product_info['minimum'] ? $product_info['minimum'] : 1;
            }

            ////http://opencart.loc/index.php?route=extension/module/buttonclickcheckout/add&product_id=58&options=246_71-247_72
            if (isset($this->request->get['options'])) {
                $soptions = $this->request->get['options'];

                $optionsid = explode('-', $soptions);

                foreach ($optionsid as $optionid) {
                    $optionvalue = explode('_', $optionid);
                    $options[$optionvalue[0]] = $optionvalue[1];
                }
            }

            // $options= array('246'=>71,'247'=>72);

            if (isset($options)) {
                $option = array_filter($options);
            } else {
                $option = array();
            }

            $product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);

            foreach ($product_options as $product_option) {
                if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                    $json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
                }
            }

            if (isset($this->request->get['recurring_id'])) {
                $recurring_id = $this->request->get['recurring_id'];
            } else {
                $recurring_id = 0;
            }

            $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

            if ($recurrings) {
                $recurring_ids = array();

                foreach ($recurrings as $recurring) {
                    $recurring_ids[] = $recurring['recurring_id'];
                }

                if (!in_array($recurring_id, $recurring_ids)) {
                    $json['error']['recurring'] = $this->language->get('error_recurring_required');
                }
            }

            if (!$json) {
                $this->cart->add($this->request->get['product_id'], $quantity, $option, $recurring_id);

                $json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), $product_info['name'], $this->url->link('checkout/cart'));

                // Unset all shipping and payment methods
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
                // Totals
                $this->load->model('setting/extension');

                $totals = array();
                $taxes = $this->cart->getTaxes();
                $total = 0;
                // Because __call can not keep var references so we put them into an array.
                $total_data = array(
                    'totals' => &$totals,
                    'taxes' => &$taxes,
                    'total' => &$total,
                );
                // Display prices
                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $sort_order = array();
                    $results = $this->model_setting_extension->getExtensions('total');
                    foreach ($results as $key => $value) {
                        $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                    }
                    array_multisort($sort_order, SORT_ASC, $results);
                    foreach ($results as $result) {
                        if ($this->config->get($result['code'] . '_status')) {
                            $this->load->model('total/' . $result['code']);
                            // We have to put the totals in an array so that they pass by reference.
                            $this->{'model_total_' . $result['code']}->getTotal($total_data);
                        }
                    }
                    $sort_order = array();
                    foreach ($totals as $key => $value) {
                        $sort_order[$key] = $value['sort_order'];
                    }
                    array_multisort($sort_order, SORT_ASC, $totals);
                }

                $json['total'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
            } else {
                $json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->get['product_id']));
            }
        }
        $this->response->redirect($this->url->link('checkout/checkout'));
    }

}
