<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Logger\Processor;

use PHPUnit\Framework\TestCase;
use rollun\logger\Processor\IdMaker;

class IdMakerTest extends TestCase
{
    public function testAddId()
    {
        $processor = new IdMaker();

        $event = [
            'timestamp' => '',
            'priority' => 1,
            'level' => 'ALERT',
            'message' => 'foo',
            'context' => [],
        ];

        $event = $processor->process($event);
        $this->assertArrayHasKey('id', $event);

        $id = $event['id'];
        $logsTime = explode('.', $id)[0]; //"1512736194"
        $timeInterval = time() - date('Z') - $logsTime;
        $this->assertTrue($timeInterval <= 1);
    }

    public function testNotAddId()
    {
        $processor = new IdMaker();

        $event = [
            'timestamp' => '',
            'priority' => 1,
            'level' => 'ALERT',
            'message' => 'foo',
            'context' => ['foo' => 'bar'],
            'id' => '1512570082.960175_VFSOODML',
        ];

        $event = $processor->process($event);

        $this->assertArrayHasKey('id', $event);
        $this->assertEquals('1512570082.960175_VFSOODML', $event['id']);
    }
}
