<?php

class Menus extends Controller {

	function __construct() {
		parent::Controller ();
		require_once (APPPATH . 'controllers/default_constructor.php');
		require_once (APPPATH . 'controllers/admin/default_constructor.php');

	}

	function index() {
		$this->template ['functionName'] = strtolower ( __FUNCTION__ );
		CI::helper(array('form', 'url'));
		
		$this->load->library('form_validation');
		$this->form_validation->set_rules ( 'menu_unique_id', 'Menu unique ID', 'trim|required' );
		$this->form_validation->set_error_delimiters ( '<div class="error">', '</div>' );

		if ($this->form_validation->run () == FALSE) {
			if ($_POST) {
				$this->template ['form_values'] = $_POST;
			}
			$this->template ['form_validation_errors'] = $this->form_validation->_error_array;
			$this->load->vars ( $this->template );

		} else {
			$to_save = $_POST;
			$to_save ['item_type'] = 'menu';
			CI::model('content')->saveMenu ( $to_save );

			redirect ( 'admin/menus' );
		}

		//reorder menu item
		$move_up = CI::model('core')->getParamFromURL ( 'move_up' );
		$move_down = CI::model('core')->getParamFromURL ( 'move_down' );
		if (intval ( $move_down ) != 0) {
			CI::model('content')->reorderMenuItem ( 'down', $move_down );
			redirect ( 'admin/menus' );
		}

		if (intval ( $move_up ) != 0) {
			CI::model('content')->reorderMenuItem ( 'up', $move_up );
			redirect ( 'admin/menus' );
		}

		$data = array ();
		$data ['item_type'] = 'menu';
		$menus = CI::model('content')->getMenus ( $data );
		$this->template ['menus'] = $menus;

		$this->load->vars ( $this->template );
		$layout = CI::view ( 'admin/layout', true, true );
		$primarycontent = CI::view ( 'admin/content/menus/menus_list', true, true );
		$nav = CI::view ( 'admin/content/menus/menus_nav', true, true );
		$primarycontent = $nav . $primarycontent;

		$secondarycontent = false;
		$layout = str_ireplace ( '{primarycontent}', $primarycontent, $layout );
		$layout = str_ireplace ( '{secondarycontent}', $secondarycontent, $layout );
		CI::library('output')->set_output ( $layout );
	}

	function menus_delete() {
		//delete menu
		$delete = CI::model('core')->getParamFromURL ( 'delete' );
		if (intval ( $delete ) != 0) {
			$table = TABLE_PREFIX . 'menus';
			$data = array ();
			$data ['id'] = $delete;
			$del = CI::model('core')->deleteData ( $table, $data, 'menus' );
			CI::model('content')->fixMenusPositions ();
			CI::model('core')->cacheDeleteAll ();
		//	redirect ( 'admin/menus' );
		}

	}

	function menus_show_menu_ajax() {
		$edit = CI::model('core')->getParamFromURL ( 'id' );
		//var_dump ( $edit );
		if (intval ( $edit ) != 0) {
			$data = array ();
			$data ['item_type'] = 'menu';
			$data ['id'] = $edit;
			$menu = CI::model('content')->getMenus ( $data );
			$this->template ['item'] = $menu [0];
		}

		$this->load->vars ( $this->template );
		$layout = CI::view ( 'admin/content/menus_show_menu_ajax.php', true, true );
		exit ( $layout );
	}

	function menus_edit_small_menu_item() {
		$edit = CI::model('core')->getParamFromURL ( 'id' );
		$form = CI::model('core')->getParamFromURL ( 'form' );
		$this->template ['form_id'] = $form;
		//var_dump ( $edit );
		if (intval ( $edit ) != 0) {
			$data = array ();
			$data ['item_type'] = 'menu_item';
			$data ['id'] = $edit;
			$menu = CI::model('content')->getMenus ( $data );
			$this->template ['form_values'] = $menu [0];
		}
		//	var_dump ( $menu );
		$this->load->vars ( $this->template );
		$layout = CI::view ( 'admin/content/menus_edit_small_menu_item.php', true, true );
		exit ( $layout );
	}

	function menus_edit_small() {
		if ($_POST) {
			$to_save = $_POST;
			$to_save ['item_type'] = 'menu';
			CI::model('content')->saveMenu ( $to_save );
		}

		$edit = CI::model('core')->getParamFromURL ( 'id' );
		//var_dump ( $edit );
		if (intval ( $edit ) != 0) {
			$data = array ();
			$data ['item_type'] = 'menu';
			$data ['id'] = $edit;
			$menu = CI::model('content')->getMenus ( $data );
			$this->template ['form_values'] = $menu [0];
		}

		$this->load->vars ( $this->template );
		$layout = CI::view ( 'admin/content/menus_edit_small.php', true, true );
		exit ( $layout );
	}

	function menus_delete_menu_item() {
		$delete_menu_item = CI::model('core')->getParamFromURL ( 'delete_menu_item' );
		$delete_menu_item = $_POST ['delete_menu_item'];

		if (intval ( $delete_menu_item ) != 0) {
			$table = TABLE_PREFIX . 'menus';
			$data = array ();
			$data ['id'] = $delete_menu_item;
			$del = CI::model('core')->deleteData ( $table, $data, 'menus' );
			//redirect ( 'admin/content/menus' );
			exit ( 'ok' );
		}
	}

	function menus_save_menu_item() {
		$this->template ['functionName'] = strtolower ( __FUNCTION__ );

		$data = $_POST;
		$data ['item_type'] = 'menu_item';

		if ($data ['content_id'] != 0) {
			$data ['taxonomy_id'] = '0';
			$data ['menu_url'] = '0';
		}

		if ($data ['taxonomy_id'] != 0) {
			$data ['content_id'] = '0';
			$data ['menu_url'] = '0';
		}

		if ($data ['menu_url'] != 0) {
			$data ['taxonomy_id'] = '0';
			$data ['content_id'] = '0';
		}

		CI::model('content')->saveMenu ( $data );
		redirect ( 'admin/content/menus' );
		exit ();

	}

	function menus_add() {
		$this->template ['functionName'] = strtolower ( __FUNCTION__ );

		//edit menu
		$edit = CI::model('core')->getParamFromURL ( 'edit' );
		//var_dump ( $edit );
		if (intval ( $edit ) != 0) {
			$data = array ();
			$data ['item_type'] = 'menu';
			$data ['id'] = $edit;
			$menu = CI::model('content')->getMenus ( $data );
			$this->template ['form_values'] = $menu [0];
		}

		$this->load->vars ( $this->template );
		$layout = CI::view ( 'admin/layout', true, true );
		$primarycontent = CI::view ( 'admin/content/menus/menus_add', true, true );
		$nav = CI::view ( 'admin/content/menus/menus_nav', true, true );
		$primarycontent = $nav . $primarycontent;

		$secondarycontent = false;
		$layout = str_ireplace ( '{primarycontent}', $primarycontent, $layout );
		$layout = str_ireplace ( '{secondarycontent}', $secondarycontent, $layout );
		CI::library('output')->set_output ( $layout );
	}

}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */