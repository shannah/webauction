<?php
class tables_products {

	function afterDelete(&$record){
		// We need to delete the image when the product is deleted.
	
		$imgfile = basename($record->val('product_image'));
		$imgField =& $record->_table->getField('product_image');
		@unlink($imgField['savepath'].'/'.$imgfile);
	}


	function field__high_bid(&$record){
		$reverseAuction = getConf('reverse_auction');
		if ( $reverseAuction ) $sort = 'bid_amount asc';
		else $sort = 'bid_amount desc';
		
		$bids = df_get_records_array('bids', 
					array(
						'product_id'=>$record->val('product_id'),
						'bid_status'=>'APPROVED',
						'-sort'=>$sort,
						'-limit'=>1
						)
					);
		if ( count($bids) > 0 ) return $bids[0];

		return null;
		
	}
	
	
	function field__high_bidder(&$record){
		$bid = $record->val('high_bid');
		if ( $bid ){
			return $bid->val('username');
		}
		return null;
	}
	
	
	function field__prev_high_bid(&$record){
		$reverseAuction = getConf('reverse_auction');
		if ( $reverseAuction ) $sort = 'bid_amount asc';
		else $sort = 'bid_amount desc';
		
	
		$bids = df_get_records_array('bids', 
					array(
						'product_id'=>$record->val('product_id'),
						'bid_status'=>'APPROVED',
						'-sort'=>$sort,
						'-skip'=>1,
						'-limit'=>1
						)
					);
		if ( count($bids) > 0 ) return $bids[0];

		return null;
	}
	
	function field__prev_high_bid_amount(&$record){
		$bid = $record->val('prev_high_bid');
		if ( !isset($bid) ) return 0;
		return $bid->val('bid_amount');
	}
	
		function field__prev_high_bidder(&$record){
		$bid = $record->val('prev_high_bid');
		if ( $bid ){
			return $bid->val('username');
		}
		return null;
	}
	
	function field__high_bid_amount(&$record){
		$bid = $record->val('high_bid');
		
		if ( !isset($bid) ) return 0;
		return $bid->val('bid_amount');
	}
	
	function high_bid_amount__display(&$record){
		$amt = $record->val('high_bid_amount');
		$out = '$'.number_format(unserialize(serialize($amt)), 2);
		return $out;
	}
	
		
	function field__isOpen(&$record){
		return ($record->val('opening_time_seconds') < time() and time() < $record->val('cooked_closing_time_seconds'));
	}
	
	
	function field__cooked_closing_time_seconds(&$record){
		$closing_time = $record->strval('closing_time');
		if ( !$closing_time ){
			$app =& Dataface_Application::getInstance();
			$closing_time = $app->_conf['df_auction']['closing_time'];
		}
		
		$closing_time_seconds = strtotime($closing_time);
		$high_bid = $record->val('high_bid');
		if ( $high_bid ){
			$bid_time = $high_bid->strval('time_of_bid');
			$closing_time_seconds = max($closing_time_seconds, strtotime($bid_time));
		}
		return $closing_time_seconds;
	}
	
	function field__opening_time_seconds(&$record){

		return strtotime($record->strval('opening_time'));
	}
	
	function field__cooked_minimum_bid(&$record){
		if ( $record->val('bid_increment') ){
			$increment = floatval($record->val('bid_increment'));
		} else {
			$increment = floatval(getConf('bid_increment'));
		}
		if ( getConf('reverse_auction') ){
			$increment = $increment * (-1);
		}
		return max($record->val('minimum_bid'), $record->val('high_bid_amount')+$increment);
		
	}
	
	
	
	function seller_username__default(){
		return getUsername();
	}
	
	function minimum_bid__default(){
		return getConf('default_minimum_bid');
	}
	
	function opening_time__default(){
		$time = getConf('default_opening_time');
		if ( !$time ) $time = date('Y-m-d H:i:s');
		return $time;
		
	}

	function closing_time__default(){
		$time = getConf('default_closing_time');
		if ( !$time ) $time = date('Y-m-d H:i:s', time()+(60*60*24*7*2)); // 2 weeks from now
		return $time;
	}
	
	
	function block__view_tab_content(){
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		df_display(array('product'=>&$record), 'view_product.html'); 
	}
	
	function block__result_list(){
		if ( isAdmin() ) return PEAR::raiseError("Just show the default list");
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$products = df_get_records_array('products', $query);
		df_display(array('products'=>&$products), 'public_product_list.html');
	}
	

	function current_high_bid__display(&$record){
		return '$'.number_format($record->val('current_high_bid'),2);
		
	}
	
	function minimum_bid__display(&$record){
		return '$'.number_format($record->val('minimum_bid'),2);
	}
	
}
?>