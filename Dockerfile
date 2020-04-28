FROM yourls:latest

# Install all the requirement so we can install php-jwt
RUN apt-get update
RUN apt-get install git -y
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /usr/src/yourls

# Install JWT
RUN ["composer", "require", "firebase/php-jwt"]
# phpFastCache is used for managing the download of JWKs
RUN ["composer", "require", "phpfastcache/phpfastcache"]
RUN ["composer", "global", "require", "phpunit/phpunit"]

# Copy our plugin directory into the image
COPY share-c3d-io-plugin /var/www/html/user/plugins/share-c3d-io-plugin

# Allow running of phpunit from any location
ENV PATH "$PATH:/root/.composer/vendor/bin"

# Set the working directory
WORKDIR /var/www/html

