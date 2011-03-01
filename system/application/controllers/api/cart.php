<?php

class Cart extends Controller {
	
	function __construct() {
		
		parent::Controller ();
		
		require_once (APPPATH . 'controllers/default_constructor.php');
		//	require_once (APPPATH . 'controllers/api/default_constructor.php');
	

	}
	
	function add() {
		
		if ($_POST) {
			
			$cart = CI::model ( 'cart' )->itemAdd ( $_POST );
			$cart = CI::model ( 'cart' )->itemsGetQty ();
			print $cart;
		
		}
	
	}
	
	function remove_item() {
		$id = $_POST ['id'];
		print CI::model ( 'cart' )->itemDeleteById ( $id );
	}
	
	function checkout_return() {
		//var_dump($_SERVER);
		$get = $_REQUEST ['eBorica'];
		$param = CI::model ( 'core' )->getParamFromURL ( 'eBorica' );
		
		//	var_dump($param);
		

		$msg = $this->cart_model->billing_borica_getBOResp ( $param );
		$msg_resp = $msg ["RESP_CODE"];
		//var_dump($msg_resp);
		//$msg_resp = 00;
		switch ($msg_resp) {
			case '00' :
				
				$msg_to_show = "You have completed the payment. We will contact you shortly for your order.";
				break;
			
			case '94' :
				$msg_to_show = "You have canceled your payment.";
				break;
			
			default :
				$msg_to_show = "There was error with your payment. Yor payment has not been processed.";
				break;
		
		}
		$site = site_url ();
		print "<h1>$msg_to_show</h1><br>";
		print "<b><a href='$site' >Click here to continue</a></b>";
		exit ();
		//billing_borica_getBOResp
	//header("Location: ".site_url('shop'));
	//exit;
	}

}


