<?php
class tables_bids {

	var $cache = array();
	
	
	function getPermissions(&$record){
	/*
		$app =& Dataface_Application::getInstance();
		$del =& $app->getDelegate();
		$perms =& $del->getPermissions($record);
	*/
		//if ( $record ) echo "Yes"; else echo "No";
		//if ( $record and $record->val('username') ) echo "We have a username";
		if ( isAdmin() ) return null;
		if (  $record and ($record->strval('username') == getUsername())) {
			$perms = Dataface_PermissionsTool::getRolePermissions('EDIT');
		} else {
			$perms = Dataface_PermissionsTool::NO_ACCESS();
		}
		$perms['new'] = 1;
		return $perms;
	}
	
	function beforeSave(&$record){
		$reverseAuction = getConf('reverse_auction');
		
		if ( !isAdmin() || @$_REQUEST['--force-validate'] ){
			$product =& $record->val('product_object');
			
			$closing_time = $product->val('cooked_closing_time_seconds');
			if ( $closing_time < time() ){
				// The bidding is closed
				$msg = "The bidding on this product is already closed.  It closed at ".date('Y-m-d H:i:s', $closing_time).".";
				$msg = df_translate('MESSAGE_BIDDING_CLOSED', $msg, array('closing_time'=>date('Y-m-d H:i:s', $closing_time))); 
				return PEAR::raiseError($msg, DATAFACE_E_NOTICE);
			}
			
			$opening_time = $product->val('opening_time_seconds');
			if ( $opening_time > time() ){
				// The bidding isn't opened yet
				$msg = "The bidding on this product has not yet begun.  It opens at ".date('Y-m-d H:i:s', $opening_time).".";
				$msg = df_translate('MESSAGE_BIDDING_NOT_OPEN_YET', $msg, array('opening_time'=>date('Y-m-d H:i:s', $opening_time)));
				return PEAR::raiseError($msg, DATAFACE_E_NOTICE);
			}
			
			$min_bid = $product->val('cooked_minimum_bid');
			

			if ( (!$reverseAuction and ($min_bid > $record->val('bid_amount')))
					or
				 ( $reverseAuction and ($min_bid < $record->val('bid_amount'))) ){
				// The bid isn't high enough.
				$msg = "The minimum bid on '".$product->getTitle()."' is ".$product->display('cooked_minimum_bid').".  Your bid must be at least this amount.";
				$msg = df_translate('MESSAGE_BID_TOO_LOW', $msg, array('product_title'=>$product->getTitle(), 'minimum_bid'=>$product->display('cooked_minimum_bid')));
				
				return PEAR::raiseError($msg, DATAFACE_E_NOTICE);
			}
		
		}
	
	}
	
	//function bid_amount__parse($value){
	//	//echo "Val: $value <br>";
	//	echo preg_replace('/[^\.0-9]/','', $value); exit;
	//}
	

	
	function username__default(){
		$user =& getUser();
		if ( $user ) return $user->val('username');
		return null;
	}
	
	
	function bid_status__default(){
		$app =& Dataface_Application::getInstance();
		return $app->_conf['df_auction']['default_bid_status'];
	}
	
	function field__product_object(&$record){
		return df_get_record('products', array('product_id'=>$record->val('product_id')));
	}
	
	
	
	
	
	
}
?>