<?php
/*
While creating the Class name follow the folder strucutre, as our folder structure is catalog/controller/product/catgorieslist.php so the name of class is ControllerProductCategorieslist. Do not use - and _. They give Fatal error: Uncaught Error: Class 'Controllerproductcategorieslist' not found
*/
class ControllerProductCategorieslist extends Controller
{
    public function index()
    {
        $this->load->language('product/categorieslist');
        
        $this->document->setTitle($this->language->get('meta_title'));
        $this->document->setDescription($this->language->get('meta_description'));
        $this->document->setKeywords($this->language->get('meta_keyword'));

        $data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
        );
        $data['breadcrumbs'][] = array(
			'text' => $this->language->get('title'),
			'href' => $this->url->link('product/categorieslist')
        );
        
        $category_id = 0;
        $data['categories'] = array();
        
        $this->load->model('catalog/category');
        $results = $this->model_catalog_category->getCategories($category_id);

        $this->load->model('tool/image');

        foreach ($results as $result) {
            $categories = $this->imageCategory($result['name'], $result['image'], $result['category_id']);
            $data['categories'][]= $categories;

            $data['subcategories']=$this->model_catalog_category->getCategories($result['category_id']);
            if(!empty($data['subcategories'])){
                foreach($data['subcategories'] as $category){
                    $subcategories = $this->imageCategory($category['name'], $category['image'], $category['category_id']);
                    $data['categories'][]= $subcategories;
                }
            }
        }

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('product/categorieslist', $data));
    }

    public function imageCategory($name, $category_image, $category_id){
        if ($category_image) {
            $image = $this->model_tool_image->resize($category_image, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
        } else {
            $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
        }
        $categories = array(
            'name' => $name ,
            'href' => $this->url->link('product/category', 'path=' . $category_id),
            'image' => $image,
        );
        return $categories;
    }
}

