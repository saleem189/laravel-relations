FROM php:8.2-fpm

WORKDIR /var/www/html

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Configure timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install system dependencies in separate layers for better caching
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    libpq-dev \
    libsqlite3-dev \
    libreadline-dev \
    zip \
    unzip \
    ca-certificates \
    gnupg \
    supervisor \
    mariadb-client \
    postgresql-client \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        intl \
        opcache \
        xml \
        soap \
        readline

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js 18 with proper GPG keys
ARG NODE_VERSION=18
RUN mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm pnpm bun \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /etc/apt/keyrings/yarn.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN if [ -f "composer.lock" ] || [ -f "composer.json" ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-scripts; \
    fi

# Copy package files
COPY package.json package-lock.json* ./

# Build assets (Node.js remains installed for runtime use)
RUN if [ -f "package.json" ]; then \
        if [ -f "package-lock.json" ]; then npm ci; else npm install; fi && \
        npm run production 2>/dev/null || true; \
    fi

# Copy application code
COPY . .

# Create Laravel directories and set permissions
RUN mkdir -p bootstrap/cache storage/framework/{sessions,views,cache} storage/logs && \
    chmod -R 775 storage bootstrap/cache

# Copy PHP configuration
COPY docker_custom/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker_custom/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy Supervisor configuration
COPY docker_custom/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker_custom/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

# Create non-root user
RUN groupadd --gid 1000 appgroup && \
    useradd --system --uid 1000 --gid 1000 appuser && \
    chown -R appuser:appgroup /var/www/html

# Copy entrypoint script
COPY docker_custom/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create log directories
RUN mkdir -p /var/log/php-fpm /var/log/supervisor /var/run/supervisor && \
    chown -R appuser:appgroup /var/log/php-fpm && \
    chmod -R 755 /var/log/supervisor /var/run/supervisor

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php -r "exit(0);" || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
