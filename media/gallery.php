<?php

define('GALLERY_THUMBNAIL_WIDTH', 65);
define('GALLERY_THUMBNAIL_HOVER_WIDTH', 200);

class Gallery {

	var $forbidden_files = array();
	var $replacements = array();
	var $root;
	
	function Gallery($root, $forbidden_files=array(), $replacements=array()){
		$this->forbidden_files = $forbidden_files;
		$this->replacements = $replacements;
		$this->root = $root;
	}

	function path($path){
		$path = explode('/', $path);
		$out = array();
		foreach ($path as $file){
			if ( $file == '.' or $file == '' or $file == '..' ) continue;
			$out[] = $file;
		}
		$path = $this->root.implode('/',$out);
		return $path;
	}
	function fopen($filename, $mode){
		
		return fopen($this->path($filename), $mode);
	}
	
	
	function file($path){
		if ( $this->isForbidden($path) ) return false;
		
		$contents = file($this->path($path));
		if ( is_array($contents) ){
			$replacements = $this->getReplacements($path);
			$contents = preg_replace(array_keys($replacements), array_values($replacements), $contents);
		}
		return $contents;
	}
	
	function file_get_contents($path){
		if ( $this->isForbidden($path) ) return false;

		$contents = file_get_contents($this->path($path));
		if ( $contents !== false ){
			$replacements = $this->getReplacements($path);
			if ( count($replacements) > 0 ){
				$contents = preg_replace(array_keys($replacements), array_values($replacements), $contents);
			}
		}
		return $contents;
	}
	
	function opendir($path){
		return opendir($this->path($path));
	}
	
	function readdir($dh){
		$file = readdir($dh);
		while ( $file !== false and $this->isForbidden($file) ){
			$file = readdir($dh);
		}
		return $file;
	}
		
	function isForbidden($path){
		$file = basename($path);
		foreach ( $this->forbidden_files as $ffile ){
			if ( $ffile{0} == '/' and preg_match($ffile, $file) ) return true;
			else if ($ffile == $file) return true;
		}
		return false;
	}
	
	function getReplacements($path){

		$replacements = array();
		if ( isset($this->replacements[basename($path)]) ){

			$replacements = array_merge($replacements, $this->replacements[basename($path)]);

		}
		if ( isset($this->replacements['__global__']) ){
			$replacements = array_merge($replacements, $this->replacements['__global__']);
		}

		return $replacements;
	}
}


$b = new Gallery(dirname(__FILE__).'/photos/', 
	array(),
	array('conf.ini'=>array(
		'/^ *password=.*$/m'=>'password=XXXXXXX',
		'/^ *user=.*$/m'=>'user=XXXXXX'
		)
	)
);

$path = $_GET['-path'];
if ( substr($path,0,2) == './' ) $path = substr($path,2);
if ( file_exists($b->path($path)) ){
	echo '<html><head>
			<style type="text/css"><!--
			body { background-color: #e9e9e9; font-family: Palatino;}
			.sourcecode {background-color: white; border: 1px solid #ccc; margin: 10px; padding: 10px;}
			a { text-decoration: none; font-family: sans-serif; font-size: 11px; color: black;}
			img { border: none;}
			li { list-style-type: none; border-top: 1px solid #ccc; border-bottom: 1px solid white;}
			li:hover { background-color: #ccc;}
			ul#photo-list li { list-style-type: none; list-style-image: none; display: inline;}
			#photo-details { display: none;float: right; width: 33%; padding: 2em; background-color: white; border: 1px solid #ccc;}
			#photo-details p { font-size: 12px;}
			#photo-details input { width: 100%;}
			#photo-details textarea { width: 100%;}
			input#selected-photo-width { width: 50px;}
			//--></style>
			<script language="javascript"><!--
			var selected_path = null;
			function selectPhoto(path){
				var status_message = document.getElementById(\'Status-Message\');
				status_message.style.display = \'block\';
				selected_path = path;
				var details = document.getElementById(\'photo-details\');
				details.style.display=\'block\';
				var photo = document.getElementById(\'selected-photo\');
				photo.style.display=\'none\';
				var widthfield = document.getElementById(\'selected-photo-width\');
				var width = parseInt(widthfield.value);
				var max_width = '.GALLERY_THUMBNAIL_HOVER_WIDTH.';
				if ( width > 0 ){
					max_width = width;
				}
				
				var contrast_field = document.getElementById(\'photo-contrast\');
				var brightness_field = document.getElementById(\'photo-brightness\');
				var gaussian_blur_field = document.getElementById(\'photo-gaussian-blur\');
				
				var qstr = \'?max_width=\'+max_width;
				if ( contrast_field.value ) qstr = qstr + \'&filter:contrast=\'+contrast_field.value;
				if ( brightness_field.value ) qstr = qstr + \'&filter:brightness=\'+brightness_field.value;
				if ( gaussian_blur_field.value ) qstr = qstr + \'&filter:gaussian_blur=\'+gaussian_blur_field.value;
				
				photo.src = path+qstr;
				widthfield.value = max_width;
				
				var urlfield = document.getElementById(\'photo-url\');
				var location = window.location;
				var pasteurl = \'http://\'+window.location.host+window.location.pathname.replace(\'gallery.php\', path);
				pasteurl = pasteurl + qstr;
				
				urlfield.value = pasteurl;
				var htmlfield = document.getElementById(\'photo-html\');
				htmlfield.value = \'<img src="\'+pasteurl+\'"/>\';
				
				
				
			}
			
			function updatePhoto(){
				selectPhoto(selected_path);
			}
			
			function imageLoaded(img){
				document.getElementById(\'Status-Message\').style.display = \'none\';
				document.getElementById(\'selected-photo\').style.display = \'\';
			}
			//--></script>
			</head>
			
		<body>
		
		';
	if ( is_file($b->path($path)) ){
		echo '<h2>Source of file <em>'.$path.'</em></h2>';
		echo '<div><a href="'.$_SERVER['PHP_SELF'].'?-path='.dirname($path).'">Back up to '.dirname($path).'</a></div>';
		echo '<div class="sourcecode">';
		$contents = $b->file_get_contents($path);
		highlight_string($contents);
		echo '</div>';
	} else if ( is_dir($b->path($path)) ){
		
		$dh = $b->opendir($path);
		if ( !$dh ){
			echo 'Error opening directory '.$b->path($path);
		}
		echo '<h2>Directory <em>'.$path.'</em></h2>';
		echo '<div><a href="'.$_SERVER['PHP_SELF'].'?-path='.dirname($path).'">Back up to '.dirname($path).'</a></div>';
		
		$subdirs = array();
		$images = array();
		echo '<div id="photo-details">
		    <h3>Selected Photo</h3>
		    
			<img id="selected-photo" onload="imageLoaded(this)"/><br/>
			<div id="Status-Message" style="display: none">Processing ... Please wait ...</div>
			<h4>Instructions</h4>
			<p>Adjust the size of the photo using <em>Photo Width</em> field.  When you are happy
			with the size of the photo, you can copy and paste either the URL for the photo, or
			the HTML source code into your web page.</p>
			<table>
			<tr><th><label for="selected-photo-width">Photo Width:</label></th><td><input type="text" id="selected-photo-width" size="4" onchange="updatePhoto()"/></td></tr>
			<tr><th>Contrast:</th><td><input type="text" size="4" id="photo-contrast" onchange="updatePhoto()"/></td></tr>
			<tr><th>Brightness:</th><td><input type="text" size="4" id="photo-brightness" onchange="updatePhoto()"/></td></tr>
			<tr><th>Gaussian Blur</th><td><input type="text" size="4" id="photo-gaussian-blur" onchange="updatePhoto()"/></td></tr>
			</table>
			<fieldset><legend>Copy &amp; Paste</legend>
			<label>Photo URL</label><input type="text" id="photo-url"/><br/>
			<label>HTML Source Code</label><br/><textarea id="photo-html" rows="5" ></textarea>
			</fieldset>
		</div>';
		
		while ( ( $file = $b->readdir($dh) ) !== false ){
			$newpath = $path.(strlen($path)>0?'/':'').$file;
			if ( is_file($b->path($newpath))  ){
				$images[] = array(
					'src'=>'photos/'.$newpath,
					'href'=>$_SERVER['PHP_SELF'].'?-path='.urlencode($newpath)
					); //$img = '<img src="'.CONF_DATAFACE_URL.'/images/file_icon.gif"/>';
			} else if ( is_dir($b->path($newpath)) ){
				$subdirs[] = array(
					'href'=>$_SERVER['PHP_SELF'].'?-path='.urlencode($newpath),
					'name'=> $file
					);// $img = '<img src="'.CONF_DATAFACE_URL.'/images/folder_icon.gif"/>';
			}
			//echo '<li><a href="'.$_SERVER['PHP_SELF'].'?-path='.urlencode($newpath).'">'.$img.$file.'</a></li>';
		}
		if ( count($subdirs) > 2 ){
			echo '<h3>Subdirectories</h3>';
			echo '<ul>';
			foreach ($subdirs as $subdir ){
				if ( $subdir['name'] == '.' or $subdir['name'] == '..') continue;
				echo '<li><img src="images/folder_icon.gif"/><a href="'.$subdir['href'].'">'.$subdir['name'].'</a></li>';
			}
			echo '</ul>';
		}
		
		echo '<h3>Photos in this Directory</h3>
			<h4>Instructions</h4>
			<p>Click on a photo to see a larger version and details for including it in a web page.</p>';
		echo '<ul id="photo-list">';
		foreach ($images as $image ){

			echo '<li><img src="'.$image['src'].'?max_width='.GALLERY_THUMBNAIL_WIDTH.'" onclick="selectPhoto(\''.$image['src'].'\');"/></li>';
		}
		echo '</ul>';
	}
	echo '</body></html>';
}
?>