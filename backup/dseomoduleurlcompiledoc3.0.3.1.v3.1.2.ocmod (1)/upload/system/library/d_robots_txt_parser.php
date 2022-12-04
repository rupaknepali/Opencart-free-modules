<?php
use d_robots_txt_parser\RobotsTxtParser;
use d_robots_txt_parser\RobotsTxtValidator;

class d_robots_txt_parser {
	private $parser;
	private $validator;
	
	public function __construct($content, $encoding = 'UTF-8') {
		$this->parser = new RobotsTxtParser($content, $encoding);
		$this->validator = new RobotsTxtValidator($this->parser->getRules());
	}

	public function getRules($userAgent = NULL) {
		return $this->parser->getRules($userAgent);
	}
	
	public function getSitemaps() {
		return $this->parser->getSitemaps();
	}
	
	public function isUrlAllow($url, $userAgent = '*') {
		return $this->validator->isUrlAllow($url, $userAgent);
	}
	
	public function isUrlDisallow($url, $userAgent = '*') {
		return $this->validator->isUrlDisallow($url, $userAgent);
	}
}
?>