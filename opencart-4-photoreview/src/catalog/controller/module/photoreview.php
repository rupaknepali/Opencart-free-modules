<?php
namespace Opencart\Catalog\Controller\Extension\Photoreview\Module;
/**
 * Photo Reviews: customers attach photos to their reviews; approved reviews show them, plus a customer photo gallery.
 */
class Photoreview extends \Opencart\System\Engine\Controller {
	private static array $review_ids = [];

	public function index(): string {
		return '';
	}

	/**
	 * In a view/before event $code is only filled when the Theme Editor has an override (event/theme runs at
	 * priority 500, these events at 600). Otherwise read the template file.
	 */
	private function source(string $route, string &$code): void {
		if ($code === '' && is_file(DIR_TEMPLATE . $route . '.twig')) {
			$code = (string)file_get_contents(DIR_TEMPLATE . $route . '.twig');
		}
	}

	private function setting(string $key, $default) {
		$value = $this->config->get('module_photoreview_' . $key);

		return $value === '' || $value === null ? $default : $value;
	}

	// ---------------------------------------------------------------- review form

	/**
	 * catalog/view/product/review/before: add the photo field above the captcha.
	 */
	public function form(string &$route, array &$args, string &$code): void {
		$this->source($route, $code);

		if (!$this->config->get('module_photoreview_status') || empty($args['review_guest']) || !str_contains($code, '{{ captcha }}')) {
			return;
		}

		$this->load->language('extension/photoreview/module/photoreview', 'photoreview');

		$max = max(1, (int)$this->setting('max', 3));

		$this->session->data['photoreview_token'] = oc_token(32);

		$args['photoreview'] = [
			'upload' => $this->url->link('extension/photoreview/module/photoreview.upload', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)($args['product_id'] ?? 0) . '&photoreview_token=' . $this->session->data['photoreview_token']),
			'max'    => $max,
			'size'   => (int)$this->setting('size', 5),
			'label'  => $this->language->get('photoreview_entry_photo'),
			'help'   => sprintf($this->language->get('photoreview_help_photo'), $max, (int)$this->setting('size', 5)),
			'remove' => $this->language->get('photoreview_button_remove'),
			'error'  => sprintf($this->language->get('photoreview_error_max'), $max),
			'accept' => function_exists('imagecreatefromwebp') ? 'image/jpeg,image/png,image/webp' : 'image/jpeg,image/png'
		];

		$field = <<<'TWIG'
<div class="mb-3" id="photoreview">
  <label for="photoreview-input" class="form-label">{{ photoreview.label }}</label>
  <input type="file" id="photoreview-input" accept="{{ photoreview.accept }}" multiple class="form-control"/>
  <div class="form-text">{{ photoreview.help }}</div>
  <div id="photoreview-previews" class="d-flex flex-wrap gap-2 mt-2"></div>
</div>
<script type="text/javascript"><!--
(function() {
    var max = {{ photoreview.max }}, upload = '{{ photoreview.upload|replace({'&amp;': '&'}) }}';

    function count() { return $('#photoreview-previews .photoreview-item').length; }

    $('#photoreview-input').on('change', function() {
        var files = Array.prototype.slice.call(this.files);

        this.value = '';

        files.forEach(function(file) {
            if (count() >= max) {
                alert('{{ photoreview.error|escape('js') }}');

                return;
            }

            var item = $('<div class="photoreview-item position-relative border rounded" style="width: 80px; height: 80px;"><div class="d-flex h-100 align-items-center justify-content-center"><i class="fa-solid fa-circle-notch fa-spin"></i></div></div>').appendTo('#photoreview-previews');
            var data = new FormData();

            data.append('photo', file);

            $.ajax({url: upload, type: 'post', data: data, dataType: 'json', processData: false, contentType: false, cache: false}).done(function(json) {
                if (json['error']) {
                    item.remove();
                    alert(json['error']);

                    return;
                }

                item.html('<img src="' + json['thumb'] + '" alt="" class="rounded w-100 h-100" style="object-fit: cover;"/><input type="hidden" name="photoreview[]" value="' + json['code'] + '"/><button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 py-0 px-1" title="{{ photoreview.remove|e }}">&times;</button>');
            }).fail(function() { item.remove(); });
        });
    });

    $('#photoreview-previews').on('click', 'button', function() { $(this).closest('.photoreview-item').remove(); });

    // After a successful review the core script resets the form: clear the previews too.
    $(document).ajaxSuccess(function(e, xhr, settings) {
        if (settings.url.indexOf('review.write') !== -1 && xhr.responseJSON && xhr.responseJSON['success']) {
            $('#photoreview-previews').empty();
        }
    });
})();
//--></script>
TWIG;

		$code = str_replace('{{ captcha }}', $field . "\n{{ captcha }}", $code);
	}

	/**
	 * The photo upload: checked, re-encoded as JPEG (which also strips EXIF data such as GPS location) and resized.
	 */
	public function upload(): void {
		$this->load->language('extension/photoreview/module/photoreview');

		$json = [];

		$product_id = (int)($this->request->get['product_id'] ?? 0);
		$token = (string)($this->request->get['photoreview_token'] ?? '');

		if (!$this->config->get('module_photoreview_status')) {
			$json['error'] = $this->language->get('error_status');
		} elseif (!$this->config->get('config_review_status') || (!$this->config->get('config_review_guest') && !$this->customer->isLogged())) {
			$json['error'] = $this->language->get('error_status');
		} elseif (!isset($this->session->data['photoreview_token']) || !hash_equals($this->session->data['photoreview_token'], $token)) {
			$json['error'] = $this->language->get('error_token');
		}

		if (!$json) {
			$this->load->model('catalog/product');

			if (!$this->model_catalog_product->getProduct($product_id)) {
				$json['error'] = $this->language->get('error_token');
			}
		}

		$pending = (array)($this->session->data['photoreview_pending'] ?? []);

		if (!$json && count($pending) >= 20) {
			$json['error'] = sprintf($this->language->get('error_max'), (int)$this->setting('max', 3));
		}

		$file = $this->request->files['photo'] ?? null;

		if (!$json && (!$file || !is_uploaded_file($file['tmp_name']) || $file['error'] != UPLOAD_ERR_OK)) {
			$json['error'] = $this->language->get('error_upload');
		}

		if (!$json && $file['size'] > (int)$this->setting('size', 5) * 1024 * 1024) {
			$json['error'] = sprintf($this->language->get('error_size'), (int)$this->setting('size', 5));
		}

		$image = null;

		if (!$json) {
			$info = @getimagesize($file['tmp_name']);
			$type = $info[2] ?? 0;

			if ($type == IMAGETYPE_JPEG) {
				$image = @imagecreatefromjpeg($file['tmp_name']);
			} elseif ($type == IMAGETYPE_PNG) {
				$image = @imagecreatefrompng($file['tmp_name']);
			} elseif ($type == IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
				$image = @imagecreatefromwebp($file['tmp_name']);
			}

			// Refuse huge images before GD runs out of memory on them.
			if ($info && $info[0] * $info[1] > 40000000) {
				$image = null;
			}

			if (!$image) {
				$json['error'] = $this->language->get('error_type');
			}
		}

		if (!$json) {
			// Phone photos are often stored sideways with an EXIF rotation flag: apply it before the EXIF data is dropped.
			if ($type == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
				$orientation = (int)(@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
				$angles = [3 => 180, 6 => -90, 8 => 90];

				if (isset($angles[$orientation])) {
					$image = imagerotate($image, $angles[$orientation], 0);
				}
			}

			$width = imagesx($image);
			$height = imagesy($image);
			$scale = min(1, 1600 / max($width, $height));

			$out = imagecreatetruecolor(max(1, (int)round($width * $scale)), max(1, (int)round($height * $scale)));

			// Transparent PNGs get a white background.
			imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
			imagecopyresampled($out, $image, 0, 0, 0, 0, imagesx($out), imagesy($out), $width, $height);

			$folder = 'catalog/photoreview/' . date('Y-m') . '/';

			if (!is_dir(DIR_IMAGE . $folder)) {
				@mkdir(DIR_IMAGE . $folder, 0755, true);
			}

			$code = oc_token(32);
			$filename = $folder . $code . '.jpg';

			if (!@imagejpeg($out, DIR_IMAGE . $filename, 85)) {
				$json['error'] = $this->language->get('error_upload');
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "photoreview` SET `review_id` = '0', `product_id` = '" . $product_id . "', `code` = '" . $this->db->escape($code) . "', `filename` = '" . $this->db->escape($filename) . "', `date_added` = NOW()");

				$this->session->data['photoreview_pending'][$code] = $product_id;

				$this->load->model('tool/image');

				$json['code'] = $code;
				$json['thumb'] = $this->model_tool_image->resize($filename, 160, 160);
			}

			$this->cleanup();
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Photos uploaded but never sent with a review are deleted after a day.
	 */
	private function cleanup(): void {
		$query = $this->db->query("SELECT `photo_id`, `filename` FROM `" . DB_PREFIX . "photoreview` WHERE `review_id` = '0' AND `date_added` < DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 50");

		foreach ($query->rows as $row) {
			if (str_starts_with($row['filename'], 'catalog/photoreview/') && is_file(DIR_IMAGE . $row['filename'])) {
				@unlink(DIR_IMAGE . $row['filename']);
			}

			$this->db->query("DELETE FROM `" . DB_PREFIX . "photoreview` WHERE `photo_id` = '" . (int)$row['photo_id'] . "'");
		}
	}

	/**
	 * catalog/model/catalog/review.addReview/after: attach this visitor's uploaded photos to the new review.
	 */
	public function attach(string &$route, array &$args, mixed &$output): void {
		$review_id = (int)$output;
		$product_id = (int)($args[0] ?? 0);
		$pending = (array)($this->session->data['photoreview_pending'] ?? []);
		$codes = array_slice(array_unique((array)($this->request->post['photoreview'] ?? [])), 0, max(1, (int)$this->setting('max', 3)));

		if (!$review_id || !$this->config->get('module_photoreview_status')) {
			return;
		}

		foreach ($codes as $code) {
			$code = (string)$code;

			// Only photos this visitor uploaded, for this product.
			if (isset($pending[$code]) && (int)$pending[$code] == $product_id) {
				$this->db->query("UPDATE `" . DB_PREFIX . "photoreview` SET `review_id` = '" . $review_id . "' WHERE `code` = '" . $this->db->escape($code) . "' AND `review_id` = '0' AND `product_id` = '" . $product_id . "'");
			}
		}

		unset($this->session->data['photoreview_pending']);
	}

	// ---------------------------------------------------------------- showing photos

	/**
	 * catalog/model/catalog/review.getReviewsByProductId/after: remember which reviews the list will show, in order.
	 */
	public function collect(string &$route, array &$args, mixed &$output): void {
		self::$review_ids = [];

		$rows = (array)$output;

		if (!$rows || !$this->config->get('module_photoreview_status')) {
			return;
		}

		// The core query doesn't return review_id, so match each row by author, date and text.
		$query = $this->db->query("SELECT `review_id`, `author`, `text`, `date_added` FROM `" . DB_PREFIX . "review` WHERE `product_id` = '" . (int)($args[0] ?? 0) . "' AND `status` = '1' AND `review_id` IN (SELECT `review_id` FROM `" . DB_PREFIX . "photoreview`)");

		foreach ($rows as $i => $row) {
			self::$review_ids[$i] = 0;

			foreach ($query->rows as $review) {
				if ($review['author'] === $row['author'] && $review['date_added'] === $row['date_added'] && $review['text'] === $row['text']) {
					self::$review_ids[$i] = (int)$review['review_id'];

					break;
				}
			}
		}
	}

	/**
	 * catalog/view/product/review_list/before: photos under each review, and the customer photo gallery on page 1.
	 */
	public function show(string &$route, array &$args, string &$code): void {
		if (!$this->config->get('module_photoreview_status')) {
			return;
		}

		$this->source($route, $code);

		$this->load->language('extension/photoreview/module/photoreview', 'photoreview');
		$this->load->model('tool/image');

		$thumb = max(40, (int)$this->setting('thumb', 100));
		$photos = [];

		if (array_filter(self::$review_ids) && !empty($args['reviews'])) {
			$query = $this->db->query("SELECT `review_id`, `filename` FROM `" . DB_PREFIX . "photoreview` WHERE `review_id` IN (" . implode(',', array_filter(self::$review_ids)) . ") ORDER BY `photo_id`");

			foreach ($query->rows as $row) {
				$photos[(int)$row['review_id']][] = $this->photo($row['filename'], $thumb);
			}

			foreach (array_keys($args['reviews']) as $i) {
				$args['reviews'][$i]['photos'] = $photos[self::$review_ids[$i] ?? 0] ?? [];
			}
		}

		$args['photoreview_gallery'] = [];
		$args['photoreview_title'] = $this->language->get('photoreview_text_gallery');

		$product_id = (int)($this->request->get['product_id'] ?? 0);
		$page = (int)($this->request->get['page'] ?? 1);

		if ($this->setting('gallery', 1) && $product_id && $page <= 1) {
			$query = $this->db->query("SELECT p.`filename` FROM `" . DB_PREFIX . "photoreview` p INNER JOIN `" . DB_PREFIX . "review` r ON (r.`review_id` = p.`review_id`) WHERE p.`product_id` = '" . $product_id . "' AND r.`status` = '1' ORDER BY p.`photo_id` DESC LIMIT 12");

			foreach ($query->rows as $row) {
				$args['photoreview_gallery'][] = $this->photo($row['filename'], 72);
			}
		}

		$item = '<a href="{{ photo.popup }}" class="photoreview-open d-inline-block me-1 mb-1"><img src="{{ photo.thumb }}" alt="" loading="lazy" class="rounded border" width="%1$d" height="%1$d" style="object-fit: cover;"/></a>';

		$under = '{% if review.photos %}<div class="photoreview-photos mb-2">{% for photo in review.photos %}' . sprintf($item, $thumb) . '{% endfor %}</div>{% endif %}';

		$code = str_replace('<td colspan="2"><p>{{ review.text }}</p>', '<td colspan="2"><p>{{ review.text }}</p>' . $under, $code);

		$gallery = '{% if photoreview_gallery %}<div class="photoreview-gallery mb-3"><h3 class="h5">{{ photoreview_title }}</h3>{% for photo in photoreview_gallery %}' . sprintf($item, 72) . '{% endfor %}</div>{% endif %}' . "\n";

		$code = $gallery . $code . <<<'HTML'

<script type="text/javascript"><!--
if (!window.photoreviewInit) {
    window.photoreviewInit = true;

    $(document).on('click', '.photoreview-open', function(e) {
        e.preventDefault();

        if (!$('#photoreview-modal').length) {
            $('body').append('<div id="photoreview-modal" class="modal fade" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content bg-transparent border-0"><button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button><img src="" alt="" class="img-fluid rounded mx-auto d-block"/></div></div></div>');
        }

        $('#photoreview-modal img').attr('src', this.href);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('photoreview-modal')).show();
    });
}
//--></script>
HTML;
	}

	private function photo(string $filename, int $size): array {
		return [
			'thumb' => $this->model_tool_image->resize($filename, $size, $size),
			'popup' => $this->config->get('config_url') . 'image/' . $filename
		];
	}
}
