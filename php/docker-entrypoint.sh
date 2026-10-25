#!/bin/bash

# Destination of env file inside container
ENV_FILE="/var/www/.env"

# Loop through XDEBUG, PHP_IDE_CONFIG and REMOTE_HOST variables and check if they are set.
# If they are not set then check if we have values for them in the env file, if the env file exists. If we have
# values for them add exports to ~/.bashrc.
for VAR in XDEBUG PHP_IDE_CONFIG REMOTE_HOST
do
  if [ -z "${!VAR}" ] && [ -f "${ENV_FILE}" ]; then
    VALUE=$(grep $VAR $ENV_FILE | cut -d '=' -f 2-)
    if [ ! -z "${VALUE}" ]; then
      sed -i "/$VAR/d" ~/.bashrc
      echo "export $VAR=$VALUE" >> ~/.bashrc
    fi
  fi
done

. ~/.bashrc

if [ -z "${REMOTE_HOST}" ]; then
  REMOTE_HOST="host.docker.internal"
  sed -i "/REMOTE_HOST/d" ~/.bashrc
  echo "export REMOTE_HOST=\"$REMOTE_HOST\"" >> ~/.bashrc
  . ~/.bashrc
fi

service cron start

# The Laravel source is bind-mounted at runtime, so the image build cannot
# create Vite's manifest for the mounted source tree. Build it once on startup
# when live RMS assets are not present; this prevents a clean clone from
# failing with "Vite manifest not found".
if [ -f /var/www/html/package.json ] && [ ! -f /var/www/html/public/build/manifest.json ]; then
  echo "[RMS] Vite build missing; installing frontend dependencies and building assets."
  npm install --ignore-scripts --prefix /var/www/html \
    && npm run build --prefix /var/www/html \
    || echo "[RMS] Vite build failed; server-rendered pages remain available."
fi

if [ "true" == "$XDEBUG" ] && [ ! -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ]; then
  sed -i '/PHP_IDE_CONFIG/d' /etc/cron.d/laravel-scheduler
  if [ ! -z "${PHP_IDE_CONFIG}" ]; then
    echo -e "PHP_IDE_CONFIG=\"$PHP_IDE_CONFIG\"\n$(cat /etc/cron.d/laravel-scheduler)" > /etc/cron.d/laravel-scheduler
  fi
  docker-php-ext-enable xdebug && \
  echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
  echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
  echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
  echo "xdebug.remote_host=$REMOTE_HOST" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

elif [ -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ]; then
  sed -i '/PHP_IDE_CONFIG/d' /etc/cron.d/laravel-scheduler
  rm -rf /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
fi

exec "$@"
