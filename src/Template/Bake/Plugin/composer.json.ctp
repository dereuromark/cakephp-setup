<%
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
$namespace = str_replace('\\', '\\\\', $namespace);
%>
{
    "name": "<%= $package %>",
    "description": "<%= $plugin %> plugin for CakePHP",
    "type": "cakephp-plugin",
    "require": {
        "php": ">=5.6.0",
        "cakephp/cakephp": "^3.4"
    },
    "require-dev": {
        "phpunit/phpunit": "*",
        "fig-r/psr2r-sniffer": "dev-master"
    },
    "autoload": {
        "psr-4": {
            "<%= $namespace %>\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "<%= $namespace %>\\Test\\": "tests/"
        }
    },
    "scripts": {
        "test": "php phpunit.phar",
        "test-setup": "[ ! -f phpunit.phar ] && wget https://phar.phpunit.de/phpunit-5.7.20.phar && mv phpunit-5.7.20.phar phpunit.phar || true",
        "cs-check": "phpcs -p -v --standard=vendor/fig-r/psr2r-sniffer/PSR2R/ruleset.xml --extensions=php src tests",
        "cs-fix": "phpcbf -v --standard=vendor/fig-r/psr2r-sniffer/PSR2R/ruleset.xml src tests"
    }
}
