<?php
namespace Opencart\Catalog\Controller\Extension\OcModuleWordpressPosts\Module;

class Opencartwpblog extends \Opencart\System\Engine\Controller {
	public function index(): string {

		$this->load->language('extension/oc_module_wordpress_posts/module/opencartwpblog');

		$data['module_opencartwpblog_url'] = $this->config->get('module_opencartwpblog_url');
		
		$data['module_opencartwpblog_title'] = $this->config->get('module_opencartwpblog_title');
		if(empty($data['module_opencartwpblog_title'])){
			$data['module_opencartwpblog_title'] = $this->language->get('heading_title');
		}
		$data['module_opencartwpblog_status'] = $this->config->get('module_opencartwpblog_status');
		$postslimit = $this->config->get('module_opencartwpblog_limit');
		if(empty($postslimit)){
			$postslimit=3;
		}
		$posts ="";
		if($data['module_opencartwpblog_status']  && $data['module_opencartwpblog_url']){
			$json = file_get_contents($data['module_opencartwpblog_url']."/wp-json/wp/v2/posts?per_page=".$postslimit);
			if($json)$posts = json_decode($json);	
		}

		if(!empty((array)$posts)){
			foreach ($posts as $key=>$post) {
				$data['posts'][$key]['title'] = $post->title->rendered;
				$data['posts'][$key]['excerpt'] = $post->excerpt->rendered;
				$featuredimgjson = file_get_contents($data['module_opencartwpblog_url']."/wp-json/wp/v2/media/".$post->featured_media);
				if(empty(json_decode($featuredimgjson)->source_url)){
					$data['posts'][$key]['featured_media']  = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo');
				}else {
					$data['posts'][$key]['featured_media'] = json_decode($featuredimgjson)->source_url;
				}
				
				$data['posts'][$key]['link'] = $post->link;
				
			}
		}else {
			$data['posts'] = array();
		}

		return $this->load->view('extension/oc_module_wordpress_posts/module/opencartwpblog', $data);
		
		
	}
}
