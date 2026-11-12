<?php
namespace Opencart\Catalog\Controller\Extension\Lowstock\Module;

class Lowstock extends \Opencart\System\Engine\Controller {
	/**
	 * This extension works through the catalog/view/product/product/before event,
	 * not as a layout block. If someone adds it to a layout anyway, render nothing
	 * instead of throwing "Could not load controller".
	 */
	public function index(): string {
		return '';
	}
}
