<?php
namespace Opencart\Admin\Controller\Extension\Activitylog\Module;
/**
 * Admin Activity Log: who added, changed or deleted what in the admin, and when, with before and after values.
 */
class Activitylog extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/activitylog/module/activitylog';

	private static array $before = [];

	/**
	 * Models call other models (editProduct calls addSeoUrl, deleteLayouts...): only the outermost call is logged.
	 */
	private static int $depth = 0;

	private array $defaults = [
		'module_activitylog_status'    => 0,
		'module_activitylog_retention' => 180,
		'module_activitylog_skip'      => "user/user.addAuthorize\nuser/user.editAuthorize*\nuser/user.addToken\nuser/user.deleteToken*\ntool/notification.*\nsetting/extension.addPath\nsetting/extension.deletePath\ncatalog/product.editRating\nsetting/setting.editValue\nlocalisation/currency.editValueByCode"
	];

	/**
	 * Fields never shown with their values.
	 */
	private string $secret = '/pass|salt|token|secret|key|code|hash|card|cvv|iban/i';

	/**
	 * Fields that change on every save and say nothing.
	 */
	private array $noise = ['date_modified', 'date_added', 'viewed', 'date_login'];

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
		$data['list'] = $this->url->link($this->route . '.list', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_activitylog');

		foreach ($this->defaults as $key => $value) {
			$data[$key] = array_key_exists($key, $saved) ? $saved[$key] : $value;
		}

		$data['users'] = $this->db->query("SELECT DISTINCT `user_id`, `username` FROM `" . DB_PREFIX . "activitylog` ORDER BY `username`")->rows;
		$data['objects'] = array_column($this->db->query("SELECT DISTINCT `object` FROM `" . DB_PREFIX . "activitylog` ORDER BY `object`")->rows, 'object');

		$data['log'] = $this->getList();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function list(): void {
		$this->load->language($this->route);

		$this->response->setOutput($this->getList());
	}

	private function getList(): string {
		$get = $this->request->get;
		$page = max(1, (int)($get['page'] ?? 1));
		$limit = 25;

		$where = ["1"];

		if (!empty($get['filter_user_id'])) {
			$where[] = "`user_id` = '" . (int)$get['filter_user_id'] . "'";
		}

		if (!empty($get['filter_action'])) {
			$where[] = "`action` = '" . $this->db->escape((string)$get['filter_action']) . "'";
		}

		if (!empty($get['filter_object'])) {
			$where[] = "`object` = '" . $this->db->escape((string)$get['filter_object']) . "'";
		}

		if (!empty($get['filter_keyword'])) {
			$keyword = $this->db->escape(str_replace(['%', '_'], ['\%', '\_'], (string)$get['filter_keyword']));

			$where[] = "(`name` LIKE '%" . $keyword . "%' OR `changes` LIKE '%" . $keyword . "%' OR `object_id` = '" . $this->db->escape((string)$get['filter_keyword']) . "')";
		}

		foreach (['from' => '>=', 'to' => '<='] as $key => $op) {
			if (!empty($get['filter_date_' . $key]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$get['filter_date_' . $key])) {
				$where[] = "DATE(`date_added`) " . $op . " '" . $this->db->escape((string)$get['filter_date_' . $key]) . "'";
			}
		}

		$sql = " FROM `" . DB_PREFIX . "activitylog` WHERE " . implode(' AND ', $where);

		$total = (int)$this->db->query("SELECT COUNT(*) AS total" . $sql)->row['total'];

		$data['entries'] = [];

		foreach ($this->db->query("SELECT *" . $sql . " ORDER BY `log_id` DESC LIMIT " . (($page - 1) * $limit) . "," . $limit)->rows as $row) {
			$data['entries'][] = [
				'date'      => date($this->language->get('datetime_format'), strtotime($row['date_added'])),
				'username'  => $row['username'] ?: $this->language->get('text_system'),
				'ip'        => $row['ip'],
				'action'    => $row['action'],
				'object'    => $this->language->get('object_' . str_replace('/', '_', $row['object'])) != 'object_' . str_replace('/', '_', $row['object']) ? $this->language->get('object_' . str_replace('/', '_', $row['object'])) : $row['object'],
				'object_id' => $row['object_id'],
				'name'      => $row['name'],
				'method'    => $row['method'],
				'changes'   => json_decode($row['changes'], true) ?: []
			];
		}

		$url = '';

		foreach (['filter_user_id', 'filter_action', 'filter_object', 'filter_keyword', 'filter_date_from', 'filter_date_to'] as $key) {
			if (!empty($get[$key])) {
				$url .= '&' . $key . '=' . urlencode(html_entity_decode((string)$get[$key], ENT_QUOTES, 'UTF-8'));
			}
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link($this->route . '.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($total, $page * $limit), $total, ceil($total / $limit));

		return $this->load->view('extension/activitylog/module/activitylog_list', $data);
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$post = $this->request->post;

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_activitylog', [
				'module_activitylog_status'    => (int)!empty($post['module_activitylog_status']),
				'module_activitylog_retention' => max(7, min(3650, (int)($post['module_activitylog_retention'] ?? 180))),
				'module_activitylog_skip'      => trim(html_entity_decode((string)($post['module_activitylog_skip'] ?? ''), ENT_QUOTES, 'UTF-8'))
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// ---------------------------------------------------------------- events

	/**
	 * admin/model/*.edit* and *.delete* before: read the record as it is now.
	 */
	public function before(string &$route, array &$args): void {
		// Model events get the model route without the "admin/model/" prefix: catalog/product.editProduct.
		if (!$this->active($route)) {
			return;
		}

		self::$depth++;

		if (self::$depth > 1) {
			return;
		}

		[$model, $method] = explode('.', $route, 2);

		if ($model == 'setting/setting' && $method == 'editSetting') {
			$this->load->model('setting/setting');

			self::$before[$route] = $this->model_setting_setting->getSetting((string)($args[0] ?? ''), (int)($args[2] ?? 0));
		} elseif (str_starts_with($method, 'edit') || str_starts_with($method, 'delete')) {
			self::$before[$route] = $this->read($model, $args[0] ?? null);
		}
	}

	/**
	 * admin/model/*.add*, *.edit*, *.copy*, *.delete* after: write the log entry.
	 */
	public function after(string &$route, array &$args, mixed &$output): void {
		if (!$this->active($route)) {
			return;
		}

		$depth = self::$depth;

		self::$depth = max(0, self::$depth - 1);

		if ($depth > 1) {
			return;
		}

		[$model, $method] = explode('.', $route, 2);

		preg_match('/^(add|edit|copy|delete)/', $method, $match);

		$action = $match[1] ?? 'edit';
		$changes = [];
		$id = '';
		$name = '';

		if ($model == 'setting/setting' && $method == 'editSetting') {
			$old = self::$before[$route] ?? [];

			$this->load->model('setting/setting');

			$new = $this->model_setting_setting->getSetting((string)($args[0] ?? ''), (int)($args[2] ?? 0));

			$changes = $this->diff($old, $new);
			$id = (int)($args[2] ?? 0) ? 'store ' . (int)$args[2] : '';
			$name = (string)($args[0] ?? '');

			// Saving a settings form without changing anything isn't worth an entry.
			if (!$changes) {
				return;
			}
		} elseif ($action == 'add' || $action == 'copy') {
			$id = is_scalar($output) ? (string)$output : '';
			$name = $this->label($action == 'add' ? (array)($args[1] ?? $args[0] ?? []) : [], $model, $id);

			// Logins are a user's own add: record them as a login.
			if ($route == 'user/user.addLogin') {
				$action = 'login';
				$id = (string)($args[0] ?? '');
				$name = $this->user->getUserName();
			}
		} else {
			$old = self::$before[$route] ?? [];
			$id = is_scalar($args[0] ?? null) ? (string)$args[0] : '';

			if ($action == 'edit') {
				$changes = $this->diff($old, $this->read($model, $args[0] ?? null));

				// Follow-up saves that changed nothing (editVariants after editProduct) aren't worth an entry;
				// the main save (editProduct) is logged even without changes, as a record that someone saved.
				if (!$changes && $method != 'edit' . str_replace('_', '', ucwords(basename($model), '_'))) {
					return;
				}
			}

			$name = $this->label($old, $model, $id) ?: $this->label((array)($args[1] ?? []), $model, $id);
		}

		$this->write($action, $model, $id, $name, $method, $changes);
	}

	/**
	 * admin/controller/common/login.login/after: a failed login (successful ones come from user.addLogin).
	 */
	public function login(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_activitylog_status') || $this->user->isLogged()) {
			return;
		}

		$username = oc_substr(trim((string)($this->request->post['username'] ?? '')), 0, 64);

		if ($username !== '') {
			$this->write('login_failed', 'user/user', '', $username, 'login', [], 0, $username);
		}
	}

	/**
	 * admin/controller/sale/order.call/before: the order page runs the storefront API in the same PHP request
	 * (a store instance with its own session), so hand the admin user to the storefront side.
	 */
	public function call(string &$route, array &$args): void {
		$GLOBALS['activitylog_admin'] = ['user_id' => (int)$this->user->getId(), 'username' => (string)$this->user->getUserName()];
	}

	private function active(string $route): bool {
		if (!$this->config->get('module_activitylog_status')) {
			return false;
		}

		$short = $route;

		foreach (preg_split('/\R/', (string)$this->config->get('module_activitylog_skip')) as $pattern) {
			$pattern = trim($pattern);

			if ($pattern !== '' && fnmatch($pattern, $short)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The record as the model's own getter returns it: catalog/product -> getProduct($id), plus the default
	 * language's description where there is one.
	 */
	private function read(string $model, mixed $id): array {
		if (!is_numeric($id) || (int)$id <= 0) {
			return [];
		}

		$getter = 'get' . str_replace('_', '', ucwords(basename($model), '_'));
		$property = 'model_' . str_replace('/', '_', $model);

		try {
			$this->load->model($model);

			$object = $this->{$property};

			$record = (array)$object->{$getter}((int)$id);

			if ($record && in_array($model, ['catalog/product', 'catalog/category', 'catalog/information'])) {
				$descriptions = (array)$object->getDescriptions((int)$id);

				foreach ((array)($descriptions[(int)$this->config->get('config_language_id')] ?? []) as $key => $value) {
					if (in_array($key, ['name', 'title', 'description', 'meta_title', 'meta_description', 'tag'])) {
						$record[$key] = $value;
					}
				}
			}

			return $record;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Changed scalar fields, with secrets masked.
	 */
	private function diff(array $old, array $new): array {
		$changes = [];

		foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
			if (in_array($key, $this->noise, true)) {
				continue;
			}

			$a = $old[$key] ?? null;
			$b = $new[$key] ?? null;

			$a = is_array($a) ? json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $a;
			$b = is_array($b) ? json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $b;

			if ((string)$a === (string)$b || (is_numeric($a) && is_numeric($b) && (float)$a == (float)$b)) {
				continue;
			}

			if (preg_match($this->secret, (string)$key)) {
				$changes[] = [(string)$key, '••••', '••••'];
			} else {
				$changes[] = [(string)$key, $this->short($a), $this->short($b)];
			}

			if (count($changes) >= 50) {
				break;
			}
		}

		return $changes;
	}

	private function short(mixed $value): string {
		$value = html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8');

		return oc_strlen($value) > 120 ? oc_substr($value, 0, 117) . '…' : $value;
	}

	/**
	 * A readable name for the record: its name, title, customer name, code...
	 */
	private function label(array $data, string $model, string $id): string {
		$language_id = (int)$this->config->get('config_language_id');

		foreach (['product_description', 'category_description', 'information_description', 'description'] as $key) {
			if (isset($data[$key][$language_id])) {
				$data += (array)$data[$key][$language_id];
			}
		}

		foreach (['name', 'title', 'username', 'code', 'email'] as $key) {
			if (!empty($data[$key]) && is_scalar($data[$key])) {
				return $this->short($data[$key]);
			}
		}

		if (!empty($data['firstname'])) {
			return $this->short(trim($data['firstname'] . ' ' . ($data['lastname'] ?? '')));
		}

		return '';
	}

	private function write(string $action, string $model, string $id, string $name, string $method, array $changes, ?int $user_id = null, ?string $username = null): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "activitylog` SET
			`user_id` = '" . (int)($user_id ?? $this->user->getId()) . "',
			`username` = '" . $this->db->escape((string)($username ?? $this->user->getUserName())) . "',
			`ip` = '" . $this->db->escape(oc_get_ip()) . "',
			`action` = '" . $this->db->escape($action) . "',
			`object` = '" . $this->db->escape($model) . "',
			`object_id` = '" . $this->db->escape(oc_substr($id, 0, 64)) . "',
			`name` = '" . $this->db->escape(oc_substr($name, 0, 255)) . "',
			`method` = '" . $this->db->escape(oc_substr($method, 0, 64)) . "',
			`changes` = '" . $this->db->escape(json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "',
			`date_added` = NOW()");

		// Old entries go after the retention period. Checked now and then, not on every write.
		if (mt_rand(1, 50) == 1) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "activitylog` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL " . max(7, (int)$this->config->get('module_activitylog_retention')) . " DAY)");
		}
	}

	// ---------------------------------------------------------------- install

	private function events(): array {
		return [
			'activitylog_add_b'      => ['admin/model/*.add*/before', 'before'],
			'activitylog_add'        => ['admin/model/*.add*/after', 'after'],
			'activitylog_copy_b'     => ['admin/model/*.copy*/before', 'before'],
			'activitylog_copy'       => ['admin/model/*.copy*/after', 'after'],
			'activitylog_edit_b'     => ['admin/model/*.edit*/before', 'before'],
			'activitylog_edit'       => ['admin/model/*.edit*/after', 'after'],
			'activitylog_delete_b'   => ['admin/model/*.delete*/before', 'before'],
			'activitylog_delete'     => ['admin/model/*.delete*/after', 'after'],
			'activitylog_login'      => ['admin/controller/common/login.login/after', 'login'],
			'activitylog_call'       => ['admin/controller/sale/order.call/before', 'call'],
			'activitylog_order'      => ['catalog/model/checkout/order.addHistory/after', 'order']
		];
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "activitylog` (
			`log_id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL,
			`username` VARCHAR(64) NOT NULL,
			`ip` VARCHAR(40) NOT NULL,
			`action` VARCHAR(16) NOT NULL,
			`object` VARCHAR(64) NOT NULL,
			`object_id` VARCHAR(64) NOT NULL,
			`name` VARCHAR(255) NOT NULL,
			`method` VARCHAR(64) NOT NULL,
			`changes` MEDIUMTEXT NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`log_id`),
			KEY `user_id` (`user_id`),
			KEY `object` (`object`, `object_id`),
			KEY `date_added` (`date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as $code => [$trigger, $method]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Admin Activity Log',
				'trigger'     => $trigger,
				'action'      => $this->route . '.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_activitylog', $this->defaults);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (array_keys($this->events()) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		// The log itself stays, so uninstalling can't be used to wipe the record. Drop the table by hand if you must.
	}
}
