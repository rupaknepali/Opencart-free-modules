<?php
namespace Opencart\Admin\Controller\Extension\ProductCsv\Module;
/**
 * Class ProductCsv
 *
 * Bulk Price & Stock CSV for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\ProductCsv\Module
 */
class ProductCsv extends \Opencart\System\Engine\Controller {
	/**
	 * Columns written by the export. Import reads price, quantity and status and ignores the rest.
	 *
	 * @var array<int, string>
	 */
	private array $columns = ['product_id', 'master_id', 'model', 'sku', 'name', 'price', 'quantity', 'status'];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/product_csv/module/product_csv');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/product_csv/module/product_csv', 'user_token=' . $this->session->data['user_token'])
		];

		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['export'] = $this->url->link('extension/product_csv/module/product_csv.export', 'user_token=' . $this->session->data['user_token']);
		$data['import'] = $this->url->link('extension/product_csv/module/product_csv.import', 'user_token=' . $this->session->data['user_token']);

		$data['max_size'] = $this->getMaxSize();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/product_csv/module/product_csv', $data));
	}

	/**
	 * Export
	 *
	 * Streams every product as CSV. Excel opens it correctly because of the UTF-8 byte order mark.
	 *
	 * @return void
	 */
	public function export(): void {
		$this->load->language('extension/product_csv/module/product_csv');

		if (!$this->user->hasPermission('access', 'extension/product_csv/module/product_csv')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$this->load->model('extension/product_csv/module/product_csv');

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="products-' . date('Y-m-d-His') . '.csv"');
		header('Cache-Control: no-store');

		$handle = fopen('php://output', 'w');

		fwrite($handle, "\xEF\xBB\xBF");

		// The escape character is set to '' so PHP 8.4 doesn't warn and quotes are doubled as RFC 4180 says.
		fputcsv($handle, $this->columns, ',', '"', '');

		$start = 0;

		do {
			$products = $this->model_extension_product_csv_module_product_csv->getProducts($start, 500);

			foreach ($products as $product) {
				fputcsv($handle, [
					$product['product_id'],
					$product['master_id'],
					html_entity_decode((string)$product['model'], ENT_QUOTES, 'UTF-8'),
					html_entity_decode((string)$product['sku'], ENT_QUOTES, 'UTF-8'),
					html_entity_decode((string)$product['name'], ENT_QUOTES, 'UTF-8'),
					number_format((float)$product['price'], 4, '.', ''),
					(int)$product['quantity'],
					(int)$product['status']
				], ',', '"', '');
			}

			$start += 500;
		} while ($products);

		fclose($handle);

		exit();
	}

	/**
	 * Import
	 *
	 * @return void
	 */
	public function import(): void {
		$this->load->language('extension/product_csv/module/product_csv');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/product_csv/module/product_csv')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$file = $this->request->files['file'] ?? [];

		if (!$json && (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) || $file['error'] != UPLOAD_ERR_OK)) {
			$json['error'] = $this->language->get('error_upload');
		}

		if (!$json && strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) != 'csv') {
			$json['error'] = $this->language->get('error_filetype');
		}

		if (!$json && $file['size'] > $this->getMaxSize() * 1024 * 1024) {
			$json['error'] = sprintf($this->language->get('error_size'), $this->getMaxSize());
		}

		$match = (string)($this->request->post['match'] ?? 'product_id');

		if (!in_array($match, ['product_id', 'model', 'sku'])) {
			$match = 'product_id';
		}

		$apply = !empty($this->request->post['apply']);

		if (!$json) {
			$result = $this->process($file['tmp_name'], $match, $apply);

			if (isset($result['error'])) {
				$json['error'] = $result['error'];
			} else {
				$result['apply'] = $apply;

				$json['html'] = $this->load->view('extension/product_csv/module/product_csv_result', $result + $this->language->all());

				$json['success'] = $apply ? sprintf($this->language->get('text_applied'), count($result['changes'])) : sprintf($this->language->get('text_preview'), count($result['changes']));
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Process
	 *
	 * @param string $filename
	 * @param string $match
	 * @param bool   $apply
	 *
	 * @return array<string, mixed>
	 */
	private function process(string $filename, string $match, bool $apply): array {
		$handle = fopen($filename, 'r');

		$first = (string)fgets($handle);

		rewind($handle);

		// Excel saves CSV with ; in many European locales.
		$delimiter = ',';

		foreach ([';', "\t"] as $candidate) {
			if (substr_count($first, $candidate) > substr_count($first, $delimiter)) {
				$delimiter = $candidate;
			}
		}

		$header = fgetcsv($handle, 0, $delimiter, '"', '');

		if (!$header) {
			return ['error' => $this->language->get('error_empty')];
		}

		$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);

		$header = array_map(fn ($column) => strtolower(trim((string)$column)), $header);

		if (!in_array($match, $header)) {
			return ['error' => sprintf($this->language->get('error_match'), $match)];
		}

		if (!array_intersect(['price', 'quantity', 'status'], $header)) {
			return ['error' => $this->language->get('error_columns')];
		}

		$this->load->model('extension/product_csv/module/product_csv');

		$changes = [];
		$errors = [];
		$unchanged = 0;
		$line = 1;
		$seen = [];

		while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
			$line++;

			if ($line > 20001) {
				$errors[] = ['line' => $line, 'message' => $this->language->get('error_rows')];

				break;
			}

			if ($row === [null] || !array_filter($row, fn ($value) => trim((string)$value) !== '')) {
				continue;
			}

			$values = [];

			foreach ($header as $index => $column) {
				$values[$column] = trim((string)($row[$index] ?? ''));
			}

			$key = $values[$match];

			if ($key === '') {
				$errors[] = ['line' => $line, 'message' => sprintf($this->language->get('error_key'), $match)];

				continue;
			}

			$products = $this->model_extension_product_csv_module_product_csv->findProducts($match, $key);

			if (!$products) {
				$errors[] = ['line' => $line, 'message' => sprintf($this->language->get('error_not_found'), $match, $key)];

				continue;
			}

			if (count($products) > 1) {
				$errors[] = ['line' => $line, 'message' => sprintf($this->language->get('error_duplicate'), $match, $key, count($products))];

				continue;
			}

			$product = $products[0];

			if (isset($seen[$product['product_id']])) {
				$errors[] = ['line' => $line, 'message' => sprintf($this->language->get('error_twice'), $key, $seen[$product['product_id']])];

				continue;
			}

			$seen[$product['product_id']] = $line;

			$update = [];
			$row_errors = [];

			if (isset($values['price']) && $values['price'] !== '') {
				$price = $this->parseNumber($values['price']);

				if ($price === null || $price < 0) {
					$row_errors[] = sprintf($this->language->get('error_price'), $values['price']);
				} elseif (round($price, 4) != round((float)$product['price'], 4)) {
					$update['price'] = $price;
				}
			}

			if (isset($values['quantity']) && $values['quantity'] !== '') {
				if (!preg_match('/^-?[0-9]+$/', $values['quantity'])) {
					$row_errors[] = sprintf($this->language->get('error_quantity'), $values['quantity']);
				} elseif ((int)$values['quantity'] != (int)$product['quantity']) {
					$update['quantity'] = (int)$values['quantity'];
				}
			}

			if (isset($values['status']) && $values['status'] !== '') {
				$status = $this->parseStatus($values['status']);

				if ($status === null) {
					$row_errors[] = sprintf($this->language->get('error_status'), $values['status']);
				} elseif ($status != (int)$product['status']) {
					$update['status'] = $status;
				}
			}

			if ($row_errors) {
				$errors[] = ['line' => $line, 'message' => implode(' ', $row_errors)];

				continue;
			}

			if (!$update) {
				$unchanged++;

				continue;
			}

			$changes[] = [
				'line'       => $line,
				'product_id' => (int)$product['product_id'],
				'name'       => $product['name'],
				'model'      => $product['model'],
				'variant'    => (bool)$product['master_id'],
				'price'      => isset($update['price']) ? [(float)$product['price'], $update['price']] : null,
				'quantity'   => isset($update['quantity']) ? [(int)$product['quantity'], $update['quantity']] : null,
				'status'     => isset($update['status']) ? [(int)$product['status'], $update['status']] : null,
				'edit'       => $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id']),
				'update'     => $update
			];
		}

		fclose($handle);

		if ($apply) {
			foreach ($changes as $change) {
				$this->model_extension_product_csv_module_product_csv->editProduct($change['product_id'], $change['update']);
			}

			$this->cache->delete('product');
		}

		return [
			'changes'     => array_slice($changes, 0, 500),
			'has_variant' => (bool)array_filter(array_column($changes, 'variant')),
			'total'       => count($changes),
			'errors'      => array_slice($errors, 0, 200),
			'unchanged'   => $unchanged
		];
	}

	/**
	 * Parse Number
	 *
	 * Accepts 1234.5, 1,234.50, 1234,50 and 1.234,50.
	 *
	 * @param string $value
	 *
	 * @return ?float
	 */
	private function parseNumber(string $value): ?float {
		$value = str_replace([' ', "\u{00A0}"], '', $value);

		$value = preg_replace('/[^0-9.,\-]/', '', $value);

		$comma = strrpos($value, ',');
		$dot = strrpos($value, '.');

		if ($comma !== false && ($dot === false || $comma > $dot)) {
			// Comma is the decimal separator.
			$value = str_replace(['.', ','], ['', '.'], $value);
		} else {
			$value = str_replace(',', '', $value);
		}

		return is_numeric($value) ? (float)$value : null;
	}

	/**
	 * Parse Status
	 *
	 * @param string $value
	 *
	 * @return ?int
	 */
	private function parseStatus(string $value): ?int {
		$value = strtolower($value);

		if (in_array($value, ['1', 'yes', 'y', 'true', 'enabled', 'on'])) {
			return 1;
		}

		if (in_array($value, ['0', 'no', 'n', 'false', 'disabled', 'off'])) {
			return 0;
		}

		return null;
	}

	/**
	 * Get Max Size
	 *
	 * @return int megabytes, limited by upload_max_filesize
	 */
	private function getMaxSize(): int {
		$limit = (string)ini_get('upload_max_filesize');

		$bytes = (int)$limit;

		switch (strtoupper(substr($limit, -1))) {
			case 'G':
				$bytes *= 1024;
				// no break
			case 'M':
				$bytes *= 1024;
				// no break
			case 'K':
				$bytes *= 1024;
		}

		return max(1, min(10, (int)floor($bytes / 1024 / 1024)));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_product_csv', ['module_product_csv_status' => 1]);
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
	}
}
