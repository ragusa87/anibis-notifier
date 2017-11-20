FROM php:7.1-cli

# install php+composer dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libxml2-dev \
    zlib1g-dev \
    git

# install php extensions
RUN docker-php-ext-install iconv mcrypt xml zip && docker-php-ext-enable mcrypt iconv xml zip

# install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
php composer-setup.php --install-dir=/usr/bin --filename=composer --version=1.5.2 && \
php -r "unlink('composer-setup.php');"

# run composer install
COPY composer.json /app/current/
COPY composer.lock /app/current/

WORKDIR /app/current
RUN /usr/bin/composer install --no-scripts --no-plugins
RUN mkdir -p var/cache && chmod -R 777 var/cache
COPY parameters.yml.dist  /app/current/parameters.yml

# copy source & run
COPY . /app/current

EXPOSE 80
CMD [ "php", "-S","0.0.0.0:80", "./web/app.php" ]