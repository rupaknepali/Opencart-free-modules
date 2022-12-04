<?php
class ModelExtensionModuleDTranslit extends Model {
	private $codename = 'd_translit';
	private $config_file = 'd_translit';
		
	/*
	*	Translit
	*/
	public function translit($text, $data = array()) {
		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename)) ? $_config->get($this->codename) : array();
		
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
				
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}	
		
		if (!empty($data)) {
			$config_setting = array_replace_recursive($config_setting, $data);
		}
		
		$setting = $config_setting;
				
		// Translit
		if ($setting['translit_symbol_status']) {
			$text = html_entity_decode($text);
			
			if ($setting['translit_symbol']) {
				$text = strtr($text, $this->array_entity_decode($setting['translit_symbol']));
			} else {
				$text = strtr($text, $this->array_entity_decode($setting['translit_symbol_default']));
			}
			
			$text = htmlentities($text);
		}
		
		if ($setting['translit_language_symbol_status']) {
			$text = html_entity_decode($text);
			
			if ($setting['translit_language_symbol']) {
				$text = strtr($text, $this->array_entity_decode($setting['translit_language_symbol']));
			} else {
				$text = strtr($text, $this->array_entity_decode($setting['translit_language_symbol_default']));
			}
			
			$text = htmlentities($text);
		}
		
		switch ($setting['transform_language_symbol_id']) {
			case 0:
				break;
			case 1:
				$text = mb_strtolower($text, 'UTF-8');
				break;
			case 2:
				$text = mb_strtoupper($text, 'UTF-8');
				break;
		}
		
		if ($setting['trim_symbol_status']) {
			foreach ($setting['trim_symbol'] as $symbol) {
				$text = trim($text, $symbol);
				$text = preg_replace('/(\\' . $symbol . '){2,}/', '$1', $text);
			}
		}
		
		return $text;
	}
	
	private function array_entity_decode($arr) {
		foreach ($arr as $key => $value) {
			unset($arr[$key]);
			$key = strtr($key, array('\n' => "\n", '\r' => "\r", '\t' => "\t"));
			$value = strtr($value, array('\n' => "\n", '\r' => "\r", '\t' => "\t"));
			$arr[html_entity_decode($key)] = html_entity_decode($value);
		}
		
		return $arr;
	}
}
?>