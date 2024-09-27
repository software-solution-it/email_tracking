FROM php:8.0-apache

# Instala dependências necessárias para a extensão IMAP e outras extensões PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libxml2-dev \
    libssl-dev \
    libc-client-dev \
    libkrb5-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Configura e instala as extensões PHP necessárias
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install pdo pdo_mysql imap

# Habilita o módulo rewrite do Apache
RUN a2enmod rewrite

# Instala o Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do Composer primeiro para otimizar o cache
COPY composer.json composer.lock ./

# Instala as dependências do Composer
RUN composer install --no-dev --no-scripts --no-autoloader

# Copia o restante dos arquivos da aplicação
COPY . .

# Gera os arquivos de autoload do Composer
RUN composer dump-autoload --optimize

# Define as permissões corretas para os arquivos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponha a porta 80 para acesso ao servidor web
EXPOSE 80

# Comando para iniciar o Apache
CMD ["apache2-foreground"]
