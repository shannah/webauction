<?php
class conf_ApplicationDelegate {

	function getPermissions(&$record){
		if ( isAdmin() ) return Dataface_PermissionsTool::ALL();
		return Dataface_PermissionsTool::READ_ONLY();
	
	}
	
	function block__custom_stylesheets(){
		echo '<link rel="stylesheet" href="style.css" type="text/css">';
	}
	
	function block__after_application_menu(){
		
		$categories = $this->getCategoriesMenuOptions();
		df_display(array('categories'=>&$categories), 'categories_menu.html');
	
	}
	
	function block__before_main_column(){
		if ( isAdmin() ) {
			$sql = "select sum(bid_amount) from bids b where not exists ( select bid_id from bids b2 where b2.product_id=b.product_id and b2.bid_amount > b.bid_amount) and exists ( select product_id from products p where p.product_id=b.product_id)";
			$res = xf_db_query($sql, df_db());
			list($amt) = xf_db_fetch_row($res);
			echo "<div style=\"float: right;\">Total Bids Currently: \$".number_format($amt,2).'</div>';
		}
		
		$res = xf_db_query("select convert_tz(NOW(), 'SYSTEM','".addslashes(df_utc_offset())."')", df_db());
		if ( !$res ){
			trigger_error(xf_db_error(df_db()), E_USER_ERROR);
		}
		list($now) = xf_db_fetch_row($res);
		@xf_db_free_result($res);
		
		echo '<div style="float: right; padding-right: 5px;">The Current Time is '.htmlspecialchars(date('h:i a T',strtotime($now))).'</div>';
	}
	
	function getCategoriesMenuOptions(){
		$sql = "select p.product_id, pc.category_id, pc.category_name, count(*) as num from products p inner join product_categories pc on p.product_categories rlike concat('[[:<:]]',pc.category_id,'[[:>:]]') group by pc.category_id";
		$res = xf_db_query($sql, df_db());
		$out = array();
		while ( $row = xf_db_fetch_assoc($res) ) $out[] = $row;
		return $out;
	
	}
	
	function getPreferences(){
		
		$user =& getUser();
		if ( function_exists('date_default_timezone_set') ){
			getConf('timezone'); // set the default timezone first in case this hasn't been called yet
			if ( $user and $user->val('timezone') ){
				date_default_timezone_set($user->val('timezone'));
			}
			
			
		}
	
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( $query['-table'] == 'products' and !isset($query['-sort']) ){
			$query['-sort'] = 'product_categories asc';
		}
		if ( (!getConf('disable_auto_close') or @$_GET['--close-auctions']) and $this->mutex('closingAuctions') ){
			closeAuctions();
		}
		if ( isAdmin() ){
			return array('show_record_tree'=>0);
		} else {
			return array(
				'show_tables_menu'=>0,
				'show_table_tabs'=>0,
				'show_record_tree'=>0,
				'show_record_tabs'=>0,
				'show_result_controller'=>0);
		}
	}
	
	/**
	 * Obtain a mutex (to make sure we aren't running multiple instances
	 * of this script concurrently.
	 *
	 * This function will return true if it succeeded in obtaining the mutex
	 *	(i.e.  no other instance of this script is running.  And false otherwise.
	 * @param string $name The name of the mutex to acquire.
	 */
	function mutex($name){
		global $mutex;
		$path = dirname(__FILE__).'/../templates_c/'.$name.'.mutex';
		$mutex = fopen($path, 'w');
		if ( flock($mutex, LOCK_EX | LOCK_NB) ){
			register_shutdown_function(array($this,'clear_mutex'));
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
	 * Clears the most recently acquired mutex.
	 */
	function clear_mutex(){
		global $mutex;
		if ( $mutex ){
			fclose($mutex);
		}
	}

	
	function block__global_header(){
		if ( $header = trim(getConf('custom_header')) ){
			echo $header;
			return true;
		}
		return PEAR::raiseError('Use the default header', DATAFACE_E_REQUEST_NOT_HANDLED);
	}
	
	function block__global_footer(){
		if ( $footer = trim(getConf('custom_footer')) ){
			echo $footer;
			return true;
		}
		return PEAR::raiseError('Use the default footer', DATAFACE_E_REQUEST_NOT_HANDLED);
	}
	
	function block__custom_stylesheets2(){
		if ( $css = trim(getConf('custom_css')) ){
			echo '<style type="text/css"><!--
			'.$css.';
			//--></style>';
		}
		return PEAR::raiseError('No stylesheets specified', DATAFACE_E_REQUEST_NOT_HANDLED);
		
	}
	
	function valuelist__timezones(){
		$data = getTimezones();
		$timezones = array();
		foreach ( $data as $tz=>$offset ){
			$timezones[$tz] = $offset;
		}
		return $timezones;
	}
	
	function beforeHandleRequest(){
		$user =& Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
		if ( $user and !$user->val('role') ) {
			echo 'Your user account has been suspended';exit;
		}
		$app =& Dataface_Application::getInstance();
		$title = getConf('title');
		if ( $title )
			$app->_conf['title'] = $title;
	}
	
	
	function getRegistrationActivationEmailInfo(&$record, $activationURL){ 
    	return array( 
      		'headers' => 'From: '.getConf('notification_from_address') . "\r\n" . 
            'Reply-To: '.getConf('notification_from_address') . "\r\n" 
    	); 
      
    
    } 
}
