FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
    libxml2-dev libzip-dev libonig-dev libcurl4-openssl-dev \
    unzip git curl \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN a2enmod rewrite

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath gd intl mbstring mysqli pdo pdo_mysql xml zip

WORKDIR /var/www/html
COPY . /var/www/html/

# Live subfolder path ko local root pe map karo (Apache Alias — volume mount se safe)
RUN printf 'Alias /ecwid/googlesearchconsole /var/www/html\n' \
      > /etc/apache2/conf-available/local-path-alias.conf \
    && a2enconf local-path-alias

# ../includes aur ../vendor wale relative paths ke liye (html ke bahar, mount se safe)
RUN ln -s /var/www/html/includes /var/www/includes && \
    ln -s /var/www/html/vendor /var/www/vendor

RUN if [ -f composer.json ]; then \
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
        composer install --no-interaction --prefer-dist --optimize-autoloader; \
    fi

RUN chown -R www-data:www-data /var/www/html
EXPOSE 80