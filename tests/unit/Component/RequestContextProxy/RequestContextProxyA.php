<?php

declare(strict_types=1);

namespace Imi\Test\Component\RequestContextProxy;

use Imi\RequestContextProxy\Annotation\RequestContextProxy;
use Imi\RequestContextProxy\BaseRequestContextProxy;

/**
 * @RequestContextProxy(class="Imi\Test\Component\RequestContextProxy\A", name="testRequestContextProxyA")
 *
 * @method        mixed add($a, $b)
 * @method static mixed add($a, $b)
 */
class RequestContextProxyA extends BaseRequestContextProxy
{
}
