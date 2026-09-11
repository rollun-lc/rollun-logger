<?php

namespace Rollun\Test\Logger\Writer;

use PHPUnit\Framework\TestCase;
use Laminas\Stdlib\ErrorHandler;
use rollun\logger\Exception\RuntimeException;
use rollun\logger\Formatter\FormatterInterface;
use rollun\logger\Writer\Stream;

/**
 * Descriptor hygiene of the Stream writer (wkdmZFx5): a process spawned by the logging process must not
 * inherit the writer's descriptors. Tests that inspect /proc are skipped where it is unavailable.
 */
class StreamTest extends TestCase
{
    /** @var string[] */
    private $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    public function testStdoutIsNotHeldBetweenWrites(): void
    {
        $writer = new Stream('php://stdout');

        $this->assertNull($this->streamOf($writer));
    }

    /**
     * @dataProvider reopenedUrls
     */
    public function testChildDoesNotInheritStdoutDup(string $url): void
    {
        $this->requireProc();

        $writer = new Stream($url, null, '');
        $writer->setFormatter($this->formatter());
        // An empty line still opens (and must close) the descriptor, without polluting the runner's output.
        $writer->write(['message' => '', 'priority' => 6, 'timestamp' => 0, 'extra' => []]);
        // The writer has just written; a process spawned now must see only its own 0/1/2.
        $childEntries = $this->spawnAndListPipes();

        $this->assertNotContains(
            readlink('/proc/self/fd/1'),
            $childEntries,
            "child inherited a dup of stdout from writer on $url: " . implode(', ', $childEntries)
        );
        $this->assertNotContains(readlink('/proc/self/fd/2'), $childEntries);
    }

    public function reopenedUrls(): array
    {
        return [
            ['php://stdout'],
            ['php://stderr'],
            ['PHP://STDOUT'],
            ['php://fd/1'],
        ];
    }

    public function testReopenedDescriptorReceivesEveryLine(): void
    {
        $this->requireProc();
        $file = $this->tempFile();
        $handle = fopen($file, 'w');
        $fd = $this->fdNumberOf($handle);

        $writer = new Stream("php://fd/$fd");
        $writer->setFormatter($this->formatter());
        $writer->write(['message' => 'first', 'priority' => 6, 'timestamp' => 0, 'extra' => []]);
        $writer->write(['message' => 'second', 'priority' => 6, 'timestamp' => 0, 'extra' => []]);
        $writer->shutdown();
        fclose($handle);

        $this->assertNull($this->streamOf($writer));
        $this->assertSame("first\nsecond\n", file_get_contents($file));
    }

    public function testPersistentOptionKeepsStdoutOpen(): void
    {
        $writer = new Stream(['stream' => 'php://stdout', 'persistent' => true]);

        $this->assertIsResource($this->streamOf($writer));
    }

    public function testPlainFileIsHeldOpenWithCloseOnExec(): void
    {
        $this->requireProc();
        $file = $this->tempFile();

        $writer = new Stream($file);
        $stream = $this->streamOf($writer);

        $this->assertIsResource($stream);
        $this->assertStringContainsString('e', stream_get_meta_data($stream)['mode']);
        $this->assertNotContains(realpath($file), $this->spawnAndListPipes(), 'child inherited the log file');
    }

    public function testExplicitModeGetsCloseOnExecAppended(): void
    {
        $file = $this->tempFile();

        $writer = new Stream($file, 'w');

        $this->assertSame('we', stream_get_meta_data($this->streamOf($writer))['mode']);
    }

    public function testModeAlreadyContainingFlagIsNotDuplicated(): void
    {
        $file = $this->tempFile();

        $writer = new Stream($file, 'ae');

        $this->assertSame('ae', stream_get_meta_data($this->streamOf($writer))['mode']);
    }

    public function testMemoryStreamIsUnchanged(): void
    {
        $writer = new Stream('php://memory', 'w+');
        $writer->setFormatter($this->formatter());
        $writer->write(['message' => 'probe', 'priority' => 6, 'timestamp' => 0, 'extra' => []]);

        $stream = $this->streamOf($writer);
        $this->assertIsResource($stream);
        rewind($stream);
        $this->assertSame("probe\n", stream_get_contents($stream));
    }

    public function testPlainFileStillReceivesLines(): void
    {
        $file = $this->tempFile();
        $writer = new Stream($file);
        $writer->setFormatter($this->formatter());

        $writer->write(['message' => 'probe', 'priority' => 6, 'timestamp' => 0, 'extra' => []]);
        $writer->shutdown();

        $this->assertSame("probe\n", file_get_contents($file));
    }

    public function testCallerResourceIsUsedAsIs(): void
    {
        $resource = fopen('php://memory', 'w+');

        $writer = new Stream($resource);

        $this->assertSame($resource, $this->streamOf($writer));
    }

    public function testUnopenableUrlFailsInConstructor(): void
    {
        $this->expectException(RuntimeException::class);

        new Stream('/nonexistent-dir-' . uniqid() . '/log');
    }

    public function testFailedReopenThrowsRuntimeExceptionAndLeavesErrorHandlerStopped(): void
    {
        $writer = new class ('php://stdout') extends Stream {
            public function breakUrl(): void
            {
                $this->reopenUrl = 'php://fd/999999';
            }
        };
        $writer->setFormatter($this->formatter());
        $writer->breakUrl();

        try {
            $writer->write(['message' => 'probe', 'priority' => 6, 'timestamp' => 0, 'extra' => []]);
            $this->fail('expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('cannot be opened', $e->getMessage());
            $this->assertInstanceOf(\ErrorException::class, $e->getPrevious(), 'fopen warning must be kept as previous');
        }

        $this->assertFalse(ErrorHandler::started(), 'ErrorHandler leaked after a failed write');
    }

    private function formatter(): FormatterInterface
    {
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->method('format')->willReturnCallback(fn(array $event) => $event['message']);

        return $formatter;
    }

    /** @return resource|null */
    private function streamOf(Stream $writer)
    {
        $property = new \ReflectionProperty(Stream::class, 'stream');
        $property->setAccessible(true);

        return $property->getValue($writer);
    }

    private function tempFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'stream-writer-');
        $this->tempFiles[] = $file;

        return $file;
    }

    /** @param resource $handle */
    private function fdNumberOf($handle): int
    {
        $target = realpath(stream_get_meta_data($handle)['uri']);
        foreach (scandir('/proc/self/fd') as $fd) {
            if (is_numeric($fd) && @readlink("/proc/self/fd/$fd") === $target) {
                return (int) $fd;
            }
        }
        $this->fail("descriptor of $target not found in /proc/self/fd");
    }

    private function requireProc(): void
    {
        if (! is_dir('/proc/self/fd') || ! function_exists('shell_exec')) {
            $this->markTestSkipped('/proc/self/fd and shell_exec are required');
        }
    }

    /**
     * Spawns a child the way application code does (shell_exec with 0/1/2 redirected) and returns the
     * targets of every descriptor above 2 that the child inherited.
     *
     * @return string[]
     */
    private function spawnAndListPipes(): array
    {
        // Sentinel: a handle deliberately opened without close-on-exec must show up in the child's table,
        // otherwise the listing is broken and every "not inherited" assertion would pass vacuously.
        $sentinelFile = $this->tempFile();
        $sentinel = fopen($sentinelFile, 'a');

        $output = $this->tempFile();
        shell_exec(sprintf(
            'sh -c %s 1>/dev/null 2>/dev/null',
            escapeshellarg('for f in /proc/self/fd/*; do n=${f##*/}; [ "$n" -gt 2 ] && readlink "$f"; done > ' . escapeshellarg($output))
        ));
        fclose($sentinel);

        $entries = array_filter(array_map('trim', file($output)));
        $this->assertContains(realpath($sentinelFile), $entries, 'child descriptor listing is broken');

        // the shell's own descriptor on /proc/self/fd (the dir handle), the output file and the sentinel are expected
        return array_values(array_filter(
            $entries,
            fn(string $target) => !str_contains($target, '/proc/')
                && $target !== realpath($output)
                && $target !== realpath($sentinelFile)
        ));
    }
}
