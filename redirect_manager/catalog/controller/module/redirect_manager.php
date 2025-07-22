<?php
namespace Opencart\Catalog\Controller\Extension\RedirectManager\Module;
class RedirectManager extends \Opencart\System\Engine\Controller {
    public function handler(string &$route, array &$args): void {
        if (isset($this->request->get['_route_'])) {
            $this->load->model('extension/redirect_manager/module/redirect_manager');

            $url = $this->request->get['_route_'];
            $redirects = $this->model_extension_redirect_manager_module_redirect_manager->getActiveRedirects();

            foreach ($redirects as $redirect) {
                if ($this->matchUrl($url, $redirect)) {
                    $to_url = $redirect['to_url'];
                    if ($redirect['is_regex']) {
                        $to_url = preg_replace('/' . str_replace('/', '\/', $redirect['from_url']) . '/', $to_url, $url);
                    }
                    $this->response->redirect($to_url, (int)$redirect['response_code']);
                    return; // Stop processing on first match
                }
            }

            // No redirect found, log the 404
            $this->model_extension_redirect_manager_module_redirect_manager->logNotFound($url);
        }
    }

    private function matchUrl(string $url, array $redirect): bool {
        if ($redirect['is_regex']) {
            return (bool)preg_match('/' . str_replace('/', '\/', $redirect['from_url']) . '/', $url);
        } else {
            return $url === $redirect['from_url'];
        }
    }
}
