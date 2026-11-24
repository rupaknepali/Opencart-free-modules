<?php
namespace Opencart\System\Library\Extension\Pdfinvoice;
/**
 * Builds the PDF invoice for an order and emails it. Used by both the admin and the catalog,
 * so it reads the order tables directly instead of going through either side's models.
 */
class Invoice {
	private object $db;
	private object $config;
	private object $currency;
	private array $text;

	/**
	 * @param array $text the pdf_* strings from the extension's language file
	 */
	public function __construct(\Opencart\System\Engine\Registry $registry, array $text) {
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');
		$this->currency = $registry->get('currency');
		$this->text = $text;
	}

	private function t(string $key): string {
		return $this->text[$key] ?? $key;
	}

	public function getOrder(int $order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "' AND `order_status_id` > '0'");

		return $query->row;
	}

	/**
	 * Give the order an invoice number if it has none (same numbering as Sales > Orders > Generate).
	 */
	public function ensureInvoiceNo(int $order_id): void {
		$order = $this->getOrder($order_id);

		if ($order && !$order['invoice_no']) {
			$query = $this->db->query("SELECT MAX(`invoice_no`) AS `invoice_no` FROM `" . DB_PREFIX . "order` WHERE `invoice_prefix` = '" . $this->db->escape($order['invoice_prefix']) . "'");

			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET `invoice_no` = '" . ((int)$query->row['invoice_no'] + 1) . "' WHERE `order_id` = '" . (int)$order_id . "' AND `invoice_no` = '0'");
		}
	}

	private function storeSetting(int $store_id, string $key): string {
		$query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

		if ($query->num_rows) {
			return (string)$query->row['value'];
		}

		return (string)$this->config->get($key);
	}

	private function address(array $order, string $type): array {
		$format = $order[$type . '_address_format'];

		if (!$format) {
			$format = "{firstname} {lastname}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{zone}\n{country}";
		}

		$replace = [];

		foreach (['firstname', 'lastname', 'company', 'address_1', 'address_2', 'city', 'postcode', 'zone', 'zone_code', 'country'] as $field) {
			$replace['{' . $field . '}'] = (string)($order[$type . '_' . $field] ?? '');
		}

		$lines = explode("\n", strtr(str_replace("\r", '', $format), $replace));

		return array_values(array_filter(array_map(fn($l) => trim(preg_replace('/\s\s+/', ' ', $l)), $lines), 'strlen'));
	}

	private function methodName(string $json): string {
		$method = json_decode($json, true);

		return is_array($method) ? (string)($method['name'] ?? '') : '';
	}

	public function filename(array $order): string {
		$number = $order['invoice_no'] ? $order['invoice_prefix'] . $order['invoice_no'] : 'order-' . $order['order_id'];

		return 'Invoice-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $number) . '.pdf';
	}

	/**
	 * @return string the PDF file contents, or '' if the order doesn't exist
	 */
	public function build(int $order_id): string {
		$order = $this->getOrder($order_id);

		if (!$order) {
			return '';
		}

		$store_id = (int)$order['store_id'];
		$code = $order['currency_code'];
		$value = (float)$order['currency_value'];
		$with_tax = (bool)$this->storeSetting($store_id, 'config_tax');

		$pdf = new Pdf((string)$this->config->get('module_pdfinvoice_paper'));
		$pdf->addPage();

		$left = 42;
		$right = $pdf->getWidth() - 42;
		$bottom = $pdf->getHeight() - 60;
		$y = 42;

		// Header: logo (or store name) on the left, INVOICE and the numbers on the right.
		$logo = $this->storeSetting($store_id, 'config_logo');
		$drawn = null;

		if ($this->config->get('module_pdfinvoice_logo') && $logo && is_file(DIR_IMAGE . $logo)) {
			$drawn = $pdf->image(DIR_IMAGE . $logo, $left, $y, 170, 48);
		}

		if ($drawn) {
			$sy = $y + $drawn[1] + 16;
		} else {
			$pdf->setFont(true, 16);
			$pdf->text($left, $y + 14, $order['store_name']);
			$sy = $y + 34;
		}

		$pdf->setColor(0.2, 0.2, 0.2);
		$pdf->setFont(true, 22);
		$pdf->text($right, $y + 18, mb_strtoupper($this->t('pdf_title')), 'R');

		$pdf->setFont(false, 9.5);
		$meta = [];

		if ($order['invoice_no']) {
			$meta[] = [$this->t('pdf_invoice_no'), $order['invoice_prefix'] . $order['invoice_no']];
		}

		$meta[] = [$this->t('pdf_order_id'), '#' . $order['order_id']];
		$meta[] = [$this->t('pdf_date'), date($this->t('pdf_date_format'), strtotime($order['date_added']))];

		$my = $y + 38;

		foreach ($meta as [$label, $text]) {
			$pdf->setFont(false, 9.5);
			$pdf->text($right - 100, $my, $label, 'R');
			$pdf->setFont(true, 9.5);
			$pdf->text($right, $my, $text, 'R');
			$my += 14;
		}

		// Seller block
		$pdf->setFont(true, 10);
		$pdf->text($left, $sy, $order['store_name']);
		$sy += 13;
		$pdf->setFont(false, 9);

		$seller = explode("\n", str_replace("\r", '', html_entity_decode($this->storeSetting($store_id, 'config_address'), ENT_QUOTES, 'UTF-8')));
		$seller[] = $this->storeSetting($store_id, 'config_telephone');
		$seller[] = $this->storeSetting($store_id, 'config_email');
		$seller[] = (string)$this->config->get('module_pdfinvoice_tax_id');

		foreach (array_filter(array_map('trim', $seller), 'strlen') as $line) {
			$pdf->text($left, $sy, $line);
			$sy += 12;
		}

		// Bill to / Ship to
		$y = max($sy, $my) + 18;
		$half = ($right - $left) / 2;
		$columns = [[$this->t('pdf_bill_to'), $this->address($order, 'payment')]];

		if ($order['shipping_firstname'] || $order['shipping_address_1']) {
			$columns[] = [$this->t('pdf_ship_to'), $this->address($order, 'shipping')];
		}

		// Guests on stores without a payment address step: fall back to the customer's name.
		if (!$columns[0][1]) {
			$columns[0][1] = [trim($order['firstname'] . ' ' . $order['lastname'])];
		}

		$columns[0][1][] = $order['email'];

		if ($order['telephone']) {
			$columns[0][1][] = $order['telephone'];
		}

		$tallest = $y;

		foreach ($columns as $i => [$heading, $lines]) {
			$cx = $left + $i * $half;
			$cy = $y;
			$pdf->setColor(0.45, 0.45, 0.45);
			$pdf->setFont(true, 8.5);
			$pdf->text($cx, $cy, strtoupper($heading));
			$pdf->setColor(0.1, 0.1, 0.1);
			$pdf->setFont(false, 9.5);
			$cy += 14;

			foreach ($lines as $line) {
				$pdf->text($cx, $cy, $line);
				$cy += 12.5;
			}

			$tallest = max($tallest, $cy);
		}

		$y = $tallest + 6;
		$pdf->setFont(false, 9);
		$methods = [];

		if ($name = $this->methodName((string)$order['payment_method'])) {
			$methods[] = $this->t('pdf_payment_method') . ' ' . $name;
		}

		if ($name = $this->methodName((string)$order['shipping_method'])) {
			$methods[] = $this->t('pdf_shipping_method') . ' ' . $name;
		}

		if ($methods) {
			$pdf->text($left, $y, implode('     ', $methods));
			$y += 10;
		}

		// Product table
		$cols = ['qty' => $right - 190, 'price' => $right - 85, 'total' => $right - 6];
		// The model column starts 120pt left of Qty; keep a 14pt gap before it.
		$name_width = ($cols['qty'] - 120) - ($left + 6) - 14;

		$header = function (float $y) use ($pdf, $left, $right, $cols): float {
			$pdf->setColor(0.93, 0.94, 0.95);
			$pdf->rect($left, $y, $right - $left, 20);
			$pdf->setColor(0.2, 0.2, 0.2);
			$pdf->setFont(true, 8.5);
			$pdf->text($left + 6, $y + 13.5, strtoupper($this->t('pdf_product')));
			$pdf->text($cols['qty'] - 120, $y + 13.5, strtoupper($this->t('pdf_model')));
			$pdf->text($cols['qty'], $y + 13.5, strtoupper($this->t('pdf_quantity')), 'R');
			$pdf->text($cols['price'], $y + 13.5, strtoupper($this->t('pdf_price')), 'R');
			$pdf->text($cols['total'], $y + 13.5, strtoupper($this->t('pdf_total')), 'R');

			return $y + 20;
		};

		$y = $header($y + 8);

		$products = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `order_product_id`")->rows;

		foreach ($products as $product) {
			$pdf->setFont(true, 9.5);
			$lines = $pdf->wrap(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'), $name_width);

			$options = [];

			foreach ($this->db->query("SELECT `name`, `value`, `type` FROM `" . DB_PREFIX . "order_option` WHERE `order_id` = '" . (int)$order_id . "' AND `order_product_id` = '" . (int)$product['order_product_id'] . "'")->rows as $option) {
				if ($option['type'] == 'file') {
					$upload = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "upload` WHERE `code` = '" . $this->db->escape($option['value']) . "'")->row;
					$option['value'] = $upload['name'] ?? '';
				}

				$options[] = $option['name'] . ': ' . $option['value'];
			}

			$row_height = 10 + count($lines) * 12 + count($options) * 11;

			if ($y + $row_height > $bottom) {
				$pdf->addPage();
				$y = $header(42);
			}

			$ry = $y + 15;
			$pdf->setColor(0.1, 0.1, 0.1);

			foreach ($lines as $i => $line) {
				$pdf->text($left + 6, $ry + $i * 12, $line);
			}

			$pdf->setFont(false, 9.5);
			$pdf->text($cols['qty'] - 120, $ry, (string)$product['model']);
			$pdf->text($cols['qty'], $ry, (string)$product['quantity'], 'R');

			$price = (float)$product['price'] + ($with_tax ? (float)$product['tax'] : 0);
			$total = (float)$product['total'] + ($with_tax ? (float)$product['tax'] * (int)$product['quantity'] : 0);

			$pdf->text($cols['price'], $ry, $this->currency->format($price, $code, $value), 'R');
			$pdf->text($cols['total'], $ry, $this->currency->format($total, $code, $value), 'R');

			$pdf->setColor(0.4, 0.4, 0.4);
			$pdf->setFont(false, 8.5);
			$oy = $ry + count($lines) * 12 - 1;

			foreach ($options as $option) {
				$pdf->text($left + 14, $oy, html_entity_decode($option, ENT_QUOTES, 'UTF-8'));
				$oy += 11;
			}

			$y += $row_height;
			$pdf->setColor(0.85, 0.86, 0.88);
			$pdf->line($left, $y, $right, $y);
		}

		// Totals
		$totals = $this->db->query("SELECT `title`, `value` FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `sort_order`")->rows;

		if ($y + 18 * count($totals) + 10 > $bottom) {
			$pdf->addPage();
			$y = 42;
		}

		$y += 8;

		foreach ($totals as $i => $total) {
			$last = $i == count($totals) - 1;
			$y += $last ? 20 : 15;

			if ($last) {
				$pdf->setColor(0.85, 0.86, 0.88);
				$pdf->line($cols['price'] - 110, $y - 14, $right, $y - 14);
			}

			$pdf->setColor(0.1, 0.1, 0.1);
			$pdf->setFont($last, $last ? 11 : 9.5);
			$pdf->text($cols['price'], $y, html_entity_decode($total['title'], ENT_QUOTES, 'UTF-8'), 'R');
			$pdf->text($cols['total'], $y, $this->currency->format((float)$total['value'], $code, $value), 'R');
		}

		// Customer comment
		if (trim($order['comment']) !== '') {
			$pdf->setFont(false, 9);
			$lines = $pdf->wrap(html_entity_decode($order['comment'], ENT_QUOTES, 'UTF-8'), $right - $left);

			if ($y + 30 + count($lines) * 12 > $bottom) {
				$pdf->addPage();
				$y = 42;
			}

			$y += 28;
			$pdf->setColor(0.45, 0.45, 0.45);
			$pdf->setFont(true, 8.5);
			$pdf->text($left, $y, strtoupper($this->t('pdf_comment')));
			$pdf->setColor(0.1, 0.1, 0.1);
			$pdf->setFont(false, 9);

			foreach ($lines as $line) {
				$y += 12;
				$pdf->text($left, $y, $line);
			}
		}

		// Footer on every page: the store's note and page numbers.
		$note = trim((string)$this->config->get('module_pdfinvoice_footer'));
		$count = $pdf->pageCount();

		for ($i = 0; $i < $count; $i++) {
			$pdf->setPage($i);
			$pdf->setColor(0.45, 0.45, 0.45);
			$pdf->setFont(false, 8);

			if ($note !== '') {
				$pdf->text($pdf->getWidth() / 2, $pdf->getHeight() - 36, html_entity_decode($note, ENT_QUOTES, 'UTF-8'), 'C');
			}

			if ($count > 1) {
				$pdf->text($right, $pdf->getHeight() - 22, sprintf($this->t('pdf_page'), $i + 1, $count), 'R');
			}
		}

		$title = $this->t('pdf_title') . ' ' . ($order['invoice_no'] ? $order['invoice_prefix'] . $order['invoice_no'] : '#' . $order['order_id']);

		return $pdf->output($title);
	}

	/**
	 * Email the invoice to the order's email address with the PDF attached.
	 *
	 * @return string '' on success, otherwise an error message key
	 */
	public function send(int $order_id, string $source): string {
		if (!$this->config->get('config_mail_engine')) {
			return 'error_mail';
		}

		if ($this->config->get('module_pdfinvoice_invoice_no')) {
			$this->ensureInvoiceNo($order_id);
		}

		$order = $this->getOrder($order_id);

		if (!$order || !filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
			return 'error_order';
		}

		$content = $this->build($order_id);

		// The mail library attaches files from disk and names the attachment after the file.
		$dir = DIR_STORAGE . 'upload/pdfinvoice-' . bin2hex(random_bytes(8)) . '/';
		mkdir($dir, 0700, true);
		$file = $dir . $this->filename($order);
		file_put_contents($file, $content);

		$number = $order['invoice_no'] ? $order['invoice_prefix'] . $order['invoice_no'] : '#' . $order['order_id'];
		$store_name = html_entity_decode($order['store_name'], ENT_QUOTES, 'UTF-8');

		$from = $this->storeSetting((int)$order['store_id'], 'config_email') ?: (string)$this->config->get('config_email');

		$body = '<p>' . htmlspecialchars(sprintf($this->t('pdf_mail_greeting'), $order['firstname']), ENT_QUOTES, 'UTF-8') . '</p>'
			. '<p>' . htmlspecialchars(sprintf($this->t('pdf_mail_body'), $number, $order['order_id']), ENT_QUOTES, 'UTF-8') . '</p>'
			. '<p>' . htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8') . '</p>';

		try {
			$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), [
				'parameter'     => $this->config->get('config_mail_parameter'),
				'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
				'smtp_username' => $this->config->get('config_mail_smtp_username'),
				'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
				'smtp_port'     => $this->config->get('config_mail_smtp_port'),
				'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
			]);
			$mail->setTo($order['email']);
			$mail->setFrom($from);
			$mail->setSender($store_name);
			$mail->setSubject(sprintf($this->t('pdf_mail_subject'), $number, $store_name));
			$mail->setHtml($body);
			$mail->addAttachment($file);
			$mail->send();
		} finally {
			@unlink($file);
			@rmdir($dir);
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "pdfinvoice_log` SET `order_id` = '" . (int)$order_id . "', `email` = '" . $this->db->escape($order['email']) . "', `source` = '" . $this->db->escape($source) . "', `date_added` = NOW()");

		return '';
	}

	public function lastSent(int $order_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "pdfinvoice_log` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `log_id` DESC LIMIT 1")->row;
	}
}
