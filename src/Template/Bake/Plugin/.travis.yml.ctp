language: php

sudo: false

php:
  - 5.6
  - 7.0
  - 7.1
  - 7.2

env:
  global:
    - DEFAULT=1

matrix:
  fast_finish: true

  include:
    - php: 7.1
      env: PHPCS=1 DEFAULT=0

    - php: 7.1
      env: CODECOVERAGE=1 DEFAULT=0

before_script:
  - composer install --prefer-source --no-interaction

  - if [[ $PHPCS != 1 ]]; then composer require phpunit/phpunit:"^5.0|^6.0"; fi

  - phpenv rehash
  - set +H
  - cp phpunit.xml.dist phpunit.xml

script:
  - if [[ $DEFAULT == 1 ]]; then vendor/bin/phpunit ; fi
  - if [[ $PHPCS == 1 ]]; then composer cs-check ; fi

  - if [[ $CODECOVERAGE == 1 ]]; then vendor/bin/phpunit --coverage-clover=clover.xml || true; fi
  - if [[ $CODECOVERAGE == 1 ]]; then wget -O codecov.sh https://codecov.io/bash; fi
  - if [[ $CODECOVERAGE == 1 ]]; then bash codecov.sh; fi

notifications:
  email: false
