# --- Build Layer ---
FROM php:8.2-cli AS build

# Install dependencies for building PHAR
RUN apt-get update && apt-get install -y git unzip wget && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy source files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Install Box for PHAR building
RUN wget https://github.com/box-project/box/releases/download/4.5.1/box.phar -O /usr/local/bin/box && chmod +x /usr/local/bin/box

# Build PHAR file
RUN ./scripts/phar_build.sh

# --- Runtime Layer ---
FROM php:8.2-cli-alpine

WORKDIR /app

# Copy Git
RUN apk add --no-cache git unzip wget

# Copy Composer from build layer
COPY --from=build /usr/bin/composer /usr/bin/composer

# Copy built PHAR from build layer
COPY --from=build /app/terminus.phar /app/terminus.phar

# Set entrypoint to run the PHAR
ENTRYPOINT ["/app/terminus.phar"]
