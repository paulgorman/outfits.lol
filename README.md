Presence's Outfits.lol -  What to Wear Wardrobe Closet of Outfits
=======

Install
-------
* git clone https://github.com/paulgorman/outfits.lol
* mkdir -p outfits.lol/i/artist
* mkdir outfits.lol/i/category
* mkdir outfits.lol/i/pages
* mkdir outfits.lol/m
* mysqladmin create outfits
* GRANT ALL PRIVILEGES ON outfits.* TO username@'localhost' IDENTIFIED BY 'password';
* FLUSH PRIVILEGES
* mysql outfits < outfits.lol/schema.sql
* edit outfits.lol/db.php: $user and $pass
* edit system httpd.conf and add
```
	<LocationMatch "/(i|m)/.*\.(php|cgi)$">
		Order Deny,Allow
		Deny from All
	</LocationMatch>
```

* check outfits.lol/php.ini

Notes
-----
alias ci='git add -A;git commit;git push origin master;'

Depends upon ffmpeg with ffprobe, mod_h264_streaming, php7, mariaDB, ImageMagick 

PHP modules: fileinfo, gd, mysqli, session, exif, hash, iconv, phar
