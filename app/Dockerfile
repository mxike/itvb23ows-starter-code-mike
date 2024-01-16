FROM php:8.1.4-apache
WORKDIR /var/www/html
COPY ./src/ .
RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable mysqli
EXPOSE 80
