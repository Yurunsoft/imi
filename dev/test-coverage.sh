#!/bin/bash

test() {
    local name=$1
    local cmd=$2
    echo "test $name...";
    echo $cmd
    time $cmd
    code=$?
    if [ $code != 0 ]; then
        exit $code
    fi
}

__DIR__=$(cd `dirname $0`; pwd) && \

cd $__DIR__/../ && \

rm -rf dev/cover/*

export IMI_CODE_COVERAGE=1

if [ "$IMI_CODE_COVERAGE" = 1 ]; then
    php --ri xdebug > /dev/null
    if [ $? = 0 ]; then
        paramsXdebug=""
    else
        php -dzend_extension=xdebug --ri xdebug > /dev/null 2>&1
        if [ $? = 0 ]; then
            paramsXdebug="-dzend_extension=xdebug"
        fi
    fi
    paramsXdebug="$paramsXdebug -dxdebug.mode=coverage"
fi

# core test
test "core" "php $paramsXdebug -dapc.enable_cli=1 vendor/bin/phpunit -c ./tests/phpunit.xml --coverage-php=./dev/cover/core-coverage.php -v"

phpUnitCommands=(
    "workerman"
    "roadrunner"
    "fpm"
    "jwt"
    "snowflake"
)

swoolePhpUnitCommands=(
    "swoole"
    "queue"
    "grpc"
    "mqtt"
    "smarty"
    "pgsql"
)

for name in "${phpUnitCommands[@]}"
do
    cmd="php $paramsXdebug src/Components/$name/vendor/bin/phpunit -c ./src/Components/$name/tests/phpunit.xml --coverage-php=./dev/cover/$name-coverage.php -v"
    test "$name" "$cmd"
done

for name in "${swoolePhpUnitCommands[@]}"
do
    cmd="php $paramsXdebug src/Components/swoole/bin/swoole-phpunit -c ./src/Components/$name/tests/phpunit.xml --coverage-php=./dev/cover/$name-coverage.php -v"
    test "$name" "$cmd"
done

export AMQP_TEST_MODE=swoole
test "amqp-swoole" "php $paramsXdebug src/Components/swoole/bin/swoole-phpunit -c ./src/Components/amqp/tests/phpunit.xml --coverage-php=./dev/cover/amqp-swoole-coverage.php -v"

export AMQP_TEST_MODE=workerman
test "amqp-workerman" "php $paramsXdebug src/Components/swoole/bin/swoole-phpunit -c ./src/Components/amqp/tests/phpunit.xml --coverage-php=./dev/cover/amqp-workerman-coverage.php -v"

export KAFKA_TEST_MODE=swoole
test "kafka-swoole" "php $paramsXdebug src/Components/swoole/bin/swoole-phpunit -c ./src/Components/kafka/tests/phpunit.xml --coverage-php=./dev/cover/kafka-swoole-coverage.php -v"

export KAFKA_TEST_MODE=workerman
test "kafka-workerman" "php $paramsXdebug src/Components/swoole/bin/swoole-phpunit -c ./src/Components/kafka/tests/phpunit.xml --coverage-php=./dev/cover/kafka-workerman-coverage.php -v"

test "workerman-gateway-workerman" "php $paramsXdebug src/Components/workerman-gateway/vendor/bin/phpunit -c ./src/Components/workerman-gateway/tests/phpunit.xml --testsuite workerman --coverage-php=./dev/cover/workerman-gateway-coverage.php -v"

test "workerman-gateway-swoole" "php $paramsXdebug src/Components/workerman-gateway/vendor/bin/phpunit -c ./src/Components/workerman-gateway/tests/phpunit.xml --testsuite swoole --coverage-php=./dev/cover/workerman-gateway-swoole-coverage.php -v"

php dev/merge-coverage.php $1
