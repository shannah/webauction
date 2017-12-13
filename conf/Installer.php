<?php
class conf_Installer {
	function update_10(){
		$sql[] = 'ALTER TABLE `config` ADD `title` VARCHAR( 64 ) NULL AFTER `auction_id`';
		
		foreach ($sql as $q){
			xf_db_query($q, df_db());
			
		}
	
	}
}