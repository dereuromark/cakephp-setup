language: php

sudo: false

php:
  - 5.6
  - 7.3

env:
  global:
    - DEFAULT=1

matrix:
  fast_finish: true

  include:
    - php: 7.2
      env: CHECKS=1 DEFAULT=0

    - php: 7.2
      env: CODECOVERAGE=1 DEFAULT=0

before_script:
  - if [[ $PREFER_LOWEST != 1 ]]; then composer install --prefer-source --no-interaction; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then composer update --prefer-lowest --prefer-dist --prefer-stable --no-interaction; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then composer require --dev dereuromark/composer-prefer-lowest:dev-master; fi

  - if [[ $CHECKS != 1 ]]; then composer require --dev phpunit/phpunit:"^5.7.14|^6.0"; fi

  - phpenv rehash
  - set +H
  - cp phpunit.xml.dist phpunit.xml

script:
  - if [[ $DEFAULT == 1 ]]; then vendor/bin/phpunit; fi
  - if [[ $PREFER_LOWEST == 1 ]]; then vendor/bin/validate-prefer-lowest; fi

  - if [[ $CHECKS == 1 ]]; then composer cs-check; fi

  - if [[ $CODECOVERAGE == 1 ]]; then vendor/bin/phpunit --coverage-clover=clover.xml || true; fi
  - if [[ $CODECOVERAGE == 1 ]]; then wget -O codecov.sh https://codecov.io/bash; fi
  - if [[ $CODECOVERAGE == 1 ]]; then bash codecov.sh; fi

notifications:
  email: false
