<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Capability;

use MatesOfMate\Common\Cache\RunCache;
use MatesOfMate\PHPUnitExtension\Capability\RunDetailTool;
use MatesOfMate\PHPUnitExtension\Grouping\FailureGrouper;
use MatesOfMate\PHPUnitExtension\Grouping\MessageStripper;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Mate\Encoding\ResponseEncoder;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class RunDetailToolTest extends TestCase
{
    private string $cacheDir;
    private RunCache $cache;
    private RunDetailTool $tool;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/phpunit-detail-'.bin2hex(random_bytes(4));
        $this->cache = new RunCache($this->cacheDir, 'phpunit-runs', 20);
        $this->tool = new RunDetailTool($this->cache, new MessageStripper());
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir.'/*/*') ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob($this->cacheDir.'/*') ?: [] as $dir) {
            @rmdir($dir);
        }
        @rmdir($this->cacheDir);
    }

    public function testAnUnknownRunIdReportsWhatIsAvailable(): void
    {
        $this->cache->store(['groups' => []]);

        $decoded = $this->decode($this->tool->execute('nope'));

        $this->assertStringContainsString('Unknown run id', (string) $decoded['error']);
        $this->assertStringContainsString('Cached runs', (string) $decoded['hint']);
    }

    public function testAnEmptyCacheSaysSoRatherThanListingNothing(): void
    {
        $decoded = $this->decode($this->tool->execute('nope'));

        $this->assertStringContainsString('Run phpunit-run first', (string) $decoded['hint']);
    }

    public function testOneGroupCanBeFetched(): void
    {
        $id = $this->storeRun();

        $decoded = $this->decode($this->tool->execute($id, group: 'g2'));

        $this->assertCount(1, $decoded['entries']);
        $this->assertSame('g2', $decoded['entries'][0]['group']);
    }

    public function testOneTestCanBeFetchedByName(): void
    {
        $id = $this->storeRun();

        $decoded = $this->decode($this->tool->execute($id, test: 'testThree'));

        $this->assertCount(1, $decoded['entries']);
        $this->assertSame('InvoiceTest::testThree', $decoded['entries'][0]['test']);
    }

    public function testEverythingComesBackWhenNothingIsNarrowed(): void
    {
        $decoded = $this->decode($this->tool->execute($this->storeRun()));

        $this->assertCount(3, $decoded['entries']);
    }

    public function testMessagesAreStrippedUnlessRawIsAskedFor(): void
    {
        $id = $this->storeRun();

        $stripped = $this->decode($this->tool->execute($id, group: 'g1'))['entries'][0]['message'];
        $raw = $this->decode($this->tool->execute($id, group: 'g1', raw: true))['entries'][0]['message'];

        $this->assertStringContainsString('unchanged diff lines', (string) $stripped);
        $this->assertStringNotContainsString('unchanged diff lines', (string) $raw);
        $this->assertLessThan(\strlen((string) $raw), \strlen((string) $stripped));
    }

    public function testALongMessageIsCappedWhenNoSingleTestIsRequested(): void
    {
        $groups = (new FailureGrouper())->group([
            $this->failure('testOne', str_repeat('x', 5000)),
        ]);
        $id = $this->cache->store(['groups' => $groups]);

        $decoded = $this->decode($this->tool->execute($id));

        $this->assertLessThan(1000, \strlen((string) $decoded['entries'][0]['message']));
    }

    /**
     * "return one test in full" is the documented contract for the test
     * argument, so the same long message must survive uncapped there.
     */
    public function testALongMessageIsReturnedInFullForOneNamedTest(): void
    {
        $long = str_repeat('x', 5000);
        $groups = (new FailureGrouper())->group([
            $this->failure('testOne', $long),
        ]);
        $id = $this->cache->store(['groups' => $groups]);

        $decoded = $this->decode($this->tool->execute($id, test: 'InvoiceTest::testOne'));

        $this->assertSame($long, $decoded['entries'][0]['message']);
    }

    public function testAGroupThatDoesNotExistIsReportedWithTheOnesThatDo(): void
    {
        $decoded = $this->decode($this->tool->execute($this->storeRun(), group: 'g99'));

        $this->assertStringContainsString('Nothing matched', (string) $decoded['error']);
        $this->assertNotEmpty($decoded['groups']);
    }

    private function storeRun(): string
    {
        $diff = "Failed asserting that two arrays are identical.\n--- Expected\n+++ Actual\n"
            .str_repeat("     'padding' => 'context',\n", 12)
            ."-    'subtotal' => 1,\n+    'subtotal' => 2,\n";

        $groups = (new FailureGrouper())->group([
            $this->failure('testOne', $diff),
            $this->failure('testTwo', $diff),
            $this->failure('testThree', 'Failed asserting that null is of type int.'),
        ]);

        return $this->cache->store(['groups' => $groups]);
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $method, string $message): array
    {
        return [
            'class' => 'App\\Tests\\InvoiceTest',
            'method' => $method,
            'type' => \PHPUnit\Framework\ExpectationFailedException::class,
            'file' => '/app/tests/InvoiceTest.php',
            'line' => 42,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $encoded): array
    {
        $decoded = ResponseEncoder::decode($encoded);
        $this->assertIsArray($decoded);

        return $decoded;
    }
}
