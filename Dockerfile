# Base image
FROM juhasz84/ubuntu-nginx-php71-docker:latest

# Install PHP 7.1 memcached driver
RUN apt update && apt install -y php7.1-memcached php7.1-xml php7.1-zip

# Mount to www, install composer packages, and set ownership
COPY ./service	/var/www
WORKDIR /var/www
RUN php composer.phar install
RUN chown www-data:www-data -R /var/www/

# Boot
CMD ["/usr/bin/supervisord"]

# Expose ports
EXPOSE 80
