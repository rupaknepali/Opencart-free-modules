<?php
namespace Opencart\Catalog\Controller\Extension\Indexnow\Module;
/**
 * IndexNow key file for stores whose root folder isn't writable: index.php?route=extension/indexnow/module/indexnow.key
 */
class Indexnow extends \Opencart\System\Engine\Controller {
	public function index(): string {
		return '';
	}

	public function key(): void {
		$key = (string)$this->config->get('module_indexnow_key');

		if (!$this->config->get('module_indexnow_status') || !$key) {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
			$this->response->setOutput('');

			return;
		}

		$this->response->addHeader('Content-Type: text/plain; charset=utf-8');
		$this->response->setOutput($key);
	}
}
