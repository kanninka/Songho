# Utilise une image officielle PHP avec Apache
FROM php:8.2-apache

# Active le module de réécriture pour Apache (utile pour les routes web)
RUN a2enmod rewrite

# Copie tous tes fichiers de ton dépôt dans le dossier du serveur
COPY . /var/www/html/

# Donne les droits au serveur web pour qu'il puisse lire tes fichiers
RUN chown -R www-data:www-data /var/www/html

# Expose le port 80 pour que ton site soit visible
EXPOSE 80
