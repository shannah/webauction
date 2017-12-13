<?php
class tables_config {
	function getPermissions(&$record){
		if ( isAdmin() ) return Dataface_PermissionsTool::ALL();
		return Dataface_PermissionsTool::NO_ACCESS();
	}
}
?>