# --- Composer Layer ---
FROM composer:2 AS composer

# --- Build Layer ---
FROM php:8.3-cli-alpine AS build

# Install dependencies for building PHAR
RUN apk add --no-cache bash git wget

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

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
FROM php:8.3-cli-alpine

# Install Git, Unzip, and Wget dependencies
RUN apk add --no-cache git openssh

# Create a non-root user and group
RUN addgroup -S terminus && adduser -S terminus -G terminus

# Switch to non-root user
USER terminus

WORKDIR /app

# Copy Composer from build layer
COPY --from=build /usr/bin/composer /usr/bin/composer

# Copy built PHAR from build layer
COPY --from=build /app/terminus.phar /app/terminus.phar

# Set entrypoint to run the PHAR
ENTRYPOINT ["/app/terminus.phar"]
