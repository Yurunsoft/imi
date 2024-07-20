<?php

declare(strict_types=1);

namespace Imi\Validate;

use Imi\Util\EnumUtil;

/**
 * 验证器工具类.
 */
class ValidatorHelper
{
    /**
     * 正则验证
     *
     * @param mixed $value
     */
    public static function regex($value, string $rule): bool
    {
        return preg_match($rule, (string) $value) > 0;
    }

    /**
     * 小数验证
     *
     * @param mixed $value
     */
    public static function decimal($value, ?float $min = null, ?float $max = null, ?int $accuracy = null): bool
    {
        return static::number($value, $min, $max, $accuracy) && (\is_float($value) || str_contains((string) $value, '.'));
    }

    /**
     * 整数验证
     *
     * @param mixed $value
     */
    public static function int($value, ?int $min = null, ?int $max = null): bool
    {
        // 整数验证
        if ((string) (int) $value !== (string) $value || \is_float($value))
        {
            return false;
        }
        // 最小值
        if (null !== $min && $value < $min)
        {
            return false;
        }
        // 最大值
        if (null !== $max && $value > $max)
        {
            return false;
        }

        return true;
    }

    /**
     * 数值验证，允许整数和小数.
     *
     * @param mixed          $value
     * @param float|int|null $min
     * @param float|int|null $max
     */
    public static function number($value, $min = null, $max = null, ?int $accuracy = null): bool
    {
        if (!is_numeric($value))
        {
            return false;
        }
        // 最小值
        if (null !== $min && $value < $min)
        {
            return false;
        }
        // 最大值
        if (null !== $max && $value > $max)
        {
            return false;
        }
        // 小数精度
        if (null !== $accuracy)
        {
            $value = (string) $value;

            $pointPos = strrpos($value, '.');

            return (false === $pointPos ? 0 : \strlen($value) - $pointPos - 1) <= $accuracy;
        }

        return true;
    }

    /**
     * 判断文本长度，以字节为单位.
     *
     * @param mixed $value
     */
    public static function length($value, int $min, ?int $max = null): bool
    {
        return isset($value[$min - 1]) && (null === $max || !isset($value[$max]));
    }

    /**
     * 判断文本长度，以字符为单位.
     *
     * @param mixed $value
     */
    public static function lengthChar($value, int $min, ?int $max = null): bool
    {
        $len = mb_strlen((string) $value, 'utf8');
        $result = ($len >= $min);
        if (null !== $max)
        {
            $result = ($result && $len <= $max);
        }

        return $result;
    }

    /**
     * 判断空文本.
     *
     * @param mixed $value
     */
    public static function emptyStr($value): bool
    {
        return '' === $value;
    }

    /**
     * 判断不为空文本.
     *
     * @param mixed $value
     */
    public static function notEmptyStr($value): bool
    {
        return '' !== $value;
    }

    /**
     * 检测邮箱格式.
     *
     * @param mixed $email
     */
    public static function email($email): bool
    {
        return false !== filter_var($email, \FILTER_VALIDATE_EMAIL);
    }

    /**
     * 检测中国手机号码格式.
     *
     * @param mixed $str
     */
    public static function cnMobile($str): bool
    {
        return preg_match('/1\d{10}/', (string) $str) > 0;
    }

    /**
     * 检测中国电话号码格式，支持400、800等.
     *
     * @param mixed $str
     */
    public static function tel($str): bool
    {
        return preg_match('/^(((\d{3,4}-)?(\d{7,8}){1}(-\d{2,4})?)|((\d{3,4}-)?(\d{3,4}){1}(-\d{3,4})))$/', (string) $str) > 0;
    }

    /**
     * 检测中国手机电话号码格式.
     *
     * @param mixed $str
     */
    public static function mobile($str): bool
    {
        return preg_match('/^(\\+?0?86\\-?)?1[3456789]\\d{9}$/', (string) $str) > 0;
    }

    /**
     * 检测是否符合中国固话或手机格式，支持400、800等.
     *
     * @param mixed $str
     */
    public static function phone($str): bool
    {
        return static::mobile($str) || static::tel($str);
    }

    /**
     * 检测中国邮政编码
     *
     * @param mixed $str
     */
    public static function postcode($str): bool
    {
        return preg_match('/^\d{6}$/', (string) $str) > 0;
    }

    /**
     * 检测URL地址
     *
     * @param mixed $str
     */
    public static function url($str): bool
    {
        return false !== filter_var($str, \FILTER_VALIDATE_URL);
    }

    /**
     * 检测QQ号是否符合规则.
     *
     * @param mixed $str
     */
    public static function qq($str): bool
    {
        return preg_match('/^[1-9]{1}[0-9]{4,10}$/', (string) $str) > 0;
    }

    /**
     * 判断IP地址是否符合IP的格式，ipv4或ipv6.
     *
     * @param mixed $str
     */
    public static function ip($str): bool
    {
        return static::ipv4($str) || static::ipv6($str);
    }

    /**
     * 判断IP地址是否是合法的ipv4格式.
     *
     * @param mixed $str
     */
    public static function ipv4($str): bool
    {
        return false !== filter_var($str, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4);
    }

    /**
     * 判断IP地址是否是合法的ipv6格式.
     *
     * @param mixed $str
     */
    public static function ipv6($str): bool
    {
        return false !== filter_var($str, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6);
    }

    /**
     * 在两个数之间，不包含这2个数字.
     *
     * @param float|int $value
     * @param float|int $max
     * @param float|int $min
     */
    public static function between($value, $min, $max): bool
    {
        return $value > $min && $value < $max;
    }

    /**
     * 在两个数之间，包含这2个数字.
     *
     * @param float|int $value
     * @param float|int $max
     * @param float|int $min
     */
    public static function betweenEqual($value, $min, $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * 小于.
     *
     * @param float|int $value
     * @param float|int $num
     */
    public static function lt($value, $num): bool
    {
        return $value < $num;
    }

    /**
     * 小于等于.
     *
     * @param float|int $value
     * @param float|int $num
     */
    public static function ltEqual($value, $num): bool
    {
        return $value <= $num;
    }

    /**
     * 大于.
     *
     * @param float|int $value
     * @param float|int $num
     */
    public static function gt($value, $num): bool
    {
        return $value > $num;
    }

    /**
     * 大于等于.
     *
     * @param float|int $value
     * @param float|int $num
     */
    public static function gtEqual($value, $num): bool
    {
        return $value >= $num;
    }

    /**
     * 等于.
     *
     * @param mixed $value
     * @param mixed $num
     */
    public static function equal($value, $num): bool
    {
        return $value == $num;
    }

    /**
     * 不等于.
     *
     * @param mixed $value
     * @param mixed $num
     */
    public static function unequal($value, $num): bool
    {
        return $value != $num;
    }

    /**
     * 比较.
     *
     * @param mixed $valueLeft
     * @param mixed $valueRight
     */
    public static function compare($valueLeft, string $operation, $valueRight): bool
    {
        switch ($operation)
        {
            case '==':
                return $valueLeft == $valueRight;
            case '!=':
                return $valueLeft != $valueRight;
            case '===':
                return $valueLeft === $valueRight;
            case '!==':
                return $valueLeft !== $valueRight;
            case '<':
                return $valueLeft < $valueRight;
            case '<=':
                return $valueLeft <= $valueRight;
            case '>':
                return $valueLeft > $valueRight;
            case '>=':
                return $valueLeft >= $valueRight;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupport operation %s', $operation));
        }
    }

    /**
     * 值在范围内.
     *
     * @param mixed        $value
     * @param string|array $list
     */
    public static function in($value, $list): bool
    {
        if (!\is_array($list))
        {
            $list = explode(',', $list);
        }

        return \in_array($value, $list);
    }

    /**
     * 值不在范围内.
     *
     * @param mixed        $value
     * @param string|array $list
     */
    public static function notIn($value, $list): bool
    {
        if (!\is_array($list))
        {
            $list = explode(',', $list);
        }

        return !\in_array($value, $list);
    }

    /**
     * 值在枚举值范围内.
     *
     * @param mixed $value
     */
    public static function inEnum($value, string $enumClass): bool
    {
        if (is_subclass_of($enumClass, \UnitEnum::class))
        {
            return EnumUtil::in($enumClass, $value);
        }
        else
        {
            return \in_array($value, $enumClass::getValues());
        }
    }

    /**
     * 值不在枚举值范围内.
     *
     * @param mixed $value
     */
    public static function notInEnum($value, string $enumClass): bool
    {
        return !static::inEnum($value, $enumClass);
    }

    /**
     * 检测中国居民身份证，支持15位和18位.
     *
     * @param mixed $id_card
     */
    public static function cnIdcard($id_card): bool
    {
        $id_card = (string) $id_card;
        /**
         * 计算身份证校验码，根据国家标准GB 11643-1999.
         *
         * @param string $idcard_base
         *
         * @return int|false
         */
        $idcard_verify_number = static function () use (&$id_card) {
            if (17 !== \strlen($id_card))
            {
                return false;
            }
            // 加权因子
            $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
            // 校验码对应值
            $verify_number_list = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
            $checksum = 0;
            $len = \strlen($id_card);
            for ($i = 0; $i < $len; ++$i)
            {
                $checksum += (int) $id_card[$i] * $factor[$i];
            }
            $mod = $checksum % 11;
            $verify_number = $verify_number_list[$mod];

            return $verify_number;
        };
        /**
         * 18位身份证校验码有效性检查.
         */
        $idcard_checksum18 = static function () use (&$id_card, $idcard_verify_number): bool {
            if (18 !== \strlen($id_card))
            {
                return false;
            }
            $id_card1 = $id_card;
            $id_card = substr($id_card, 0, 17);

            return $idcard_verify_number() === strtoupper($id_card1[17]);
        };
        /**
         * 将15位身份证升级到18位.
         */
        $idcard_15to18 = static function () use (&$id_card, $idcard_verify_number): string {
            if (15 !== \strlen($id_card))
            {
                return '';
            }
            else
            {
                // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
                if (\in_array(substr($id_card, 12, 3), ['996', '997', '998', '999']))
                {
                    $id_card = substr($id_card, 0, 6) . '18' . substr($id_card, 6, 9);
                }
                else
                {
                    $id_card = substr($id_card, 0, 6) . '19' . substr($id_card, 6, 9);
                }
            }
            $id_card = $id_card . $idcard_verify_number();

            return $id_card;
        };
        $len = \strlen($id_card);
        if (18 === $len)
        {
            return $idcard_checksum18();
        }
        elseif (15 === $len)
        {
            $id_card = $idcard_15to18();

            return $idcard_checksum18();
        }
        else
        {
            return false;
        }
    }

    /**
     * 文本验证
     *
     * @param mixed $str
     */
    public static function text($str, int $min, ?int $max = null, bool $char = false): bool
    {
        if ($char)
        {
            return static::lengthChar($str, $min, $max);
        }
        else
        {
            return static::length($str, $min, $max);
        }
    }
}
