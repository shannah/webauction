<?php
function sendAdminEmail($subject, $msg){

			
	if ( !getConf('admin_email') ){
		error_log("Failed to send email to admin because no email address was specified in the conf.ini file.");
		return false;
	}
	$mail_headers = 'From: '.getConf('notification_from_address') . "\r\n" .
					'Reply-To: '.getConf('notification_from_address') . "\r\n" ;	
	return mail(getConf('admin_email'), $app->_conf['title'].' - '.$subject, $msg, $mail_headers);
		
}

function sendEmail($to,$subject,$msg){
	$mail_headers = 'From: '.getConf('notification_from_address') . "\r\n" .
					'Reply-To: '.getConf('notification_from_address') . "\r\n" ;	
	return mail($to, $app->_conf['title'].' - '.$subject, $msg, $mail_headers);
}

function getAuctionWinnerMessage($closeRec){
	$product = df_get_record('products', array('product_id'=>$closeRec->val('product_id')));
	$product_url = $product->getURL('-action=view');
	$product_title = $product->getTitle();
	$bid_amount = '$'.number_format($closeRec->val('bid_amount'),2);
	$winner_instructions = getConf('winner_instructions');
	
	return <<<END
You are the winner of the product '$product_title'.  Your bid was $bid_amount .
For more information about this product visit $product_url .

Instructions to collect your product:

$winner_instructions
END;
	


}


function &getUser(){
	static $user = 0;
	if ( $user === 0 ){
		$auth = Dataface_AuthenticationTool::getInstance();
		$user = $auth->getLoggedInUser();
	}
	return $user;
}

function getUsername(){
	static $username = 0;
	if ( $username === 0 ){
		$auth = Dataface_AuthenticationTool::getInstance();
		$username = $auth->getLoggedInUsername();
	}
	return $username;

}

function isAdmin(){
	$user = getUser();
	return ($user and $user->val('role') == 'ADMIN');
}

function isLoggedIn(){
	if ( getUser() ) return true;
	return false;
}

function isRegistered(){
	$user = getUser();
	if ( $user->val('role') ) return true;
	return false;
}

function registerUser(){
	if ( isRegistered() ) return;
	$user = getUser();
	$user->setValue('role','USER');
	$user->save();
}

// Returns extra information about a user
// Returned array is in the following format
// array("uid"=>"shannah", "givenName"=>"Steve", "sn"=>"Hannah", "cn"=>"Steve Hannah", "mail"=>"steve_hannah@sfu.ca")
// uid : Unix id
// givenName : First Name (eg Jane)
// sn : Surname (eg: Doe)
// cn : Full name (eg: Steve Hannah)
// mail : Email address (eg: shannah@sfu.ca)
// telephoneNumber : Phone number
// title : Title (Eg: Manager, Financial Services)
// ou : Organizational unit (eg: Computing Science)
function getLDAPUserInfo($userid){
	
	static $cache = 1;
	if ( !is_array($cache) ){
		$cache = array();
	}
	
	if ( isset($cache[$userid]) ){
		return $cache[$userid];
	}
	
	$app = Dataface_Application::getInstance();
	$vars =& $app->_conf['_auth'];
	if ( !@$vars['ldap_host']  || !@$vars['ldap_base'] || !function_exists('ldap_connect')) return null;
	if ( !$vars['ldap_port'] ) $vars['ldap_port'] = 1389;
	list($ldap_host, $ldap_base, $ldap_port) = array($vars['ldap_host'], $vars['ldap_base'], $vars['ldap_port']);
	
	//global $ldap_host, $ldap_base, $ldap_port;
	$query = "uid=$userid, $ldap_base";
	$ldap = ldap_connect($ldap_host, $ldap_port);
	if ( $ldap ){
		$r = ldap_bind($ldap);
		$sr = ldap_search($ldap, $query, "objectclass=*");
		if ( ldap_count_entries($ldap, $sr) > 0 ){
			$val = ldap_first_entry($ldap, $sr);
			$atts = ldap_get_attributes($ldap, $val);
			foreach ($atts as $key=>$value){
				if ( is_array($value) ){
					$atts[$key] = $value[0];
				}
			}
			$cache[$userid] = $atts;
			return $atts;
		}
	} else {
		return null;
	}
}

/**
 * Notifies the high bidder via email that he has been outbid.
 */
function notifyHighBidder($product){
	$app = Dataface_Application::getInstance();
	
	$username = $product->val('prev_high_bidder');
	$product_name = $product->val('product_name');
	$high_bid = $product->display('high_bid_amount');
	$url = $product->getURL('-action=view');
	
	$mail_headers = 'From: '.getConf('notification_from_address') . "\r\n" .
    'Reply-To: '.getConf('notification_from_address') . "\r\n" ;
	
	if ( getConf('send_outbid_notifications_to_admin') ){
		// First we send a notification to the admin
		$newusername = $product->val('high_bidder');
		$msg =<<<END
The user '$newusername' has outbid '$username' for the product '$product_name' with a new high bid of $high_bid .
Visit $url to see this item.
END;
	
		if ( !getConf('admin_email') ){
			error_log("Failed to send outbid notification to admin because no admin_email was specified in the conf.ini file.");
			return false;
		}
		
		mail(getConf('admin_email'), $app->_conf['title'].' outbid notification', $msg, $mail_headers);
	}
	
	
	if ( !isset($username) ) return false;
	$user = df_get_record('users', array('username'=>'='.$username));
	
		
	if ( !isset($user) ){
		// Can't find any users by that username so we don't send a notification to 
		// the user
		return false;
	}
	
	if ( getConf('send_email_notifications') and $user->val('prefs_receive_outbid_notifications') ){
		$mail = $user->val('email');
		if ( !$mail ){
			return false;
		}
		
		
		
		
		
		$msg =<<<END
You have been outbid on the product '$product_name' with a bid amount of $high_bid .  To view this product info and/or bid again on this item, please visit $url .
END;
		$res = mail($mail, $app->_conf['title'].': You have been outbid on an auction item.', $msg, $mail_headers);
		
		if ( !$res ){
			error_log("Failed to send outbid notification to $mail for product ".$product->val('product_name'));
		}
		
		
	}
	
}


function getConf($name){
	static $conf = 0;
	if ( !is_array($conf) ){
		$res = mysql_query("select `timezone` from `config` limit 1", df_db());
		list($timezone) = mysql_fetch_row($res);
		if ( $timezone ){
			putenv('TZ='.$timezone);
		}
		@mysql_free_result($res);
		$temp = df_get_record('config',array());
		if ( isset($temp) ) $conf = $temp->strvals();
		else $conf = array();
	}
	if ( isset($conf[$name]) ) return $conf[$name];
	
	$app = Dataface_Application::getInstance();
	return @$app->_conf['df_auction'][$name];
}

/**
 * Posts a bid on a product.  This will return PEAR_Error objects if there are 
 * problems.  Problems that could occur include:
 *	1. User is not logged in
 *	2. Bidding hasn't opened yet
 *	3. Bidding is already closed
 *	4. The bid is too low.
 */
function makeBid(&$product, $amount){
	$app = Dataface_Application::getInstance();
	if ( !isLoggedIn() ){
		return PEAR::raiseError("Sorry, you must be logged in to make a bid.");
	}
	
	if ( !isRegistered() ) {
		// Register the user if he is not registered yet
		$res = registerUser();
		if ( PEAR::isError($res) ) return $res;
		
	}
	
	$bid = new Dataface_Record('bids', array());
	$bid->setValues(array(
		'username'=>getUsername(),
		'bid_amount'=>$amount,
		'product_id'=>$product->val("product_id"),
		'bid_status'=>getConf('default_bid_status')
		)
	);
	
	$res = $bid->save();
	if ( PEAR::isError($res) ) return $res;
	notifyHighBidder($product);
	return $res;
}


function closeAuction($product){

	if ( $product->val('cooked_closing_time_seconds') > time() ){
		return PEAR::raiseError("The auction for product '".$product->getTitle()."' cannot be closed because its closing time has not yet come.");
	}
	
	$app = Dataface_Application::getInstance();
	
	$username = $product->val('high_bidder');
	$amount = $product->val('high_bid_amount');
	
	$closeRec = df_get_record('closed', array('product_id'=>$product->val('product_id')));
	
	if ( !$closeRec ){
		$closeRec = new Dataface_Record('closed', array());
		$closeRec->setValues(array('product_id'=>$product->val('product_id'), 'winner'=>$username, 'bid_amount'=>$amount));
		
	}
	
	
	if (!isset($username) ){
		// No username was set as the high bidder for this product.  That means that nobody wins.
		// send an email notification to the admin about this product - and record that the
		// notification was sent - so it doesn't get sent twice.
		if ( !$closeRec->val('admin_email_sent') ){
			sendAdminEmail('Auction closed without bids','The auction for product "'.$product->getTitle().'" was closed without any bids having been made on it.');
			$closeRec->setValue('admin_email_sent',1);
			$closeRec->save();
		}
		return PEAR::raiseError("Nobody bid on the product '".$product->getTitle()."'");
	}
	
	$user = df_get_record('users', array('username'=>'='.$username));
	if ( !$user ){
		$user = new Dataface_Record('users', array());
		$user->setValue('username',$username);
		$user->save();
		
	}
	if ( !isset($user) ){
		// Although a winner is listed, they couldn't be found in the users table.
		// We need to notify the admin about this so that he can contact this person
		// manually to let them know that they won the auction.
		if ( !$closeRec->val('admin_email_sent') ){
			if ( sendAdminEmail('Action Required: Contact Auction Winner', 'The auction for product "'.$product->getTitle()."' was won by the user '$username', but no information about this user could be found in the users table.  Please contact this user and let him or her know that he/she has won the auction.")){
			
				$closeRec->setValue('admin_email_sent', 1);
				
				$closeRec->save();
			} else {
				return PEAR::raiseError("Failed to send email to admin");
			}
		}
		return PEAR::raiseError("The user '$username' who won the auction for product '".$product->getTitle()."' could not be found.");
		
	}
	
	
	
	$mail = $user->val('email');
	if ( !$mail ){
		if ( !$closeRec->val('admin_email_sent') ){
			if ( sendAdminEmail('Action Required: Contact Auction Winner', 'The auction for product "'.$product->getTitle()."' was won by the user '$username', but no email address for this user could be found.  Please contact this user and let him/her know that he/she has won the auction.")){
				$closeRec->setValue('admin_email_sent',1);
				$closeRec->save();
			} else {
				return PEAR::raiseError("Failed to send email to admin.");
			}
		}
		return PEAR::raiseError("No email address found for the user '$username' who won the auction for product '".$product->getTitle()."'");
		
	}
	
	if ( !$closeRec->val('email_sent') ){
		if ( sendEmail($mail, 'Action Required: You have Won the auction', getAuctionWinnerMessage($closeRec)) ){
		
			$closeRec->setValue('email_sent',1);
		} else {
			return PEAR::raiseError("Failed to send auction win confirmation to winner because of an email error.");
			
		}
	
	}
	
	if ( !$closeRec->val('admin_email_sent') ){
		if ( sendAdminEmail('Notification: Auction closed & winner notified', "The auction for product '".$product->getTitle()."' has been won by the user '$username'. \n\nThis user has been notified via email and has been given instructions for claiming his prize.  For more information about the product, see ".$product->getURL('-action=view').".  For user details about the winner, see ".$user->getURL('-action=view'))){
			$closeRec->setValue('admin_email_sent', 1);
		} else {
			$closeRec->save();
			return PEAR::raiseError("Failed to send email confirmation to admin about the winner.  No action required though, because the email was successfully sent to the user.");	
		}
	}
	$closeRec->save();
	return true;
	
}

function closeAuctions(){
	$app = Dataface_Application::getInstance();
	$app->_conf['nocache'] = 1;  /// We want to disable the cache if we're closing auctions
	$sql = "select p.product_id from products p left join closed c on p.product_id=c.product_id where (c.product_id IS NULL or (c.email_sent=0 and c.admin_email_sent=0)) and p.closing_time < NOW()";
	
	$res = mysql_query($sql, df_db());
	if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
	$results = array();
	while ($row = mysql_fetch_row($res) ){
		list($product_id) = $row;
		$product = df_get_record('products', array('product_id'=>$product_id));
		$results[$product_id] = closeAuction($product);
	}
	return $results;

}


function getTimezones(){
	static $lang = -1;
	if ( $lang == -1 ){
		$lang = array();
		
		
		if ( function_exists('timezone_abbreviations_list') ){
			foreach (timezone_identifiers_list() as $tzname ){
				if ( !trim($tzname) ) continue;
				$tz = new DateTimeZone($tzname);
				$dt = new DateTime("now", $tz);
				$lang[$tzname] = $tzname. ' ('.date('h:i a', time()+$tz->getOffset($dt)-date('Z')).')';
			}
			
			//asort($lang);
		} else {
			$lang[''] = 'Timezones require PHP 5.1 or higher';
		}
	}
	return $lang;
	
}

?>