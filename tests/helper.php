<?php

declare(strict_types=1);

use function Imi\env;

function array_column_ex(array $arr, array $column, ?string $key = null): array
{
    $result = array_map(static function ($val) use ($column) {
        $item = [];
        foreach ($column as $index => $key)
        {
            if (\is_int($index))
            {
                $item[$key] = $val[$key];
            }
            else
            {
                $item[$key] = $val[$index];
            }
        }

        return $item;
    }, $arr);

    if (!empty($key))
    {
        $result = array_combine(array_column($arr, $key), $result);
    }

    return $result;
}

function envs_is_ready(array $envs, string &$failEnv = null): bool
{
    foreach ($envs as $env)
    {
        $value = env($env);
        if (empty($value))
        {
            $failEnv = $env;

            return false;
        }
    }

    return true;
}
