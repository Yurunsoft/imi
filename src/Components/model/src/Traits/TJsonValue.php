<?php

declare(strict_types=1);

namespace Imi\Model\Traits;

use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\JsonDecode;
use Imi\Model\Annotation\JsonEncode;
use Imi\Model\Meta;
use Imi\Util\LazyArrayObject;

trait TJsonValue
{
    protected static function parseJsonInitValue(string $name, mixed $value, Column $fieldAnnotation, Meta $meta): mixed
    {
        $fieldsJsonDecode = $meta->getPropertyAnnotations()[JsonDecode::class] ?? [];
        $realJsonDecode = $fieldsJsonDecode[$name][0] ?? $meta->getClassAnnotations()[JsonDecode::class][0] ?? false;
        /** @var JsonDecode|false $realJsonDecode */
        if ($realJsonDecode)
        {
            $data = json_decode((string) $value, $realJsonDecode->associative, $realJsonDecode->depth, $realJsonDecode->flags);
        }
        else
        {
            $data = json_decode((string) $value, true);
        }
        if (\JSON_ERROR_NONE === json_last_error())
        {
            if ($realJsonDecode)
            {
                /** @var \Imi\Model\Annotation\JsonDecode $realJsonDecode */
                $wrap = $realJsonDecode->wrap;
                if ('' !== $wrap && (\is_array($data) || \is_object($data)))
                {
                    $classExists = class_exists($wrap);
                    if ($realJsonDecode->arrayWrap)
                    {
                        $value = [];
                        foreach ($data as $key => $_value)
                        {
                            if ($classExists)
                            {
                                $value[$key] = new $wrap($_value);
                            }
                            else
                            {
                                $value[$key] = $wrap($_value);
                            }
                        }
                    }
                    else
                    {
                        if ($classExists)
                        {
                            $value = new $wrap($data);
                        }
                        else
                        {
                            $value = $wrap($data);
                        }
                    }
                }
                else
                {
                    $value = $data;
                }
            }
            elseif (\is_array($data) || \is_object($data))
            {
                $value = new LazyArrayObject($data);
            }
            else
            {
                $value = $data;
            }
        }

        return $value;
    }

    protected static function parseJsonSaveValue(string $name, mixed $value, Column $fieldAnnotation, Meta $meta): mixed
    {
        $fieldsJsonEncode = $meta->getPropertyAnnotations()[JsonEncode::class] ?? [];
        $realJsonEncode = $fieldsJsonEncode[$name][0] ?? $meta->getClassAnnotations()[JsonEncode::class][0] ?? false;
        /** @var JsonEncode|false $realJsonEncode */
        if (null === $value && $fieldAnnotation->nullable)
        {
            // 当字段允许`null`时，使用原生`null`存储
            $value = null;
        }
        elseif ($realJsonEncode)
        {
            $value = json_encode($value, $realJsonEncode->flags, $realJsonEncode->depth);
        }
        else
        {
            $value = json_encode($value, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }
}
