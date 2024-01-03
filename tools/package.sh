#!/usr/bin/env bash

php -d phar.readonly=Off /usr/local/bin/phar-composer build . dist
mv ./dist/php-git-ops.phar ./dist/php-git-ops
chmod +x ./dist/php-git-ops
