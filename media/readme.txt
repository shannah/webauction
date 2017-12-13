PHP Image Repository
Created by Steve Hannah <shannah@sfu.ca>
Copyright 2007 Simon Fraser University
All rights reserved

Portions of this script were adapted from:
http://veryraw.com/history/2005/03/image-resizing-with-php/

This application is a simple image repository application that allows you to 
host a collection of images and make them easily publishable to the web.

Usage:
------

1. Upload photos into the photos directory.
2. Point your web browser to the gallery.php script 
(http://yourdomain.com/path.to/imagerep/gallery.php)
3. Find the image that you want to place in the web page, adjust its size, 
then copy & paste its url into your web page.


Installation:
-------------

Step 1: Upload the imagelib directory and all its contents (including the
		  .htaccess file onto your web server.

Step 2: Make sure that the cache directory is writable by the webserver
		  process if you want to use the cache.

Step 3: Edit the RewriteBase directive in the .htaccess file to be the path
		to the imagelib directory.  E.g. If you installed imagelib into to 
		  be accessible at http://yourdomain.com/path/to/imagelib , you would 
		  make this value '/path/to/imagelib'

Step 4: Add images to your photos directory and try to access them through
		  your web browser.  You're good to go!


Requirements:
-------------
1. PHP 4.3+
2. GD Image Library installed with support for at least one of JPEG, GIF, or PNG.
3. Safe mode must be OFF if you want to use caching.

