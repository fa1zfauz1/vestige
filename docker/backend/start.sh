#!/bin/sh
set -e
cd /var/www/html/public
exec php -S 0.0.0.0:8000 \
  /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
