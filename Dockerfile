FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql && rm -rf /var/lib/apt/lists/*
# Only frontend/ is web-visible; backend/ stays outside the document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/frontend
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf
COPY backend /var/www/html/backend
COPY frontend /var/www/html/frontend
