ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli-alpine

# システム依存パッケージの導入
RUN apk add --no-cache \
    git \
    unzip \
    linux-headers \
    $PHPIZE_DEPS

# Xdebugのインストールと有効化
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# 不要なビルド依存を削除（軽量化）
RUN apk del --no-cache $PHPIZE_DEPS

# Composerの導入 (最新公式イメージよりコピー)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# php.ini 設定の適用
COPY docker/php.ini /usr/local/etc/php/conf.d/99-custom.ini

WORKDIR /app
