<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

function assert_env_redis_is_ready(): void
{
    if (!envs_is_ready([
        'REDIS_SERVER_HOST',
        'REDIS_SERVER_PORT',
    ], $failEnvVal))
    {
        Assert::markTestSkipped("tls options {$failEnvVal} not set, skip this test");
    }
}

function assert_env_redis_cluster_is_ready(): void
{
    if (!envs_is_ready([
        'REDIS_SERVER_CLUSTER_SEEDS',
    ], $failEnvVal))
    {
        Assert::markTestSkipped("tls options {$failEnvVal} not set, skip this test");
    }
}

function assert_env_redis_unix_sock_is_ready(): void
{
    if (!envs_is_ready([
        'REDIS_SERVER_UNIX_SOCK',
    ], $failEnvVal))
    {
        Assert::markTestSkipped("unixsock options {$failEnvVal} not set, skip this test");
    }
}

function assert_env_redis_with_tls_is_ready(): void
{
    if (!envs_is_ready([
        'REDIS_SERVER_TLS_HOST',
        'REDIS_SERVER_TLS_PORT',
        'REDIS_SERVER_TLS_CA_FILE',
        'REDIS_SERVER_TLS_CERT_FILE',
        'REDIS_SERVER_TLS_KEY_FILE',
    ], $failEnvVal))
    {
        Assert::markTestSkipped("tls options {$failEnvVal} not set, skip this test");
    }
}

function assert_env_redis_cluster_with_tls_is_ready(): void
{
    if (!envs_is_ready([
        'REDIS_SERVER_TLS_CLUSTER_SEEDS',
        'REDIS_SERVER_TLS_CA_FILE',
        'REDIS_SERVER_TLS_CERT_FILE',
        'REDIS_SERVER_TLS_KEY_FILE',
    ], $failEnvVal))
    {
        Assert::markTestSkipped("cluster tls options {$failEnvVal} not set, skip this test");
    }
}
