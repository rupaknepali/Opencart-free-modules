<?php
namespace Opencart\Admin\Controller\Extension\AiDescriptionGenerator\Module;

class AiDescriptionGenerator extends \Opencart\System\Engine\Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('extension/ai_description_generator/module/ai_description_generator');

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
            'href' => $this->url->link('extension/ai_description_generator/module/ai_description_generator', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/ai_description_generator/module/ai_description_generator.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['module_ai_description_generator_api_key'] = $this->config->get('module_ai_description_generator_api_key');
        $data['module_ai_description_generator_model'] = $this->config->get('module_ai_description_generator_model');
        $data['module_ai_description_generator_status'] = $this->config->get('module_ai_description_generator_status');

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_ai_description_generator', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'));
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/ai_description_generator/module/ai_description_generator', $data));
    }

    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/ai_description_generator/module/ai_description_generator')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['module_ai_description_generator_api_key'])) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (empty($this->request->post['module_ai_description_generator_model'])) {
            $this->error['model'] = $this->language->get('error_model');
        }

        return !$this->error;
    }

    public function save(): void {
        $this->load->language('extension/ai_description_generator/module/ai_description_generator');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');
            
            $this->model_setting_setting->editSetting('module_ai_description_generator', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        } else {
            $json['error'] = $this->language->get('error_permission');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function generate(): void {
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/ai_description_generator/module/ai_description_generator')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $apiKey = $this->config->get('module_ai_description_generator_api_key');
            $model = $this->config->get('module_ai_description_generator_model') ?? 'llama-3.3-70b-versatile';

            if (!$apiKey) {
                $json['error'] = $this->language->get('error_api_key');
            } elseif (empty($this->request->post['product_name'])) {
                $json['error'] = $this->language->get('error_product_name');
            } else {
                $productName = $this->request->post['product_name'];
                $selectedModel = $this->request->post['model'] ?? $model;
                $prompt = $this->config->get('module_ai_description_generator_prompt');
                $prompt = preg_replace('{({productname})}', $productName, $prompt);

                $data = [
                    'model' => $selectedModel,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant that writes well-formatted product descriptions using simple markdown'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'text']
                ];

                // Handle different API endpoints based on model
                $apiUrl = 'https://api.openai.com/v1/chat/completions';
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ];
                
                if (strpos($selectedModel, 'claude') !== false) {
                    $apiUrl = 'https://api.anthropic.com/v1/messages';
                } elseif (strpos($selectedModel, 'llama-3.3-70b-versatile') !== false) {
                    $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
                }

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);

                if (curl_errno($ch)) {
                    $json['error'] = 'Curl error: ' . curl_error($ch);
                } else {
                    $response = json_decode($result, true);
                    if (isset($response['choices'][0]['message']['content'])) {
                        $description = $response['choices'][0]['message']['content'];
                        
                        // Convert markdown to HTML in controller
                        $description = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $description);
                        $description = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $description);
                        $description = preg_replace('/^\s*- (.*)$/m', '<li>$1</li>', $description);
                        // Headings
                        $description = preg_replace('/^\s*####\s+(.*)$/m', '<h4>$1</h4>', $description);
                        $description = preg_replace('/^\s*###\s+(.*)$/m', '<h3>$1</h3>', $description);
                        $description = preg_replace('/^\s*##\s+(.*)$/m', '<h2>$1</h2>', $description);
                        $description = preg_replace('/^\s*#\s+(.*)$/m', '<h1>$1</h1>', $description); 
                        $json['description'] = $description;
                    } else {
                        $json['error'] = 'Could not get a description from the AI. Response: ' . $result;
                    }
                }

                curl_close($ch);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
