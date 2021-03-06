<?php

class Comments extends Controller {
	
	function __construct() {
		parent::Controller ();
		require_once (APPPATH . 'controllers/default_constructor.php');
		require_once (APPPATH . 'controllers/admin/default_constructor.php');
	
	}
	
	function comments_do_search() {
		if ($_POST ['search_by_keyword']) {
			$togo_tags = "/tags:{$_POST ['search_by_keyword']}";
		} else {
			$togo_tags = false;
		}
		$gogo = site_url ( 'admin/comments/index' ) . $togo_tags;
		$gogo = reduce_double_slashes ( $gogo );
		header ( "Location: $gogo " );
		exit ();
	}
	
	function index() {
		$this->template ['functionName'] = strtolower ( __FUNCTION__ );
		
		$tags = CI::model('core')->getParamFromURL ( 'tags' );
		
		
		$data = array ( );
		
		$this->template ['search_by_keyword'] = '';
		if($tags){
			$data ['search_by_keyword'] = $tags;
			$this->template ['search_by_keyword'] = $tags;
		}
		
		
		
		$results_count = CI::model('comments')->commentsGet ($data,false,true);
		
		$items_per_page = CI::model('core')->optionsGetByKey ( 'admin_default_items_per_page' );

		$content_pages_count = ceil ( $results_count / $items_per_page );
		
		$curent_page = CI::model('core')->getParamFromURL ( 'curent_page' );
		if (intval ( $curent_page ) < 1 || intval($curent_page) > $content_pages_count ) {
			$curent_page = 1;
		}
		
		$page_start = ($curent_page - 1) * $items_per_page;
		$page_end = ($page_start) + $items_per_page;
		
		//$data ['is_moderated'] = 'n';
		$form_values = CI::model('comments')->commentsGet ( $data ,array ($page_start, $page_end ),false);
		$new_comments = array();
		$old_comments = array();
		for($i=0; $i < count($form_values);$i++){
			if($form_values[$i]['is_moderated'] == 'n') $new_comments[] = $form_values[$i];
			else $old_comments[] = $form_values[$i];
		}
		
		$this->template ['content_pages_count'] = $content_pages_count;
		//var_dump($content_pages_count);
		$this->template ['content_pages_curent_page'] = $curent_page;
		
		//get paging urls
		$content_pages = CI::model('content')->pagingPrepareUrls ( false, $content_pages_count );
		$this->template ['content_pages_links'] = $content_pages;
		
		$this->template ['new_comments'] = $new_comments;
		$this->load->vars ( $this->template );
		/*
		$data = array ( );
		$data ['is_moderated'] = 'y';
		$limit [0] = 0;
		$limit [1] = 100;
		
		$form_values = CI::model('comments')->commentsGet ( $data );
		*/
		$this->template ['old_comments'] = $old_comments;
		$this->load->vars ( $this->template );
		
		$this->load->vars ( $this->template );
		
		$layout = CI::view ( 'admin/layout', true, true );
		$primarycontent = '';
		$secondarycontent = '';
		
		$primarycontent = CI::view ( 'admin/comments/index', true, true );
		//$secondarycontent = CI::view ( 'admin/content/sidebar', true, true );
		$layout = str_ireplace ( '{primarycontent}', $primarycontent, $layout );
		$layout = str_ireplace ( '{secondarycontent}', $secondarycontent, $layout );
		//CI::view('welcome_message');
		CI::library('output')->set_output ( $layout );
	}
	
	function approve() {
		$id = $_POST ['id'];
		if (intval ( $id ) == 0) {
			exit ( 'id' );
		} else {
			CI::model('comments')->commentApprove ( $id );
		}
	}
	
	function delete() {
	$id = $_POST ['id'];
		if (intval ( $id ) == 0) {
			exit ( 'id' );
		} else {
			CI::model('comments')->commentsDeleteById ( $id );
		}
	}

}

