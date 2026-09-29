# Ki Reservation - composer.json runtime dependencies
# Resolving from composer.json here ensures vendor/ matches what the application requires.
# Repo konsolidasyonu (2026-09-16) - build context artik deploy/'un ust dizini (repo koku), tek
# repoda uygulama kodu + deploy config bir arada. Onceki "src/" alt klasoru kalkti, kod dogrudan
# repo kokunde (application/, assets/, vb.).
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer update  --no-interaction --optimize-autoloader --ignore-platform-reqs

FROM alextselegidis/easyappointments:1.6.0

# Ki Reservation - multi-tenant cloud SaaS instance (2026-08-26). Same base-image strategy as the
# Salon Flora deployment (Dockerfile.ki-reservation there): the base image supplies the PHP/Apache
# runtime and extensions only; the entire application source below is Ki Reservation's own tree
# (tracked in the ki-reservation GitHub repository, copied in here as of the multi-tenant conversion).
# The base image leaves mod_rewrite DISABLED by default - but our src/.htaccess routes every clean
# URL (/health, /booking, /login...) through the CodeIgniter front controller. Without the module the
# whole app 404s on any segment route. a2enmod here makes the Apache apache2.conf AllowOverride All
# (from docker-php.conf) actually take effect for .htaccess.
RUN a2enmod rewrite proxy proxy_http headers && rm -rf /var/www/html/*
COPY --chown=www-data:www-data . /var/www/html/
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor

# Configures entrypoint script for the container
COPY deploy/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
