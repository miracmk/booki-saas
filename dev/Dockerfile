# Ki Reservation (2026-08-28, Dalga 2 Faz 29) - composer.json gained two new runtime dependencies
# (robthree/twofactorauth, bacon/bacon-qr-code, for MFA/TOTP) that do NOT exist in the base image's
# own baked-in vendor/ - that vendor/ was built from the STOCK EasyAppointments composer.json, before
# our fork's composer.json (a full superset, listing every package the app needs) ever ran. Without
# this stage, the app would silently ship with a stale vendor/ missing both new classes, and MFA
# setup would fatal with "Class not found" on first use. Resolving from OUR composer.json here
# replaces the base image's vendor/ wholesale with one that matches what the source actually requires.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer update --no-dev --no-interaction --optimize-autoloader --ignore-platform-reqs

FROM alextselegidis/easyappointments:1.6.0

# Ki Reservation - multi-tenant cloud SaaS instance (2026-08-26). Same base-image strategy as the
# Salon Flora deployment (Dockerfile.ki-reservation there): the base image supplies the PHP/Apache
# runtime and extensions only; the entire application source below is Ki Reservation's own tree
# (tracked in the ki-reservation GitHub repository, copied in here as of the multi-tenant conversion).
# The base image leaves mod_rewrite DISABLED by default - but our src/.htaccess routes every clean
# URL (/health, /booking, /login...) through the CodeIgniter front controller. Without the module the
# whole app 404s on any segment route. a2enmod here makes the Apache apache2.conf AllowOverride All
# (from docker-php.conf) actually take effect for .htaccess.
RUN a2enmod rewrite
COPY --chown=www-data:www-data . /var/www/html/
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor

# Fixes the base image's own entrypoint script (outside /var/www/html, so the COPY above never
# touches it) so it stops generating "Easy!Appointments" as the SMTP useragent header on every
# container start - see docker-entrypoint.sh in this directory for the one-line diff against the
# base image's original.
COPY dev/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
