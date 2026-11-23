<?php
namespace Opencart\Catalog\Controller\Extension\Webocreationtheme\Startup;
/**
 * Webocreation theme: the default OpenCart templates, restyled from the theme's settings (colors, fonts, layout).
 *
 * Runs as a startup action. When the store's theme is Webocreation Theme, it hooks the header and footer views.
 */
class Webocreationtheme extends \Opencart\System\Engine\Controller {
	public function index(): void {
		if ($this->config->get('config_theme') != 'webocreationtheme' || !$this->config->get('theme_webocreationtheme_status')) {
			return;
		}

		$this->event->register('view/common/header/after', new \Opencart\System\Engine\Action('extension/webocreationtheme/startup/webocreationtheme.header'));
		$this->event->register('view/common/footer/before', new \Opencart\System\Engine\Action('extension/webocreationtheme/startup/webocreationtheme.footerData'));
		$this->event->register('view/common/footer/after', new \Opencart\System\Engine\Action('extension/webocreationtheme/startup/webocreationtheme.footer'));
	}

	private function get(string $key, $default = '') {
		$value = $this->config->get('theme_webocreationtheme_' . $key);

		return $value === '' || $value === null ? $default : $value;
	}

	/**
	 * Fonts the theme offers: name => [CSS stack, Google Fonts family or '' for system fonts].
	 */
	public static function fonts(): array {
		$sans = ', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
		$serif = ', Georgia, "Times New Roman", serif';

		return [
			'system'            => ['system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif', ''],
			'georgia'           => ['Georgia, "Times New Roman", serif', ''],
			'Inter'             => ['"Inter"' . $sans, 'Inter'],
			'Roboto'            => ['"Roboto"' . $sans, 'Roboto'],
			'Open Sans'         => ['"Open Sans"' . $sans, 'Open+Sans'],
			'Lato'              => ['"Lato"' . $sans, 'Lato'],
			'Montserrat'        => ['"Montserrat"' . $sans, 'Montserrat'],
			'Poppins'           => ['"Poppins"' . $sans, 'Poppins'],
			'Nunito'            => ['"Nunito"' . $sans, 'Nunito'],
			'Raleway'           => ['"Raleway"' . $sans, 'Raleway'],
			'Work Sans'         => ['"Work Sans"' . $sans, 'Work+Sans'],
			'DM Sans'           => ['"DM Sans"' . $sans, 'DM+Sans'],
			'Manrope'           => ['"Manrope"' . $sans, 'Manrope'],
			'Plus Jakarta Sans' => ['"Plus Jakarta Sans"' . $sans, 'Plus+Jakarta+Sans'],
			'Outfit'            => ['"Outfit"' . $sans, 'Outfit'],
			'Rubik'             => ['"Rubik"' . $sans, 'Rubik'],
			'Oswald'            => ['"Oswald"' . $sans, 'Oswald'],
			'Playfair Display'  => ['"Playfair Display"' . $serif, 'Playfair+Display'],
			'Merriweather'      => ['"Merriweather"' . $serif, 'Merriweather'],
			'Lora'              => ['"Lora"' . $serif, 'Lora'],
			'Libre Baskerville' => ['"Libre Baskerville"' . $serif, 'Libre+Baskerville']
		];
	}

	/**
	 * view/common/header/after: fonts, the color and size variables, body classes, and the theme stylesheet.
	 */
	public function header(string &$route, array &$args, mixed &$output): void {
		$fonts = self::fonts();

		$body_font = isset($fonts[$this->get('font_body')]) ? $this->get('font_body') : 'Inter';
		$heading_font = isset($fonts[$this->get('font_heading')]) ? $this->get('font_heading') : $body_font;

		if ($this->get('font_source', 'google') == 'system') {
			// No requests to Google: map each font to the closest system stack.
			// Serif fonts fall back to Georgia, the rest to the system UI font.
			$body_stack = $fonts[str_contains($fonts[$body_font][0], 'Georgia') ? 'georgia' : 'system'][0];
			$heading_stack = $fonts[str_contains($fonts[$heading_font][0], 'Georgia') ? 'georgia' : 'system'][0];
			$families = [];
		} else {
			$body_stack = $fonts[$body_font][0];
			$heading_stack = $fonts[$heading_font][0];

			$weight = (int)$this->get('heading_weight', 700);
			$families = [];

			foreach (array_unique([$body_font, $heading_font]) as $font) {
				if ($fonts[$font][1] !== '') {
					$weights = array_unique([400, 600, 700, $weight]);

					sort($weights);

					$families[] = 'family=' . $fonts[$font][1] . ':wght@' . implode(';', $weights);
				}
			}
		}

		$head = '';

		if ($families) {
			$head .= '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
			$head .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
			$head .= '<link href="https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap" rel="stylesheet">' . "\n";
		}

		$head .= '<link href="' . $this->config->get('config_url') . 'extension/webocreationtheme/catalog/view/stylesheet/webocreationtheme.css?v=1.2.0" rel="stylesheet">' . "\n";
		$head .= '<style id="webocreationtheme-vars">' . $this->variables($body_stack, $heading_stack) . '</style>' . "\n";

		$css = trim((string)$this->get('custom_css'));

		if ($css !== '') {
			// Saved as plain CSS; never let it close the style tag.
			$head .= '<style id="webocreationtheme-custom">' . str_ireplace('</style', '<\/style', $css) . '</style>' . "\n";
		}

		if (($pos = stripos($output, '</head>')) !== false) {
			$output = substr_replace($output, $head, $pos, 0);
		}

		// Body classes switch the layout options on.
		$classes = ['wt'];

		$classes[] = 'wt-card-' . (in_array($this->get('card_style'), ['border', 'shadow', 'flat']) ? $this->get('card_style') : 'border');

		foreach (['sticky' => 'wt-sticky-', 'logo' => 'wt-logo-'] as $key => $prefix) {
			$value = $this->get($key, $key == 'sticky' ? 'off' : 'left');

			if (preg_match('/^[a-z]+$/', $value)) {
				$classes[] = $prefix . $value;
			}
		}

		foreach (['topbar' => 'wt-no-topbar', 'wishlist' => 'wt-no-wishlist', 'compare' => 'wt-no-compare'] as $key => $class) {
			if ($this->get($key, 1) === '0' || $this->get($key, 1) === 0) {
				$classes[] = $class;
			}
		}

		if ($this->get('card_hover', 1)) {
			$classes[] = 'wt-card-hover';
		}

		foreach (['heading_style' => ['underline', 'accent', 'centered', 'boxed'], 'badge_style' => ['ribbon'], 'tabs_style' => ['boxed'], 'menu_style' => ['icons']] as $key => $values) {
			if (in_array($this->get($key), $values, true)) {
				$classes[] = 'wt-' . str_replace('_style', '', $key) . '-' . $this->get($key);
			}
		}

		foreach (['breadcrumb_band' => 'wt-band', 'wide_cart' => 'wt-wide-cart', 'logo_white' => 'wt-logo-white', 'card_compact' => 'wt-card-compact', 'header_compact' => 'wt-header-compact'] as $key => $class) {
			if ($this->get($key, 0)) {
				$classes[] = $class;
			}
		}

		if ($this->get('heading_case') == 'uppercase') {
			$classes[] = 'wt-headings-upper';
		}

		if ($this->get('footer_layout') == 'store') {
			$classes[] = 'wt-footer-store';
		}

		if ($this->get('button_case') == 'uppercase') {
			$classes[] = 'wt-buttons-upper';
		}

		$output = preg_replace('/<body([^>]*)>/', '<body$1 class="' . implode(' ', $classes) . '">', $output, 1);

		if ($this->get('menu_style') == 'icons' && ($pos = strpos($output, '</header>')) !== false) {
			$output = substr_replace($output, $this->iconBar(), $pos + strlen('</header>'), 0);
		}

		// Dark scheme: Bootstrap 5.3 switches forms, dropdowns, tables and modals to dark too.
		if ($this->get('scheme') == 'dark') {
			$output = preg_replace('/<html([^>]*)>/', '<html$1 data-bs-theme="dark">', $output, 1);
		}
	}

	private function variables(string $body_stack, string $heading_stack): string {
		$colors = [
			'primary'     => '#229ac8', 'primary_text' => '#ffffff', 'secondary' => '#f0ad4e',
			'background'  => '#ffffff', 'surface' => '#ffffff', 'text' => '#333333', 'muted' => '#6c757d',
			'link'        => '#229ac8', 'link_hover' => '#1a7ba1', 'border' => '#dddddd',
			'topbar_bg'   => '#eeeeee', 'topbar_text' => '#555555', 'header_bg' => '#ffffff', 'header_text' => '#333333',
			'menu_bg'     => '#229ac8', 'menu_text' => '#ffffff', 'footer_bg' => '#303030', 'footer_text' => '#e2e2e2',
			'catbar_bg'   => '#ffffff', 'catbar_text' => '#333333',
			'price'       => '#333333', 'special' => '#dc3545'
		];

		$css = ':root{';

		foreach ($colors as $key => $default) {
			$value = (string)$this->get('color_' . $key, $default);

			if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
				$value = $default;
			}

			$css .= '--wt-' . str_replace('_', '-', $key) . ':' . $value . ';';

			if (in_array($key, ['primary', 'link', 'text', 'background', 'catbar_text'])) {
				$css .= '--wt-' . str_replace('_', '-', $key) . '-rgb:' . implode(',', sscanf($value, '#%02x%02x%02x')) . ';';
			}
		}

		$container = ['1140' => '1140px', '1320' => '1320px', '1500' => '1500px', 'fluid' => '100%'][$this->get('container', '1320')] ?? '1320px';

		$css .= '--wt-font-body:' . $body_stack . ';';
		$css .= '--wt-font-heading:' . $heading_stack . ';';
		$css .= '--wt-font-size:' . max(13, min(20, (int)$this->get('font_size', 15))) . 'px;';
		$css .= '--wt-line-height:' . max(1.2, min(2, (float)$this->get('line_height', 1.6))) . ';';
		$css .= '--wt-heading-weight:' . (in_array((int)$this->get('heading_weight', 700), [300, 400, 500, 600, 700, 800]) ? (int)$this->get('heading_weight', 700) : 700) . ';';
		$css .= '--wt-heading-spacing:' . max(-0.05, min(0.2, (float)$this->get('heading_spacing', 0))) . 'em;';
		$css .= '--wt-radius:' . max(0, min(24, (int)$this->get('radius', 6))) . 'px;';
		$css .= '--wt-button-radius:' . max(0, min(999, (int)$this->get('button_radius', 6))) . 'px;';
		$css .= '--wt-container:' . $container . ';';

		return $css . '}';
	}

	/**
	 * Font Awesome icons offered for categories, and the words that pick one automatically.
	 */
	public static function icons(): array {
		return [
			'fa-mobile-screen-button' => 'phone|mobile|smartphone|pda', 'fa-tablet-screen-button' => 'tablet|ipad',
			'fa-laptop' => 'laptop|notebook|macbook', 'fa-desktop' => 'desktop|computer|\\bpc\\b|monitor',
			'fa-camera' => 'camera|photo', 'fa-tv' => 'tv|television', 'fa-print' => 'print', 'fa-volume-high' => 'speaker|audio|sound',
			'fa-headphones' => 'headphone|mp3|music|earbud', 'fa-clock' => 'watch|clock', 'fa-microchip' => 'component|hardware|chip|part',
			'fa-compact-disc' => 'software|disc|media', 'fa-plug' => 'accessor|cable|charger', 'fa-gamepad' => 'game|console',
			'fa-book' => 'book', 'fa-shirt' => 'cloth|fashion|shirt|apparel', 'fa-shoe-prints' => 'shoe|footwear',
			'fa-couch' => 'furniture|home|living', 'fa-kitchen-set' => 'kitchen|cook', 'fa-puzzle-piece' => 'toy|kid|baby',
			'fa-futbol' => 'sport|fitness|outdoor', 'fa-spa' => 'beauty|health|care', 'fa-gem' => 'jewel|ring', 'fa-tag' => ''
		];
	}

	public static function iconFor(string $name): string {
		foreach (self::icons() as $icon => $words) {
			if ($words !== '' && preg_match('/' . $words . '/i', $name)) {
				return $icon;
			}
		}

		return 'fa-tag';
	}

	/**
	 * A row of top-level categories with an icon (or the category image) above each name.
	 */
	private function iconBar(): string {
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$chosen = (array)$this->get('cat_icons', []);
		$images = $this->get('icon_source') == 'images';
		$language = 'language=' . $this->config->get('config_language');

		$items = '';

		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$name = html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8');
			$icon = $chosen[$category['category_id']] ?? '';

			if (!array_key_exists($icon, self::icons())) {
				$icon = self::iconFor($name);
			}

			if ($images && $category['image'] && is_file(DIR_IMAGE . $category['image'])) {
				$visual = '<img src="' . $this->model_tool_image->resize($category['image'], 64, 64) . '" alt="" width="40" height="40" loading="lazy">';
			} else {
				$visual = '<i class="fa-solid ' . $icon . '" aria-hidden="true"></i>';
			}

			$items .= '<li><a href="' . $this->url->link('product/category', $language . '&path=' . (int)$category['category_id']) . '"><span class="wt-catbar-icon">' . $visual . '</span><span class="wt-catbar-name">' . htmlspecialchars($name) . '</span></a></li>';
		}

		return $items ? '<nav class="wt-catbar" aria-label="Categories"><div class="container"><ul>' . $items . '</ul></div></nav>' : '';
	}

	/**
	 * view/common/footer/before: your own footer line in place of "Powered by OpenCart".
	 */
	public function footerData(string &$route, array &$args, string &$code): void {
		$text = trim((string)$this->get('footer_text'));

		if ($text !== '') {
			$args['powered'] = strtr(htmlspecialchars($text), ['{year}' => date('Y'), '{store}' => htmlspecialchars(html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8'))]);
		}
	}

	/**
	 * view/common/footer/after: social links and the back-to-top button.
	 */
	public function footer(string &$route, array &$args, mixed &$output): void {
		$networks = [
			'facebook'  => ['Facebook', 'fa-facebook-f'],
			'instagram' => ['Instagram', 'fa-instagram'],
			'x'         => ['X', 'fa-x-twitter'],
			'youtube'   => ['YouTube', 'fa-youtube'],
			'tiktok'    => ['TikTok', 'fa-tiktok'],
			'pinterest' => ['Pinterest', 'fa-pinterest-p'],
			'linkedin'  => ['LinkedIn', 'fa-linkedin-in']
		];

		$social = '';

		foreach ($networks as $key => [$name, $icon]) {
			$url = (string)$this->get('social_' . $key);

			if (preg_match('#^https://#', $url)) {
				$social .= '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" aria-label="' . $name . '"><i class="fa-brands ' . $icon . '"></i></a>';
			}
		}

		$add = '';

		if ($this->get('footer_layout') == 'store') {
			$this->storeFooter($args, $output, $social);
		} elseif ($social) {
			$add .= '<div class="wt-social">' . $social . '</div>';
		}

		// Account form opened from the footer's newsletter box: fill in the email and tick the newsletter switch.
		if (($this->request->get['route'] ?? '') == 'account/register' && !empty($this->request->get['email']) && !$this->customer->isLogged()) {
			$email = (string)$this->request->get['email'];

			if (filter_var(html_entity_decode($email, ENT_QUOTES, 'UTF-8'), FILTER_VALIDATE_EMAIL)) {
				$add .= '<script>(function(){var e=document.getElementById("input-email"),n=document.getElementById("input-newsletter");if(e&&!e.value){e.value=' . json_encode(html_entity_decode($email, ENT_QUOTES, 'UTF-8'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';}if(n){n.checked=true;}})();</script>';
			}
		}

		if ($this->get('back_to_top', 1)) {
			$add .= '<button type="button" id="wt-top" class="wt-top" aria-label="Back to top" hidden><i class="fa-solid fa-arrow-up"></i></button>'
				. '<script>(function(){var b=document.getElementById("wt-top");if(!b)return;window.addEventListener("scroll",function(){b.hidden=window.scrollY<600;},{passive:true});b.addEventListener("click",function(){window.scrollTo({top:0,behavior:"smooth"});});})();</script>';
		}

		// Sticky header and menu: the menu sticks just under the header, whatever its height, and the icon bar
		// drops its icons once the page is scrolled (with a gap between the two thresholds so it can't flicker).
		if ($this->get('sticky') == 'both') {
			$add .= '<script>(function(){var h=document.querySelector("header");if(!h)return;var r=document.documentElement,b=document.body;function m(){r.style.setProperty("--wt-header-h",h.offsetHeight+"px");}m();if(window.ResizeObserver){new ResizeObserver(m).observe(h);}else{window.addEventListener("resize",m);}window.addEventListener("scroll",function(){var y=window.scrollY;if(y>220){b.classList.add("wt-scrolled");}else if(y<60){b.classList.remove("wt-scrolled");}},{passive:true});})();</script>';
		}

		// Product page: - and + buttons around the quantity field.
		if ($this->get('qty_stepper', 0) && ($this->request->get['route'] ?? '') == 'product/product') {
			$add .= '<script>(function(){var q=document.getElementById("input-quantity");if(!q)return;function b(t,d,l){var e=document.createElement("button");e.type="button";e.className="btn btn-light wt-qty";e.textContent=t;e.setAttribute("aria-label",l);e.addEventListener("click",function(){var v=(parseInt(q.value,10)||1)+d;q.value=v<1?1:v;q.dispatchEvent(new Event("change",{bubbles:true}));});return e;}q.parentNode.insertBefore(b("\u2212",-1,"Decrease quantity"),q);q.parentNode.insertBefore(b("+",1,"Increase quantity"),q.nextSibling);q.classList.add("wt-qty-input");})();</script>';
		}

		if ($add && ($pos = strripos($output, '</footer>')) !== false) {
			$output = substr_replace($output, $add, $pos, 0);
		}
	}

	/**
	 * Store footer layout: logo and description, OpenCart's footer links, contact details, newsletter box
	 * and a bottom bar, in place of the default <footer> block. The links come from common/footer's own data.
	 */
	private function storeFooter(array $args, string &$output, string $social): void {
		$start = strpos($output, '<footer>');
		$end = strpos($output, '</footer>');

		if ($start === false || $end === false || $end < $start) {
			return;
		}

		$lang = $this->load->language('extension/webocreationtheme/theme/webocreationtheme');

		$data = $args;

		foreach ($lang as $key => $value) {
			if (str_starts_with($key, 'text_wt_')) {
				$data[$key] = $value;
			}
		}

		// Store settings arrive HTML-encoded; decode, then escape once for the page.
		$plain = fn(string $value): string => trim(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));

		$data['name'] = htmlspecialchars($plain((string)$this->config->get('config_name')));
		$data['home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));
		$data['logo'] = is_file(DIR_IMAGE . $this->config->get('config_logo')) ? $this->config->get('config_url') . 'image/' . $this->config->get('config_logo') : '';
		$data['about'] = nl2br(htmlspecialchars($plain((string)$this->get('footer_about'))));

		$telephone = $plain((string)$this->config->get('config_telephone'));
		$email = $plain((string)$this->config->get('config_email'));

		$data['telephone'] = htmlspecialchars($telephone);
		$data['telephone_link'] = preg_replace('/[^0-9+]/', '', $telephone);
		$data['email'] = filter_var($email, FILTER_VALIDATE_EMAIL) ? htmlspecialchars($email) : '';
		$data['address'] = nl2br(htmlspecialchars($plain((string)$this->config->get('config_address'))));

		$data['newsletter_box'] = (bool)$this->get('footer_newsletter', 1);
		$data['logged'] = $this->customer->isLogged();
		$data['subscribed'] = $data['logged'] && $this->customer->getNewsletter();
		$data['register_action'] = $this->config->get('config_url') . 'index.php';
		$data['language'] = htmlspecialchars((string)$this->config->get('config_language'));
		$data['social'] = $social;

		$html = $this->load->view('extension/webocreationtheme/common/store_footer', $data);

		$output = substr_replace($output, $html, $start, $end + strlen('</footer>') - $start);
	}
}
