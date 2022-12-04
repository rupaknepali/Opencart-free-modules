<?php

class ModelExtensionDAdminStyleStyle extends Model
{

    public function getStyles($theme_name)
    {
        $this->getAdminStyle($theme_name);
    }

    public function getAdminStyle($theme_name)
    {
        $this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
        $this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
        //todo add only on ie asdasdssd
        $this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
        $this->document->addStyle('view/stylesheet/d_admin_style/themes/' . $theme_name . '/' . $theme_name . '.css');

    }

    public function getAvailableThemes()
    {
        $dir = DIR_APPLICATION . 'view/stylesheet/d_admin_style/themes';
        $name_dirs = scandir($dir);
        return array_diff($name_dirs, array('.', '..'));
    }

    public function getLanguageText($data)
    {
        $this->language->load('extension/d_admin_style/style');
        $data['entry_admin_style'] = $this->language->get('entry_admin_style');
        return $data;
    }
}
