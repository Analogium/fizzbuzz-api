# Pour lancer le projet sans installer PHP/Composer. Usage : voir README.
FROM php:8.4-cli

# unzip : Composer en a besoin pour extraire les dépendances.
RUN apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-interaction --no-progress

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
