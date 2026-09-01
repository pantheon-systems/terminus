# --- Composer Layer ---
FROM composer:2 AS composer

# --- Build Layer ---
FROM php:8.4-cli-alpine AS build

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
RUN wget https://github.com/box-project/box/releases/download/4.6.7/box.phar -O /usr/local/bin/box && chmod +x /usr/local/bin/box

# Build PHAR file
RUN ./scripts/phar_build.sh

# --- Runtime Layer ---
FROM php:8.4-cli-alpine

# Install Git, SSH, and Unzip dependencies
RUN apk add --no-cache git openssh unzip

# Create a non-root user and group. UID/GID 1000 matches the typical first
# local user, so a bind-mounted ~/.terminus stays writable inside the container.
RUN addgroup -g 1000 terminus \
 && adduser -u 1000 -G terminus -h /home/terminus -D terminus

# Terminus reads its configuration, plugins, and cache from $HOME/.terminus.
ENV HOME=/home/terminus
RUN mkdir -p /home/terminus/.terminus \
 && chown -R terminus:terminus /home/terminus

# Copy Composer from build layer
COPY --from=build /usr/bin/composer /usr/bin/composer

# Copy built PHAR from build layer
COPY --from=build /app/terminus.phar /app/terminus.phar

# Switch to the non-root user
USER terminus

WORKDIR /home/terminus

# Set entrypoint to run the PHAR
ENTRYPOINT ["/app/terminus.phar"]
