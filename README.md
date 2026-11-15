Presence's Outfits.lol -  What to Wear Wardrobe Closet of Outfits
=======

> WTF to wear; skirts go spinny.

A personal wardrobe CRUD app: snap photos of your clothes, and CLIP (an
AI model that runs right on the local server, no API keys, no cloud) guesses
the name, color, pattern, category, and tags for you. Forked from the
[irev.net](https://github.com/paulgorman/irevnet) portfolio code.

This is Presence's own thing, not a real product. Backing out my
preferences is the user's problem, lol.

Install
-------
* git clone https://github.com/paulgorman/outfits.lol
* mkdir -p outfits.lol/i/sample
* mkdir outfits.lol/i/category
* mkdir outfits.lol/i/pages
* mkdir outfits.lol/m
* mysqladmin create outfits
* GRANT ALL PRIVILEGES ON outfits.* TO username@'localhost' IDENTIFIED BY 'password';
* FLUSH PRIVILEGES
* mysql outfits < outfits.lol/schema.sql
* make outfits.lol/db.php (it is gitignored, never commit it):
```php
<?php
$host = "localhost";
$user = "username";
$pass = "password";
$db = "outfits";
$dirlocation = "/path/to/outfits.lol"; // no trailing slash
```
* make yourself an admin. Get a password hash, then put it in the `admins` table:
```
php -r 'echo password_hash("user password here", PASSWORD_DEFAULT), "\n";'
mysql outfits -e "INSERT INTO admins (username, password) VALUES ('username', 'PASTE THE HASH HERE');"
```
* edit your Apache config and add (Apache 2.4 syntax):
```
        <LocationMatch "/(i|m)/.*\.(php|cgi)$">
                Require all denied
        </LocationMatch>
```

* /etc/php/8.3/cgi/php.ini
  * short_open_tag = On
  * upload_max_filesize = 200M
  * post_max_size = 200M
  * memory_limit = 256M
  * browscap = /etc/php/browscap.ini
  * session.cookie_lifetime = 86400
  * session.gc_maxlifetime = 86400

* /etc/php/8.3/cli/php.ini
  * everything above, but swap in:
    * memory_limit = -1

CLIP: the robot that fills in the blanks
-----------------------------------------
When the user uploads a new item with blank fields, `offline-classify.php` is
forked, looks at the photos, and fills in the name, summary, category, and tags.
It runs as its own command-line PHP process, not inside the web request.

* cd outfits.lol && composer install
  (composer.json and composer.lock are already here: this pulls in
  [TransformersPHP](https://github.com/CodeWithKyrian/transformers-php)
  and the ONNX runtime it needs)
* the PHP CLI needs the **FFI** extension (`php -m | grep FFI`)
* the PHP CLI needs about **1.35 GB of RAM** for the fashion-clip model.
  Web PHP never loads the model, so its memory_limit can stay small.
* try it on any photo (the database needs to exist first, since CLIP picks
  from the user's categories and tags):
```
php offline-classify.php rando-image.jpg
```
* the FIRST run downloads the
  [fashion-clip](https://huggingface.co/patrickjohncyh/fashion-clip) model
  from Hugging Face by itself (about 600 MB, into `.transformers-cache/`).
  Go make a sandwich. There is no separate download step.
* fashion-clip's two config files don't work with TransformersPHP as they come
  from Hugging Face. `offline-classify.php` patches them from
  `clip-model-fixes/` every time it starts, so this is automatic.
  See `clip-model-fixes/README.md` for the gory details.
* more tricks:
```
php offline-classify.php --sample 7 --dry-run   # what WOULD it fill in?
php offline-classify.php --mysteries            # retry uploads CLIP fell over on
php offline-classify.php --model base ...       # plain CLIP instead of fashion-clip
```

The 3D carousel: bring your own
-------------------------------
The category pages use the **FWD Royal 3D Carousel**, a paid JavaScript
library from FWDesign, so its file is NOT in this repo (it's gitignored).
Buy your own license, then drop your copy in:

* templates/js/FWDRoyal3DCarousel_uncompressed.js

Without it, the category pages throw `FWDUtils is not defined` in the
browser console and show no carousel. Everything else works fine.

Notes
-----
alias ci='git add -A;git commit;git push origin master;'

Depends upon ffmpeg with ffprobe, mod_h264_streaming, php8, mariaDB, ImageMagick

PHP modules: fileinfo, gd, mysqli, session, exif, hash, iconv, phar, imagick (HEIC iPhone photos), FFI (CLIP, command line only)
