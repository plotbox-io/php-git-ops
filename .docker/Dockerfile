FROM php:8.0-cli AS main

## Add extra tools
# @see https://docs.docker.com/develop/develop-images/dockerfile_best-practices/#apt-get
RUN apt-get update && apt-get install -y \
    && apt-get install -y iputils-ping \
    && apt-get install -y iproute2 \
    && apt-get install -y curl \
    && apt-get install -y git \
    && apt-get install -y wget \
    && apt-get install -y nano

# Get phar-composer bundler
RUN wget https://github.com/clue/phar-composer/releases/download/v1.4.0/phar-composer-1.4.0.phar \
    && chmod +x phar-composer-1.4.0.phar \
    && mv phar-composer-1.4.0.phar /usr/local/bin/phar-composer

# Get Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

# Create a non-root user for bash-ing into the container and running commands
# @see https://medium.com/redbubble/running-a-docker-container-as-a-non-root-user-7d2e00f8ee15
RUN useradd --uid 1000 --create-home --shell /bin/bash application
RUN apt-get update && apt-get install -y \
  && apt-get install -y sudo
# Remove the lecture notice when using sudo for first time
RUN echo 'Defaults        lecture="never"' >> /etc/sudoers
# Add application user to sudo-ers file (without password). This is useful for some of
# the developer CLI tools which may require access to the host's docker engine
RUN passwd --delete application && usermod -aG sudo application

WORKDIR /app
