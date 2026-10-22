<?php
namespace Opencart\Catalog\Controller\Extension\ProductSchema\Module;
/**
 * Class ProductSchema
 *
 * Product Structured Data (JSON-LD) for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/view/product/product/after  -> product
 *   catalog/view/product/category/after -> category
 *   catalog/view/common/home/after      -> home
 *
 * @package Opencart\Catalog\Controller\Extension\ProductSchema\Module
 */
class ProductSchema extends \Opencart\System\Engine\Controller {
	/**
	 * Product
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function product(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_product_schema_status') || empty($data['product_id'])) {
			return;
		}

		try {
			$this->load->model('extension/product_schema/module/product_schema');

			$graphs = [];

			if ($this->config->get('module_product_schema_product')) {
				$graphs[] = $this->model_extension_product_schema_module_product_schema->getProductSchema((int)$data['product_id'], (array)($data['breadcrumbs'] ?? []), (string)($data['manufacturer'] ?? ''));
			}

			if ($this->config->get('module_product_schema_breadcrumb')) {
				$graphs[] = $this->model_extension_product_schema_module_product_schema->getBreadcrumbSchema((array)($data['breadcrumbs'] ?? []));
			}

			$this->inject($output, $graphs);
		} catch (\Throwable $e) {
			$this->log->write('Product Structured Data: ' . $e->getMessage());
		}
	}

	/**
	 * Category
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function category(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_product_schema_status') || !$this->config->get('module_product_schema_breadcrumb') || empty($data['breadcrumbs'])) {
			return;
		}

		try {
			$this->load->model('extension/product_schema/module/product_schema');

			$this->inject($output, [$this->model_extension_product_schema_module_product_schema->getBreadcrumbSchema((array)$data['breadcrumbs'])]);
		} catch (\Throwable $e) {
			$this->log->write('Product Structured Data: ' . $e->getMessage());
		}
	}

	/**
	 * Home
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function home(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_product_schema_status')) {
			return;
		}

		try {
			$this->load->model('extension/product_schema/module/product_schema');

			$graphs = [];

			if ($this->config->get('module_product_schema_organization')) {
				$graphs[] = $this->model_extension_product_schema_module_product_schema->getOrganizationSchema();
			}

			if ($this->config->get('module_product_schema_website')) {
				$graphs[] = $this->model_extension_product_schema_module_product_schema->getWebsiteSchema();
			}

			$this->inject($output, $graphs);
		} catch (\Throwable $e) {
			$this->log->write('Product Structured Data: ' . $e->getMessage());
		}
	}

	/**
	 * Inject
	 *
	 * Adds one <script type="application/ld+json"> per schema before </head> (or </body> as a fallback).
	 *
	 * @param string                           $output
	 * @param array<int, array<string, mixed>> $graphs
	 *
	 * @return void
	 */
	private function inject(string &$output, array $graphs): void {
		$html = '';

		foreach (array_filter($graphs) as $graph) {
			// JSON_HEX_TAG turns < and > into < and > so "</script>" in a description can't end the block.
			$json = json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE);

			if ($json) {
				$html .= '<script type="application/ld+json">' . $json . '</script>' . "\n";
			}
		}

		if (!$html) {
			return;
		}

		foreach (['</head>', '</body>'] as $tag) {
			$position = stripos($output, $tag);

			if ($position !== false) {
				$output = substr_replace($output, $html, $position, 0);

				return;
			}
		}

		$output .= $html;
	}
}
