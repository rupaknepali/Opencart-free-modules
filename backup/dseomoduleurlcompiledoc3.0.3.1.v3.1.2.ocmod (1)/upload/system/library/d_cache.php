<?php
class d_cache {
	private $adaptor;
	private $config = array('adaptor' => 'File');

	public function __construct($config = array()) {
		if (!empty($config)) {
			$this->config = array_replace_recursive($this->config, $config);
		}
		
		$class = 'd_cache\\' . $this->config['adaptor'];

		if (class_exists($class)) {
			$this->adaptor = new $class($this->config);
		} else {
			throw new \Exception('Error: Could not load cache adaptor ' . $this->config['adaptor'] . ' cache!');
		}
	}
	
	/**
	 * Register a binding with the container.
	 *
	 * @param  string               $abstract
	 * @param  Closure|string|null  $concrete
	 * @param  bool                 $shared
	 * @return mixed
	*/
	public function get($group, $key) {
		return $this->adaptor->get($group, $key);
	}

	public function set($group, $key, $value, $expire = 0) {
		return $this->adaptor->set($group, $key, $value, $expire);
	}

	public function delete($group, $key) {
		return $this->adaptor->delete($group, $key);
	}
	
	public function deleteAll($group) {
		return $this->adaptor->deleteAll($group);
	}
	
	public function deleteOld($group) {
		return $this->adaptor->deleteOld($group);
	}
}
