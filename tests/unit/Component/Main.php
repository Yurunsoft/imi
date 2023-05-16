<?php

declare(strict_types=1);

namespace Imi\Test\Component;

use Imi\Test\AppBaseMain;
use Imi\Util\File;
use Imi\Util\Imi;
use Yurun\Doctrine\Common\Annotations\AnnotationReader;

class Main extends AppBaseMain
{
    public function __init(): void
    {
        // 这里可以做一些初始化操作，如果需要的话
        parent::__init();
        AnnotationReader::addGlobalIgnoredName('depends');
        AnnotationReader::addGlobalIgnoredName('covers');
        $path = Imi::getRuntimePath('test');
        if (is_dir($path))
        {
            File::deleteDir($path);
        }
    }
}
