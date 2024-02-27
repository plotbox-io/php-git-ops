FROM php:8.0-cli AS base

## Add extra tools
# @see https://docs.docker.com/develop/develop-images/dockerfile_best-practices/#apt-get
RUN apt-get update && apt-get install -y \
    && apt-get install -y iputils-ping \
    && apt-get install -y iproute2 \
    && apt-get install -y curl \
    && apt-get install -y git \
    && apt-get install -y wget \
    && apt-get install -y nano \
    && apt-get install -y zip

# Get phar-composer bundler
RUN wget https://github.com/clue/phar-composer/releases/download/v1.4.0/phar-composer-1.4.0.phar \
    && chmod +x phar-composer-1.4.0.phar \
    && mv phar-composer-1.4.0.phar /usr/local/bin/phar-composer

# Get Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

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

# Ignore 'StrictHostKeyChecking' for SSH to bitbucket.org to ensure requests that need
# to use git can be done non-interactively
RUN mkdir /root/.ssh \
    && chmod 700 /root/.ssh \
    && mkdir /home/application/.ssh \
    && chmod 700 /home/application/.ssh
COPY ./.docker/ssh-config /root/.ssh/config
COPY ./.docker/ssh-config /home/application/.ssh/config
RUN chmod 400 /root/.ssh/config \
    && chmod 400 /home/application/.ssh/config \
    && chown -R application:application /home/application/.ssh

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY ./.docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /app

FROM base as prod

COPY --chown=application:application . /app
