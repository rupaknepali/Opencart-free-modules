<?php
namespace Opencart\Catalog\Controller\Extension\Thirdpartyjs\analytics;

class Thirdpartyjs extends \Opencart\System\Engine\Controller
{
    public function index(): string
    {
        return html_entity_decode($this->config->get('analytics_thirdpartyjs_code'), ENT_QUOTES, 'UTF-8');
    }
}
