<html>
	<body>
		<h1>Web Auction Installation Instructions</h1>
		
		<h2>System requirements</h2>
		<ol>
			<li>PHP 4.3 or higher (Safe mode must be turned off, and short tags turned on)</li>
			<li>MySQL 4.1 or higher</li>
		</ol>
		
		<p>Before you can use WebAuction, you need to set up the database and 
		make some changes to the conf.ini file.</p>
		
		<h2>License</h2>
		<textarea cols="60" rows="20">
Copyright (C) 2005-2007 Steve Hannah

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 of the license.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
		</textarea>
		
		<h2>Step 1: Create the MySQL database & MySQL User</h2>
		<p>Please create a database in your MySQL server where WebAuction can 
		store its data.  Also create a MySQL user account that WebAuction can
		use to access the database.  This user account should have full access
		privileges to this database.</p>
		
		<h2>Step 2: Create database tables</h2>
		<p>Please run the following MySQL commands to set up the Web Auction 
		database.  (You can copy and paste these commands directly into MySQL
		if you like).</p>
		<textarea style="width: 100%; height: 200px;"><?php include 'install/install.sql'?></textarea>
		
		<h2>Step 3: Update conf.ini file</h2>
		<p>Please update the <em>[_database]</em> section of the conf.ini
		file so that it reflects the connection information to the database
		that you just created for WebAuction.</p>
		
		<h2>Step 4: File Permissions</h2>
		<ol>
			<li>Make the <em>templates_c</em> directory writable by the web server.  This is where
			the <a href="http://smarty.php.net">Smarty</a> templates are cached.</li>
			
			<li>Make the <em>media/photos</em> directory writable by the 
			web server.  This is where the images for the auction products will be stored.
			</li>
		</ol>
		
		<h2>Step 5: Log In</h2>
		<p>At this point, your web auction installation is available <a href="index.php">here</a>.
		You can log into the administration section <a href="index.php?-action=login">here</a> with username <em>admin</em> and password <em>password</em>.
		</p>
		
		
		<h1>Troubleshooting</h1>
		<h2>Support</h2>
		<p>If you run into problems with the installation, please visit the <a href="http://data-face.com/forum/viewforum.php?f=5" target="_blank">WebAuction forum</a>.</p>
		
		<h2>Common Problems</h2>
		<dl>
			<dt>I get a blank page when I try to access the auction script</dt>
			<dd>If your PHP installation is configured to suppress errors in the browser, then <em>any</em>
			error will result in a blank page.  Your first troubleshooting step should be to
			find out what the error is by either:
			<ol>
				<li>
					<p><b>Checking your error log.</b>  (Error logs may be located anywhere, but a common location
				for Linux distributions in /var/log/httpd/error_log).  If you have shell access
				you can read the last few lines of the error log with the command:
				<code><pre>tail /var/log/httpd/error_log</pre></code>.</p>
				<p>If you do not have shell access then you may be able to access your error log
				through your web administration panel.  Please check with your hosting provider 
				to find out how to access the error log.</p>
				
				</li>
				<li>
					<p><b>Turning on display of errors in your browser</b>.  You can do this
					by adding the following line to the beginning of the <em>index.php</em> file
					(after the opening &lt;?php tag):
					<code><pre>ini_set('display_errors','on');
error_reporting(E_ALL);
					</pre></code>
					</p>
				</li>
			</ol>
			</dd>
		</dl>
	
	</body>

</html>