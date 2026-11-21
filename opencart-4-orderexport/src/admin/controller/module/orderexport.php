<?php
namespace Opencart\Admin\Controller\Extension\Orderexport\Module;
/**
 * Order Export: download orders as a CSV file for Excel, Google Sheets or your accountant,
 * filtered by date, status and store, one row per order or one row per product.
 */
class Orderexport extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/orderexport/module/orderexport';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['download'] = $this->url->link($this->route . '.download', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_orderexport');

		$data['module_orderexport_status'] = $saved['module_orderexport_status'] ?? 1;
		$data['module_orderexport_delimiter'] = $saved['module_orderexport_delimiter'] ?? ',';

		// The form starts with last month and the statuses that count as sales.
		$data['date_from'] = date('Y-m-01', strtotime('first day of last month'));
		$data['date_to'] = date('Y-m-t', strtotime('last day of last month'));

		$this->load->model('localisation/order_status');
		$this->load->model('setting/store');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['selected_statuses'] = array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status')));

		$data['stores'] = [['store_id' => 0, 'name' => $this->config->get('config_name')]];

		foreach ($this->model_setting_store->getStores() as $store) {
			$data['stores'][] = ['store_id' => $store['store_id'], 'name' => $store['name']];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$post = [
				'module_orderexport_status'    => (int)!empty($this->request->post['module_orderexport_status']),
				'module_orderexport_delimiter' => in_array($this->request->post['module_orderexport_delimiter'] ?? ',', [',', ';', 'tab']) ? $this->request->post['module_orderexport_delimiter'] : ','
			];

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_orderexport', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * The CSV file. Takes the export form's fields, or the Sales > Orders list filters (filter_*).
	 */
	public function download(): void {
		$this->load->language($this->route);

		if (!$this->user->hasPermission('access', $this->route) || !$this->user->hasPermission('access', 'sale/order')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$in = $this->request->post + $this->request->get;

		$filter = [
			'filter_order_id'        => (int)($in['filter_order_id'] ?? 0),
			'filter_customer'        => (string)($in['filter_customer'] ?? ''),
			'filter_email'           => (string)($in['filter_email'] ?? ''),
			'filter_store_id'        => isset($in['filter_store_id']) && $in['filter_store_id'] !== '' ? (int)$in['filter_store_id'] : '',
			'filter_order_status_id' => isset($in['filter_order_status_id']) && $in['filter_order_status_id'] !== '' ? (int)$in['filter_order_status_id'] : '',
			'filter_total'           => (string)($in['filter_total'] ?? ''),
			'filter_date_from'       => $this->date($in['filter_date_from'] ?? ''),
			'filter_date_to'         => $this->date($in['filter_date_to'] ?? ''),
			'filter_date_modified_from' => $this->date($in['filter_date_modified_from'] ?? ''),
			'filter_date_modified_to'   => $this->date($in['filter_date_modified_to'] ?? ''),
			'sort'                   => 'o.order_id',
			'order'                  => 'ASC'
		];

		// Several statuses from the export form, as the comma list the core order model understands.
		if (!empty($in['statuses'])) {
			$filter['filter_order_status'] = implode(',', array_map('intval', (array)$in['statuses']));
		}

		$rows = ($in['rows'] ?? 'orders') == 'products' ? 'products' : 'orders';

		$delimiter = $in['delimiter'] ?? $this->config->get('module_orderexport_delimiter') ?: ',';
		$delimiter = $delimiter == 'tab' ? "\t" : (in_array($delimiter, [',', ';']) ? $delimiter : ',');

		$this->load->model('sale/order');
		$this->load->model('localisation/order_status');
		$this->load->model('customer/customer_group');

		$statuses = array_column($this->model_localisation_order_status->getOrderStatuses(), 'name', 'order_status_id');
		$groups = array_column($this->model_customer_customer_group->getCustomerGroups(), 'name', 'customer_group_id');

		// Read the orders in batches so a big export doesn't load every order at once.
		$orders = [];
		$total_codes = [];

		for ($start = 0; ; $start += 200) {
			$batch = $this->model_sale_order->getOrders($filter + ['start' => $start, 'limit' => 200]);

			foreach ($batch as $result) {
				$order = $this->model_sale_order->getOrder((int)$result['order_id']);

				if (!$order) {
					continue;
				}

				$order['totals'] = $this->model_sale_order->getTotals((int)$result['order_id']);

				foreach ($order['totals'] as $line) {
					$key = $this->totalKey($line);

					if (!isset($total_codes[$key])) {
						$title = html_entity_decode($line['title'], ENT_QUOTES, 'UTF-8');

						if ($line['code'] == 'shipping') {
							$title = $this->language->get('column_shipping');
						} elseif ($line['code'] != 'tax') {
							// "Coupon (SAVE10)" becomes "Coupon". Tax lines keep their name: each rate is its own column.
							$title = trim(preg_replace('/\s*\(.*\)\s*$/', '', $title));
						}

						$total_codes[$key] = $title;
					}
				}

				$orders[] = $order;
			}

			if (count($batch) < 200) {
				break;
			}
		}

		$csv = fopen('php://temp', 'r+');

		// A byte order mark, so Excel opens accented names (Zoë, Müller) correctly.
		fwrite($csv, "\xEF\xBB\xBF");

		if ($rows == 'orders') {
			$header = [
				'Order ID', 'Invoice', 'Date added', 'Status', 'Store', 'Customer ID', 'First name', 'Last name', 'Email', 'Telephone', 'Customer group',
				'Payment method', 'Shipping method', 'Items',
				'Billing company', 'Billing address', 'Billing city', 'Billing postcode', 'Billing region', 'Billing country',
				'Shipping name', 'Shipping address', 'Shipping city', 'Shipping postcode', 'Shipping region', 'Shipping country'
			];

			// "total" goes last, after the other total lines.
			$codes = array_keys(array_diff_key($total_codes, ['total' => '']));

			foreach ($codes as $code) {
				$header[] = $total_codes[$code];
			}

			array_push($header, $total_codes['total'] ?? 'Total', 'Currency', 'Total in order currency', 'Comment');

			$this->put($csv, $header, $delimiter);

			foreach ($orders as $order) {
				$amounts = array_fill_keys($codes, 0.0);
				$grand = (float)$order['total'];

				foreach ($order['totals'] as $line) {
					if ($line['code'] == 'total') {
						$grand = (float)$line['value'];
					} else {
						$amounts[$this->totalKey($line)] += (float)$line['value'];
					}
				}

				$row = [
					$order['order_id'],
					$order['invoice_no'] ? $order['invoice_prefix'] . $order['invoice_no'] : '',
					$order['date_added'],
					$statuses[$order['order_status_id']] ?? '',
					$order['store_name'],
					$order['customer_id'] ?: '',
					$order['firstname'],
					$order['lastname'],
					$order['email'],
					$order['telephone'],
					$groups[$order['customer_group_id']] ?? '',
					$order['payment_method']['name'] ?? '',
					$order['shipping_method']['name'] ?? '',
					array_sum(array_column($order['products'], 'quantity')),
					$order['payment_company'],
					trim($order['payment_address_1'] . ' ' . $order['payment_address_2']),
					$order['payment_city'],
					$order['payment_postcode'],
					$order['payment_zone'],
					$order['payment_country'],
					trim($order['shipping_firstname'] . ' ' . $order['shipping_lastname']),
					trim($order['shipping_address_1'] . ' ' . $order['shipping_address_2']),
					$order['shipping_city'],
					$order['shipping_postcode'],
					$order['shipping_zone'],
					$order['shipping_country']
				];

				foreach ($codes as $code) {
					$row[] = $this->money($amounts[$code]);
				}

				array_push($row, $this->money($grand), $order['currency_code'], $this->money($grand * (float)$order['currency_value']), $order['comment']);

				$this->put($csv, $row, $delimiter);
			}
		} else {
			$this->put($csv, ['Order ID', 'Date added', 'Status', 'Customer', 'Email', 'Product ID', 'Product', 'Model', 'Options', 'Quantity', 'Unit price', 'Unit tax', 'Line total', 'Line total incl. tax', 'Currency'], $delimiter);

			foreach ($orders as $order) {
				foreach ($order['products'] as $product) {
					$options = [];

					foreach ($this->model_sale_order->getOptions((int)$order['order_id'], (int)$product['order_product_id']) as $option) {
						$options[] = $option['name'] . ': ' . $option['value'];
					}

					$this->put($csv, [
						$order['order_id'],
						$order['date_added'],
						$statuses[$order['order_status_id']] ?? '',
						trim($order['firstname'] . ' ' . $order['lastname']),
						$order['email'],
						$product['product_id'],
						$product['name'],
						$product['model'],
						implode('; ', $options),
						$product['quantity'],
						$this->money((float)$product['price']),
						$this->money((float)$product['tax']),
						$this->money((float)$product['total']),
						$this->money((float)$product['total'] + (float)$product['tax'] * (int)$product['quantity']),
						$this->config->get('config_currency')
					], $delimiter);
				}
			}
		}

		rewind($csv);

		$filename = 'orders-' . ($filter['filter_date_from'] ?: 'all') . ($filter['filter_date_to'] ? '-to-' . $filter['filter_date_to'] : '') . ($rows == 'products' ? '-products' : '') . '.csv';

		$this->response->addHeader('Content-Type: text/csv; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
		$this->response->addHeader('Cache-Control: no-store');
		$this->response->setOutput(stream_get_contents($csv));

		fclose($csv);
	}

	/**
	 * One CSV line. Text that a spreadsheet would run as a formula (=, +, -, @ at the start) gets a leading
	 * apostrophe, so a customer named "=HYPERLINK(...)" can't run anything when the file is opened.
	 */
	private function put($handle, array $row, string $delimiter): void {
		foreach ($row as &$cell) {
			$cell = html_entity_decode((string)$cell, ENT_QUOTES, 'UTF-8');

			if ($cell !== '' && !is_numeric($cell) && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
				$cell = "'" . $cell;
			}
		}

		fputcsv($handle, $row, $delimiter, '"', '');
	}

	/**
	 * The column a total line goes in: one per total code, except taxes, which get one column per tax rate.
	 */
	private function totalKey(array $line): string {
		return $line['code'] == 'tax' ? 'tax:' . $line['title'] : $line['code'];
	}

	private function money(float $value): string {
		return number_format($value, 2, '.', '');
	}

	private function date(string $value): string {
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
	}

	/**
	 * Event: admin/view/sale/order/after. An Export CSV button next to Print Invoice, using the list's filters.
	 */
	public function button(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_orderexport_status') || !$this->user->hasPermission('access', $this->route)) {
			return;
		}

		$this->load->language($this->route);

		$url = '';

		foreach (['filter_order_id', 'filter_customer', 'filter_email', 'filter_store_id', 'filter_order_status_id', 'filter_total', 'filter_date_from', 'filter_date_to', 'filter_date_modified_from', 'filter_date_modified_to'] as $key) {
			if (isset($this->request->get[$key]) && $this->request->get[$key] !== '') {
				$url .= '&' . $key . '=' . urlencode(html_entity_decode((string)$this->request->get[$key], ENT_QUOTES, 'UTF-8'));
			}
		}

		$href = $this->url->link($this->route . '.download', 'user_token=' . $this->session->data['user_token'] . $url);

		$button = '<a href="' . $href . '" id="button-export-csv" data-bs-toggle="tooltip" title="' . $this->language->get('button_export_list') . '" class="btn btn-success"><i class="fa-solid fa-file-csv"></i></a> ';

		$output = preg_replace('/(<button type="submit" id="button-invoice")/', $button . '$1', $output, 1);

		// The list reloads by AJAX when filtered, so keep the button's link in step with the filter form.
		$output = str_replace('</body>', '<script>$(document).on("click", "#button-filter", function() { var u = $("#button-export-csv").attr("href").split("&filter_")[0]; $("#filter-order").find("input, select").each(function() { if ($(this).attr("name") && $(this).val() !== "" && $(this).attr("name").indexOf("filter_") === 0) { u += "&" + $(this).attr("name") + "=" + encodeURIComponent($(this).val()); } }); $("#button-export-csv").attr("href", u); });</script></body>', $output);
	}

	/**
	 * Event: admin/view/common/column_left/before. Sales > Export Orders in the admin menu.
	 */
	public function menu(string &$route, array &$args): void {
		if (!$this->config->get('module_orderexport_status') || !$this->user->hasPermission('access', $this->route)) {
			return;
		}

		$this->load->language($this->route);

		foreach ($args['menus'] as &$menu) {
			if ($menu['id'] == 'menu-sale') {
				$menu['children'][] = [
					'name'     => $this->language->get('text_menu'),
					'href'     => $this->url->link($this->route, 'user_token=' . $this->session->data['user_token']),
					'children' => []
				];
			}
		}
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			['orderexport_button', 'admin/view/sale/order/after', 'button', 'Export CSV button on Sales > Orders'],
			['orderexport_menu', 'admin/view/common/column_left/before', 'menu', 'Sales > Export Orders menu item']
		];

		foreach ($events as [$code, $trigger, $method, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Order Export: ' . $description,
				'trigger'     => $trigger,
				'action'      => $this->route . '.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_orderexport', ['module_orderexport_status' => 1, 'module_orderexport_delimiter' => ',']);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('orderexport_button');
		$this->model_setting_event->deleteEventByCode('orderexport_menu');
	}
}
