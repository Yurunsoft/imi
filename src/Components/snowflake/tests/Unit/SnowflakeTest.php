<?php

declare(strict_types=1);

namespace Imi\Snowflake\Test;

use Imi\Snowflake\SnowflakeUtil;
use PHPUnit\Framework\TestCase;

class SnowflakeTest extends TestCase
{
    public function testBasic(): void
    {
        $this->assertTrue(!empty(SnowflakeUtil::id('testBasic')));
        $this->assertTrue(\strlen(SnowflakeUtil::id('testBasic')) <= 19);
    }

    public function testBasicByPredis(): void
    {
        $this->assertTrue(!empty(SnowflakeUtil::id('testBasicByPredis')));
        $this->assertTrue(\strlen(SnowflakeUtil::id('testBasicByPredis')) <= 19);
    }

    public function testWorkIdAndDataCenterId(): void
    {
        $this->assertTrue(!empty(SnowflakeUtil::id('test1')));
        $this->assertTrue(\strlen(SnowflakeUtil::id('test1')) <= 19);

        $this->assertTrue(!empty(SnowflakeUtil::id('test2')));
        $this->assertTrue(\strlen(SnowflakeUtil::id('test2')) <= 19);

        $this->assertTrue(!empty(SnowflakeUtil::id('test3')));
        $this->assertTrue(\strlen($id = SnowflakeUtil::id('test3')) <= 19);

        $this->assertEquals(1, SnowflakeUtil::parseId('test3', $id, true)['datacenter']);
        $this->assertEquals(2, SnowflakeUtil::parseId('test3', $id, true)['workerid']);

        $id = SnowflakeUtil::id('test4');

        $this->assertNotEquals(999, SnowflakeUtil::parseId('test4', $id, true)['datacenter']);
        $this->assertEquals(20, SnowflakeUtil::parseId('test4', $id, true)['workerid']);
    }

    public function testBatch(): void
    {
        $datas = [];

        for ($i = 0; $i < 10000; ++$i)
        {
            $id = SnowflakeUtil::id('testBatch');

            $datas[$id] = 1;
        }

        $this->assertCount(10000, $datas);
    }

    public function testParseId(): void
    {
        $data = SnowflakeUtil::parseId('testParseId', '1537200202186752');

        $this->assertSame($data['workerid'], '00000');
        $this->assertSame($data['datacenter'], '00000');
        $this->assertSame($data['sequence'], '000000000000');

        $data = SnowflakeUtil::parseId('testParseId', '1537200202186752', true);

        $this->assertEquals(0, $data['workerid']);
        $this->assertEquals(0, $data['datacenter']);
        $this->assertEquals(0, $data['sequence']);
    }
}
