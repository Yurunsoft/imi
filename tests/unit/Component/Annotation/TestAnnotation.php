<?php

declare(strict_types=1);

namespace Imi\Test\Component\Annotation;

use Imi\Aop\Annotation\Inject;
use Imi\Bean\Annotation\Bean;
use Imi\Enum\Annotation\EnumItem;
use Imi\Server\Http\Route\Annotation\Route;

#[Bean(name: 'TestAnnotation')]
class TestAnnotation
{
    #[EnumItem(text: 'test')]
    public const CONST_VALUE = 1;

    #[Inject(name: 'ErrorLog')]
    protected \Imi\Log\ErrorLog $errorLog;

    #[Route(url: '/testAnnotation')]
    public function test(): void
    {
    }
}
