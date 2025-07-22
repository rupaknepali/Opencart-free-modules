<?php
namespace Opencart\Catalog\Controller\Extension\RedirectManager\Startup;
class RedirectManager extends \Opencart\System\Engine\Controller {
    public function index(): void {
        if (!$this->config->get('module_redirect_manager_status')) {
            return;
        }

        $this->load->model('extension/redirect_manager/module/redirect_manager');

        // Get the current request path, e.g., 'path/to/page'
        // We use _route_ so it works with SEO URLs
        $current_path = trim($this->request->get['_route_'] ?? '', '/');

        $redirects = $this->model_extension_redirect_manager_module_redirect_manager->getActiveRedirects();

        foreach ($redirects as $redirect) {
            $from_url = trim($redirect['from_url'], '/');
            $to_url = $redirect['to_url'];

            // Check for a direct match or a regex match
            $match = false;
            if ($redirect['is_regex']) {
                if (preg_match('/^' . str_replace('/', '\/', $from_url) . '$/i', $current_path)) {
                    // For regex, we can use back-references in the to_url
                    $to_url = preg_replace('/^' . str_replace('/', '\/', $from_url) . '$/i', $to_url, $current_path);
                    $match = true;
                }
            } else {
                if (strtolower($from_url) == strtolower($current_path)) {
                    $match = true;
                }
            }

            if ($match) {
                // If the to_url is not an absolute URL, build it
                if (!preg_match('/^(f|ht)tps?:\/\//i', $to_url)) {
                    $to_url = $this->config->get('config_url') . ltrim($to_url, '/');
                }
                
                $this->response->redirect($to_url, $redirect['response_code']);
                exit;
            }
        }

        // Log 404 errors if enabled, no redirect was found, and the page is a 404
        if ($this->config->get('module_redirect_manager_log_status') && isset($this->request->get['route']) && $this->request->get['route'] == 'error/not_found') {
            $this->model_extension_redirect_manager_module_redirect_manager->logNotFound($current_path);
        }
    }
}
