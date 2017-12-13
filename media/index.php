<?php
/**
 * ImageLib
 * Created by Steve Hannah <shannah@sfu.ca>
 * Copyright 2007 Simon Fraser University
 * All rights reserved
 *
 * Portions of this script were adapted from:
 * http://veryraw.com/history/2005/03/image-resizing-with-php/
 *
 * This script is a simple image resizing script that works in conjunction with
 * mod_rewrite to provide a simple mechanism for offering web site images
 * in multiple sizes.
 *
 * Any images placed in the photos directory and subdirectories thereof can
 * be accessed using their normal URL (e.g. photos/myimage.jpg).  But if you 
 * add a query string to the image (e.g. photos/myimage.jpg?max_width=100)
 * you can cause the server to dynamically resize the image so that it will
 * be returned in the correct size.
 *
 * Available parameters:
 * ----------------------
 *
 * max_width : An integer number of pixels that is the maximum width for this 
 *			   image.  If the image is wider than this, it will be resized to
 *			   fit, maintaining aspect ratio.  If the image is narrower than
 *			   this, it will be output as is.
 *
 * format : One of 'gif', 'jpg', 'jpeg', or 'png'.  Defaults to the same as
 *		    the source image.
 *
 *
 * Installation:
 * -------------
 *
 * Step 1: Upload the imagelib directory and all its contents (including the
 *		   .htaccess file onto your web server.
 * 
 * Step 2: Make sure that the cache directory is writable by the webserver
 *		   process if you want to use the cache.
 *
 * Step 3: Edit the RewriteBase directive in the .htaccess file to be the path
 *         to the imagelib directory.  E.g. If you installed imagelib into to 
 * 		   be accessible at http://yourdomain.com/path/to/imagelib , you would 
 * 		   make this value '/path/to/imagelib'
 *
 * Step 4: Add images to your photos directory and try to access them through
 * 		   your web browser.  You're good to go!
 *
 *
 * Requirements:
 * -------------
 * 1. PHP 4.3+
 * 2. GD Image Library installed with support for at least one of JPEG, GIF, or PNG.
 * 3. Safe mode must be OFF if you want to use caching.
 *
 * Usage Examples:
 * ----------------
 *
 * The following examples assume that you have imagelib installed at
 * http://yourdomain.com/imagelib .
 *
 * Suppose you have a portait of yourself named 'me.jpg'.  Upload this portait
 * into the photos directory.  It should now be accessible at 
 * http://yourdomain.com/imagelib/photos/me.jpg .
 *
 * Now if you want to view this photo so that its width is no bigger than 100 
 * pixels, just enter the URL http://yourdomain.com/imagelib/photos/me.jpg?maxwidth=100
 *
 * If we want to see the photo as a PNG file, we would enter:
 * http://yourdomain.com/imagelib/photos/me.jpg?format=png
 *
 * We can also organize our photos into subdirectories within the photos directory.
 * For example, maybe we want to store our portrait in a directory called
 * portraits, so it is accessible at http://yourdomain.com/imagelib/photos/portraits/me.jpg
 * That is fine, and you can still work with it and resize it in the same way.
 */
$directoryUrl = dirname($_SERVER['SCRIPT_NAME']);
$imageUrl = $_SERVER['REDIRECT_URL'];
$imagePath = substr($imageUrl, strlen($directoryUrl)+8);

$_GET['path'] = $imagePath;
 define('IMAGELIB_JPEG_QUALITY', 90);  // 0 to 100 .  100 is best quality
 define('IMAGELIB_PNG_COMPRESSION', 0); // 0 to 9.  0 is No compression
define('MEDIA_CACHE_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR.'mediacache');
if ( !is_dir(MEDIA_CACHE_PATH) ){
	mkdir(MEDIA_CACHE_PATH);
}
 

if ( !isset($_GET['path']) ){
	header('Location: gallery.php');
	exit;
}
 
$image_path = 'photos/'.$_GET['path'];



//$image_type = exif_imagetype($image_path);

$size = GetImageSize($image_path);
$width = $size[0];
$height = $size[1];
$image_type = $size[2];

$filters = preg_grep('/^filter:/', array_keys($_GET) );
if ( !isset($_GET['max_width']) && !isset($_GET['max_height']) &&!isset($_GET['format']) && (count($filters) == 0)){
	// No max width is set.. so we can just return the image unaltered
	header('Content-type: '.image_type_to_mime_type($image_type));
	header('Connection: close');
	header('Cache-Control: max-age=3600');
	echo file_get_contents($image_path);
	flush();
	exit;
}

$max_height = @$_GET['max_height'] ? $_GET['max_height'] : 1000;
$max_width = @$_GET['max_width'] ? $_GET['max_width'] : 1000;

$filterstr = array();
foreach ($filters as $filter){
	$filterstr[] = urlencode($filter).'='.urlencode($_GET[$filter]);
}
$filterstr = implode('&', $filterstr);
$cachestr = $filterstr.'&max_width='.$max_width.'&max_height='.$max_height;
if (isset($_GET['format']) ) $cachestr .= '&format='.$_GET['format'];
$cache_code = md5(md5($image_path).'?'.$cachestr);
$cache_path = MEDIA_CACHE_PATH.DIRECTORY_SEPARATOR.$cache_code;
if ( file_exists($cache_path) and filemtime($cache_path) > filemtime($image_path) ){
	header('Content-type: '.image_type_to_mime_type($image_type));
	header('Cache-Control: max-age=360000');
	header('Conent-Length: '.filesize($cache_path));
	header('Connection: close');
	$fp = fopen($cache_path, "rb");
	//start buffered download
	while(!feof($fp))
	        {
	        print(fread($fp,1024*16));
	        flush();
	        ob_flush();
	        }
	fclose($fp);
	//echo file_get_contents($cache_path);
	exit;
}


// get the ratio needed
$x_ratio = $max_width / $width;
$y_ratio = $max_height / $height;

// if image allready meets criteria, load current values in
// if not, use ratios to load new size info
if ( ($width <= $max_width) && ($height <= $max_height) ) {
  $tn_width = $width;
  $tn_height = $height;
} else if (($x_ratio * $height) < $max_height) {
  $tn_height = ceil($x_ratio * $height);
  $tn_width = $max_width;
} else {
  $tn_width = ceil($y_ratio * $width);
  $tn_height = $max_height;
}


// read image
//$src = ImageCreateFromJpeg($image);

switch ( $image_type ){
	case IMAGETYPE_GIF: $src = imagecreatefromgif($image_path);break;
	case IMAGETYPE_PNG: $src = imagecreatefrompng($image_path);break;
	case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($image_path);break;
	default: $src = imagecreatefromstring(file_get_contents($image_path)); break;
}
//imagejpeg($im);

// set up canvas
$dst = imagecreatetruecolor($tn_width,$tn_height);


if ( ($image_type == IMAGETYPE_GIF) || ($image_type == IMAGETYPE_PNG) ) {
	$trnprt_indx = imagecolortransparent($src);
	
	// If we have a specific transparent color
	if ($trnprt_indx >= 0) {
		
		// Get the original image's transparent color's RGB values
		$trnprt_color = imagecolorsforindex($src, $trnprt_indx);
		
		// Allocate the same color in the new image resource
		$trnprt_indx = imagecolorallocate($dst, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
		
		// Completely fill the background of the new image with allocated color.
		imagefill($dst, 0, 0, $trnprt_indx);
		
		// Set the background color for new image to transparent
		imagecolortransparent($dst, $trnprt_indx);
 
	 
	 } 
	 // Always make a transparent background color for PNGs that don't have one allocated already
	 elseif ($image_type == IMAGETYPE_PNG) {
	 
		 // Turn off transparency blending (temporarily)
		 imagealphablending($dst, false);
		 
		 // Create a new transparent color for image
		 $color = imagecolorallocatealpha($dst, 0, 0, 0, 127);
		 
		 // Completely fill the background of the new image with allocated color.
		 imagefill($dst, 0, 0, $color);
		 
		 // Restore transparency blending
		 imagesavealpha($dst, true);
	 }
 }


// Apply all of the filters
foreach ( $filters as $filter ){
	switch( $filter ){
		case 'filter:negate': imagefilter($src, IMG_FILTER_NEGATE); break;
		case 'filter:grayscale': imagefilter($src, IMG_FILTER_GRAYSCALE); break;
		case 'filter:brightness': imagefilter($src, IMG_FILTER_BRIGHTNESS, $_GET['filter:brightness']); break;
		case 'filter:contrast': imagefilter($src, IMG_FILTER_CONTRAST, $_GET['filter:contrast']); break;
		case 'filter:colorize': 
			$args = explode(',',$_GET['filter:colorize']);
			imagefilter($src, IMG_FILTER_COLORIZE, $args[0], $args[1], $args[2]);
			break;
		case 'filter:edgedetect': imagefilter($src, IMG_FILTER_EDGEDETECT); break;
		case 'filter:emboss': imagefilter($src, IMG_FILTER_EMBOSS); break;
		case 'filter:gaussian_blur': 
			for ( $i=0; $i<$_GET['filter:gaussian_blur']; $i++){
				imagefilter($src, IMG_FILTER_GAUSSIAN_BLUR);
		}
		break;
		case 'filter:selective_blur': 
			for ($i=0; $i<$_GET['filter:selective_blur']; $i++){
				imagefilter($src, IMG_FILTER_SELECTIVE_BLUR); 
			}
			break;
		case 'filter:mean_removal': imagefilter($src, IMG_FILTER_MEAN_REMOVAL); break;
		case 'filter:smooth': imagefilter($src, IMG_FILTER_SMOOTH, $_GET['filter:image_smooth']); break;
		
	
	}

}


// copy resized image to new canvas
ImageCopyResampled($dst, $src, 0, 0, 0, 0, $tn_width,$tn_height,$width,$height);

// send the header and new image


switch (strtolower(@$_GET['format'])){
	case 'gif': $format = IMAGETYPE_GIF;break;
	case 'jpg':
	case 'jpeg':
		$format = IMAGETYPE_JPEG;break;
	case 'png':
		$format = IMAGETYPE_PNG;break;
	default:
		$format = $image_type;
}

switch ($format){

	case IMAGETYPE_GIF:
		header("Content-type: image/gif");
		header('Cache-Control: max-age=3600');
		imagegif($dst,null,-1);
		imagegif($dst,$cache_path,-1);
		break;
	case IMAGETYPE_JPEG:
		header("Content-type: image/jpeg");
		header('Cache-Control: max-age=3600');
		imagejpeg($dst,null,IMAGELIB_JPEG_QUALITY);
		imagejpeg($dst,$cache_path,IMAGELIB_JPEG_QUALITY);
		break;
	case IMAGETYPE_PNG:
		header("Content-type: image/png");
		header('Cache-Control: max-age=3600');
		imagepng($dst,null,IMAGELIB_PNG_COMPRESSION);
		imagepng($dst,$cache_path,IMAGELIB_PNG_COMPRESSION);
		break;
}
//ImageJpeg($dst, null, -1);

// clear out the resources
ImageDestroy($src);
ImageDestroy($dst);

?>