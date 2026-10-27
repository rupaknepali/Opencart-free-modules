<?php
namespace Opencart\Admin\Controller\Extension\AiAltText\Module;
/**
 * Class AiAltText
 *
 * AI Image Alt Text for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\AiAltText\Module
 */
class AiAltText extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'ai_alt_text_product' => ['catalog/view/product/product/after', 'output'],
		'ai_alt_text_thumb'   => ['catalog/view/product/thumb/after', 'output']
	];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/ai_alt_text/module/ai_alt_text');

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
			'href' => $this->url->link('extension/ai_alt_text/module/ai_alt_text', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/ai_alt_text/module/ai_alt_text.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$data['module_ai_alt_text_status'] = $this->config->get('module_ai_alt_text_status');
		$data['module_ai_alt_text_api_key'] = $this->config->get('module_ai_alt_text_api_key');
		$data['module_ai_alt_text_model'] = $this->config->get('module_ai_alt_text_model') ?: \Opencart\System\Library\Extension\AiAltText\Claude::MODELS[0];
		$data['module_ai_alt_text_language'] = (array)$this->config->get('module_ai_alt_text_language');
		$data['module_ai_alt_text_instructions'] = $this->config->get('module_ai_alt_text_instructions');

		$data['models'] = \Opencart\System\Library\Extension\AiAltText\Claude::MODELS;

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ai_alt_text/module/ai_alt_text', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('extension/ai_alt_text/module/ai_alt_text');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		$filter_name = (string)($this->request->get['filter_name'] ?? '');
		$filter_missing = (int)($this->request->get['filter_missing'] ?? 0);

		$page = max(1, (int)($this->request->get['page'] ?? 1));

		$limit = 10;

		$filter_data = [
			'filter_name'    => $filter_name,
			'filter_missing' => $filter_missing,
			'start'          => ($page - 1) * $limit,
			'limit'          => $limit
		];

		$this->load->model('extension/ai_alt_text/module/ai_alt_text');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');

		$languages = $this->getActiveLanguages();

		$data['languages'] = $languages;

		$data['products'] = [];

		foreach ($this->model_extension_ai_alt_text_module_ai_alt_text->getProducts($filter_data) as $product) {
			$images = $this->model_extension_ai_alt_text_module_ai_alt_text->getImages((int)$product['product_id'], (string)$product['image']);

			$keys = array_map([$this, 'getKey'], $images);

			$alts = $this->model_extension_ai_alt_text_module_ai_alt_text->getAlts($keys);

			$image_data = [];

			foreach ($images as $index => $image) {
				$key = $keys[$index];

				$image_data[] = [
					'image'  => $image,
					'key'    => $key,
					'thumb'  => is_file(DIR_IMAGE . html_entity_decode($image, ENT_QUOTES, 'UTF-8')) ? $this->model_tool_image->resize(html_entity_decode($image, ENT_QUOTES, 'UTF-8'), 100, 100) : '',
					'alts'   => $alts[$key] ?? [],
					'main'   => $index == 0
				];
			}

			$data['products'][] = [
				'product_id' => $product['product_id'],
				'name'       => $product['name'],
				'images'     => $image_data,
				'edit'       => $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'])
			];
		}

		$total = $this->model_extension_ai_alt_text_module_ai_alt_text->getTotalProducts($filter_data);

		$url = '';

		if ($filter_name !== '') {
			$url .= '&filter_name=' . urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_missing) {
			$url .= '&filter_missing=1';
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/ai_alt_text/module/ai_alt_text.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		$data['filter_name'] = $filter_name;
		$data['filter_missing'] = $filter_missing;

		$data['user_token'] = $this->session->data['user_token'];

		return $this->load->view('extension/ai_alt_text/module/ai_alt_text_list', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/ai_alt_text/module/ai_alt_text');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ai_alt_text/module/ai_alt_text')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		$post['module_ai_alt_text_api_key'] = trim((string)($post['module_ai_alt_text_api_key'] ?? ''));

		if ($post['module_ai_alt_text_api_key'] !== '' && !str_starts_with($post['module_ai_alt_text_api_key'], 'sk-ant-')) {
			$json['error']['api-key'] = $this->language->get('error_api_key');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_ai_alt_text_language'] = array_map('intval', (array)($post['module_ai_alt_text_language'] ?? []));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ai_alt_text', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Save Alt
	 *
	 * Saves the alt texts typed in the image list.
	 *
	 * @return void
	 */
	public function saveAlt(): void {
		$this->load->language('extension/ai_alt_text/module/ai_alt_text');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ai_alt_text/module/ai_alt_text')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/ai_alt_text/module/ai_alt_text');

			$saved = 0;

			foreach ((array)($this->request->post['alt'] ?? []) as $key => $languages) {
				foreach ((array)$languages as $language_id => $value) {
					$value = html_entity_decode((string)$value['alt'], ENT_QUOTES, 'UTF-8');

					$this->model_extension_ai_alt_text_module_ai_alt_text->editAlt((string)$key, (int)$language_id, $value, (string)($this->request->post['source'][$key][$language_id] ?? 'manual') == 'ai' ? 'ai' : 'manual');

					$saved++;
				}
			}

			$json['success'] = sprintf($this->language->get('text_saved'), $saved);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Generate
	 *
	 * Asks Claude for alt text for one image, in every selected language. Nothing is saved: the texts are put in
	 * the form so you can read and edit them, and are saved when you click Save.
	 *
	 * @return void
	 */
	public function generate(): void {
		$this->load->language('extension/ai_alt_text/module/ai_alt_text');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ai_alt_text/module/ai_alt_text')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$api_key = (string)$this->config->get('module_ai_alt_text_api_key');

		if (!$json && $api_key === '') {
			$json['error'] = $this->language->get('error_no_key');
		}

		$product_id = (int)($this->request->post['product_id'] ?? 0);
		$image = html_entity_decode((string)($this->request->post['image'] ?? ''), ENT_QUOTES, 'UTF-8');

		$this->load->model('catalog/product');

		$product_info = $product_id ? $this->model_catalog_product->getProduct($product_id) : [];

		// The image must belong to the product, so this can't be used to read other files.
		if (!$json) {
			$this->load->model('extension/ai_alt_text/module/ai_alt_text');

			$images = array_map(fn ($value) => html_entity_decode($value, ENT_QUOTES, 'UTF-8'), $product_info ? $this->model_extension_ai_alt_text_module_ai_alt_text->getImages($product_id, (string)$product_info['image']) : []);

			if (!in_array($image, $images) || !is_file(DIR_IMAGE . $image)) {
				$json['error'] = $this->language->get('error_image');
			}
		}

		if (!$json) {
			try {
				$json['alts'] = $this->ask($api_key, $image, $product_info);
			} catch (\Throwable $e) {
				$json['error'] = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Ask
	 *
	 * @param string               $api_key
	 * @param string               $image   path below DIR_IMAGE
	 * @param array<string, mixed> $product
	 *
	 * @throws \Exception
	 *
	 * @return array<int, string> language_id => alt text
	 */
	private function ask(string $api_key, string $image, array $product): array {
		[$media_type, $data] = $this->prepareImage(DIR_IMAGE . $image);

		$languages = $this->getActiveLanguages();

		$properties = [];
		$list = [];

		foreach ($languages as $language) {
			$properties[$language['code']] = ['type' => 'string'];
			$list[] = $language['code'] . ' (' . $language['name'] . ')';
		}

		$context = 'Product name: ' . html_entity_decode((string)$product['name'], ENT_QUOTES, 'UTF-8') . "\n";

		if (!empty($product['model'])) {
			$context .= 'Model: ' . html_entity_decode((string)$product['model'], ENT_QUOTES, 'UTF-8') . "\n";
		}

		$instructions = trim((string)$this->config->get('module_ai_alt_text_instructions'));

		$system = "You write alt text for product images in an online store. Alt text is read aloud by screen readers and shown when an image can't load, so it must tell a shopper what the image shows.\n\n"
			. "Rules:\n"
			. "- Describe what is visible: the product type, color, material, angle or view, what is included, and any text that matters.\n"
			. "- Use the product name for brand and model. Don't guess details you can't see in the image.\n"
			. "- Keep it under 125 characters. One phrase or sentence, no full stop needed.\n"
			. "- Don't start with \"Image of\", \"Picture of\" or \"Photo of\". Don't add marketing words like \"stunning\" or \"best\".\n"
			. "- If the image is a size chart, diagram, packaging or a lifestyle scene, say that.\n"
			. "- Write each language naturally, not as a word-for-word translation."
			. ($instructions !== '' ? "\n\nStore instructions:\n" . $instructions : '');

		$claude = new \Opencart\System\Library\Extension\AiAltText\Claude($api_key, (string)$this->config->get('module_ai_alt_text_model'));

		$response = $claude->message([
			'max_tokens'    => 4000,
			'system'        => $system,
			'output_config' => [
				'effort' => 'low',
				'format' => [
					'type'   => 'json_schema',
					'schema' => [
						'type'                 => 'object',
						'properties'           => $properties,
						'required'             => array_keys($properties),
						'additionalProperties' => false
					]
				]
			],
			'messages'      => [
				[
					'role'    => 'user',
					'content' => [
						[
							'type'   => 'image',
							'source' => [
								'type'       => 'base64',
								'media_type' => $media_type,
								'data'       => $data
							]
						],
						[
							'type' => 'text',
							'text' => $context . "\nWrite alt text for this image in: " . implode(', ', $list) . '. Return one string per language code.'
						]
					]
				]
			]
		]);

		$stop_reason = $response['stop_reason'] ?? '';

		if ($stop_reason == 'refusal') {
			throw new \Exception($this->language->get('error_refusal'));
		}

		if ($stop_reason == 'max_tokens') {
			throw new \Exception($this->language->get('error_max_tokens'));
		}

		$result = json_decode(\Opencart\System\Library\Extension\AiAltText\Claude::text($response), true);

		if (!is_array($result)) {
			throw new \Exception($this->language->get('error_response'));
		}

		$alts = [];

		foreach ($languages as $language) {
			$alt = trim((string)($result[$language['code']] ?? ''));

			// Remove quotes the model may wrap around the text, and keep within the column size.
			$alts[(int)$language['language_id']] = oc_substr(trim($alt, " \"'"), 0, 255);
		}

		return $alts;
	}

	/**
	 * Prepare Image
	 *
	 * Scales large images down to 1024 px on the longest side before sending them. Big images cost more
	 * input tokens and don't give better alt text.
	 *
	 * @param string $file
	 *
	 * @throws \Exception
	 *
	 * @return array{0: string, 1: string} media type and base64 data
	 */
	private function prepareImage(string $file): array {
		$info = getimagesize($file);

		if (!$info) {
			throw new \Exception($this->language->get('error_image'));
		}

		[$width, $height] = $info;
		$mime = (string)$info['mime'];

		$supported = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

		if (in_array($mime, $supported) && max($width, $height) <= 1024 && filesize($file) < 4 * 1024 * 1024) {
			return [$mime, base64_encode((string)file_get_contents($file))];
		}

		if (!function_exists('imagecreatefromstring')) {
			throw new \Exception($this->language->get('error_gd'));
		}

		$source = imagecreatefromstring((string)file_get_contents($file));

		if (!$source) {
			throw new \Exception($this->language->get('error_image'));
		}

		$scale = min(1, 1024 / max($width, $height));

		$new_width = max(1, (int)round($width * $scale));
		$new_height = max(1, (int)round($height * $scale));

		$resized = imagecreatetruecolor($new_width, $new_height);

		// White background for transparent PNGs, because the result is a JPEG.
		imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
		imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		ob_start();
		imagejpeg($resized, null, 85);
		$data = (string)ob_get_clean();

		return ['image/jpeg', base64_encode($data)];
	}

	/**
	 * Get Active Languages
	 *
	 * The languages chosen in the settings, or all languages if none are chosen.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getActiveLanguages(): array {
		$this->load->model('localisation/language');

		$selected = (array)$this->config->get('module_ai_alt_text_language');

		$languages = [];

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			if (!$selected || in_array((int)$language['language_id'], $selected)) {
				$languages[] = $language;
			}
		}

		return $languages;
	}

	/**
	 * Get Key
	 *
	 * @param string $image
	 *
	 * @return string image path without its extension
	 */
	public function getKey(string $image): string {
		$image = html_entity_decode($image, ENT_QUOTES, 'UTF-8');

		$position = strrpos($image, '.');

		return $position !== false ? substr($image, 0, $position) : $image;
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

		$this->load->model('extension/ai_alt_text/module/ai_alt_text');

		$this->model_extension_ai_alt_text_module_ai_alt_text->install();

		$this->load->model('setting/event');

		foreach ($this->events as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'AI Image Alt Text',
				'trigger'     => $event[0],
				'action'      => 'extension/ai_alt_text/module/ai_alt_text.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_ai_alt_text', [
			'module_ai_alt_text_status'       => 0,
			'module_ai_alt_text_api_key'      => '',
			'module_ai_alt_text_model'        => \Opencart\System\Library\Extension\AiAltText\Claude::MODELS[0],
			'module_ai_alt_text_language'     => [],
			'module_ai_alt_text_instructions' => ''
		]);
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/event');

		foreach (array_keys($this->events) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		$this->load->model('extension/ai_alt_text/module/ai_alt_text');

		$this->model_extension_ai_alt_text_module_ai_alt_text->uninstall();
	}
}
