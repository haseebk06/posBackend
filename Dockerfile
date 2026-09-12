FROM richarvey/nginx-php-fpm:3.1.6

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY . .

# Install PHP dependencies at build time, not on every container start
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --working-dir=/var/www/html

# Image config
ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
# Without this, nginx's default try_files hard-404s any path that
# isn't a literal file (i.e. every Laravel route except "/")
ENV PHP_CATCHALL=1

# Laravel config
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

CMD ["/start.sh"]
