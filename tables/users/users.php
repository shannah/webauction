<?php
class tables_users{

/**
 * These calculated functions were used for the SFU plantsale because we 
 * didn't store this information.  Rather, we obtained if from LDAP.
 *
	function field__email(&$record){
		$info = $record->val('ldap_info');
		return $info['mail'];
	}
	
	function field__fullname(&$record){
		$info = $record->val('ldap_info');
		return $info['cn'];
	}
	
	function field__title(&$record){
		$info = $record->val('ldap_info');
		return $info['title'];
	}
	
	function field__department(&$record){
		$info = $record->val('ldap_info');
		return $info['ou'];
	}
	
	function field__phone(&$record){
		$info = $record->val('ldap_info');
		return $info['telephoneNumber'];
	}
	
	function field__ldap_info(&$record){
		$info = getLDAPUserInfo($record->val("username"));
		return $info;
	}
/**/

	function getPermissions(&$record){
	/*
		$app =& Dataface_Application::getInstance();
		$del =& $app->getDelegate();
		$perms =& $del->getPermissions($record);
	*/
		//if ( $record ) echo "Yes"; else echo "No";
		//if ( $record and $record->val('username') ) echo "We have a username";
		if ( isAdmin() ){
			$perms = Dataface_PermissionsTool::ALL();
		} else if (  $record and $record->strval('username') == getUsername()) {
			$perms = Dataface_PermissionsTool::getRolePermissions('EDIT');
		} else {
			$perms = Dataface_PermissionsTool::NO_ACCESS();
		}
		$perms['new'] = 1;
		return $perms;
	}
	
	function username__permissions(&$record){
		$perms = $this->role__permissions($record);
		$perms['new'] = 1;
		return $perms;
	
	}
	
	function role__permissions(&$record){
		if ( isAdmin() ){
			return null;
		} else {
			return array('edit'=>0, 'view'=>1, 'delete'=>0, 'new'=>0);
		}
	}
	
	function block__after_view_tab_content(){
		if (isAdmin()){
			$app =& Dataface_Application::getInstance();
			$record =& $app->getRecord();
			df_display(array('user'=>&$record), 'after_user_profile.html');
		}
	}
	
	function field__fullname(&$record){
		return $record->val('firstname').' '.$record->val('lastname');
	}
	
	function role__default(){
		return 'USER';
	}
	
	function beforeSave(&$record){
		if ( $record->valueChanged('username') ){
			$res = mysql_query("select count(*) from `users` where `username`='".addslashes($record->strval('username'))."'", df_db());
			if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			list($num) = mysql_fetch_row($res);
			if ( $num > 0 ) return PEAR::raiseError("That username already exists.  Please choose a different one.", DATAFACE_E_NOTICE);
		}	
	}

}
?>