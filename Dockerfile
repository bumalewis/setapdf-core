# Use an existing PHP 8.0 image as base
FROM php:8.0-apache

#USER root

RUN apt-get update
RUN docker-php-ext-install mysqli

# Add your SSH private key to the container
ARG SSH_PRIVATE_KEY
RUN mkdir -p /root/.ssh && \
    echo "$SSH_PRIVATE_KEY" > /root/.ssh/id_rsa && \
    chmod 600 /root/.ssh/id_rsa

# Start the ssh-agent and add the SSH key
RUN eval "$(ssh-agent -s)" && \
    ssh-add /root/.ssh/id_rsa

    
## Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN  composer config --global http-basic.www.setasign.com $(SETASIGN_USER) $(SETASIGN_PASSWORD)

## Install PHP-GD
RUN apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install gd

## Install xdebug
RUN apt-get install --assume-yes --fix-missing git libzip-dev libmcrypt-dev openssh-client \
    libxml2-dev libpng-dev g++ make autoconf \
    && docker-php-source extract \
    && pecl install xdebug redis \
    && docker-php-ext-enable xdebug redis \
    && docker-php-source delete \
    && docker-php-ext-install pdo_mysql soap intl zip

## Configure xdebug
RUN echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=wolfpackvision.com" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

## Enable mod_rewrite http://httpd.apache.org/docs/current/mod/mod_rewrite.html & mod_headers http://httpd.apache.org/docs/current/mod/mod_headers.html
RUN a2enmod rewrite \
    && a2enmod headers

## Give Full folder permissions to server
#RUN chown -R www-data:www-data /var/www/html
#RUN chmod -R 777 /var/www/html/
#RUN chmod -R 777 /var/www/html/wp-content/uploads/
#RUN chmod -R 777 /var/www/html/
#RUN chmod -R 766 /var/www/html/

## Clone the Git repository

## Cleanup
RUN rm -rf /tmp/*

# Expose port 80
EXPOSE 80

# Start Apache web server
CMD ["apache2ctl", "-D", "FOREGROUND"]
