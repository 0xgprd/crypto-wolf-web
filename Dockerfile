# Landing de la lista de espera de Crypto Wolf Academy (gabrielperdomo.com).
# Apache con PHP: sirve las páginas y el intermediario del formulario (lista.php).
FROM php:8.3-apache

# Las notas internas (.md) no se publican.
COPY . /var/www/html/
RUN rm -f /var/www/html/*.md /var/www/html/Dockerfile \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
