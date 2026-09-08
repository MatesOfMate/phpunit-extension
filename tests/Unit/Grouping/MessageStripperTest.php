<?php

/*
 * This file is part of the MatesOfMate Organisation.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MatesOfMate\PHPUnitExtension\Tests\Unit\Grouping;

use MatesOfMate\PHPUnitExtension\Grouping\MessageStripper;
use PHPUnit\Framework\TestCase;

/**
 * @author Johannes Wachter <johannes@sulu.io>
 */
class MessageStripperTest extends TestCase
{
    private MessageStripper $stripper;

    protected function setUp(): void
    {
        $this->stripper = new MessageStripper();
    }

    public function testTruncateLeavesAShortMessageAlone(): void
    {
        $message = 'Failed asserting that false is true.';

        $this->assertSame($message, $this->stripper->truncate($message, 800));
    }

    public function testTruncateCutsALongMessageWithAMarker(): void
    {
        $truncated = $this->stripper->truncate(str_repeat('x', 1000), 800);

        $this->assertSame(800, \strlen($truncated));
        $this->assertStringEndsWith('...', $truncated);
    }

    public function testAShortMessageIsLeftAlone(): void
    {
        $message = 'Failed asserting that false is true.';

        $this->assertSame($message, $this->stripper->strip($message));
    }

    /**
     * A byte cut keeps the top of a diff, which is unchanged context, and drops
     * the changed lines below it. Stripping keeps the changed lines, which are
     * the only part that says what is wrong.
     */
    public function testTheChangedLinesSurviveWhereAByteCutWouldLoseThem(): void
    {
        $message = "Failed asserting that two arrays are identical.\n"
            ."--- Expected\n+++ Actual\n@@ @@\n"
            .str_repeat("     'padding' => 'unchanged context line',\n", 40)
            ."-    'subtotal' => 4841.2,\n"
            ."+    'subtotal' => 5096,\n";

        $stripped = $this->stripper->strip($message);

        $this->assertStringContainsString("-    'subtotal' => 4841.2,", $stripped);
        $this->assertStringContainsString("+    'subtotal' => 5096,", $stripped);
        $this->assertLessThan(\strlen($message), \strlen($stripped));
        $this->assertStringNotContainsString('subtotal', substr($message, 0, 200));
    }

    public function testWhatWasRemovedIsDeclared(): void
    {
        $message = "Failed asserting that two arrays are identical.\n"
            ."--- Expected\n+++ Actual\n"
            .str_repeat("     'x' => 'y',\n", 10)
            ."-    'a' => 1,\n";

        $this->assertStringContainsString('unchanged diff lines', $this->stripper->strip($message));
    }

    public function testContextImmediatelyBeforeAChangeIsKept(): void
    {
        $message = "Failed asserting that two arrays are identical.\n"
            ."     'first' => 1,\n"
            ."     'second' => 2,\n"
            ."-    'third' => 3,\n";

        $stripped = $this->stripper->strip($message);

        $this->assertStringContainsString("'second' => 2,", $stripped);
        $this->assertStringContainsString("-    'third' => 3,", $stripped);
    }

    /**
     * Indentation alone does not make a line diff context. A message with no
     * diff markers anywhere (an indented SQL statement inside an exception,
     * a var_export dump) must survive intact, not get replaced by a
     * "[stripped: N unchanged diff lines]" note that claims a safe removal
     * that never happened.
     */
    public function testIndentedContentWithNoDiffIsNotTreatedAsContext(): void
    {
        $message = "Doctrine\\DBAL\\Exception\\SyntaxErrorException: An exception occurred while executing:\n"
            ."    SELECT * FROM invoice WHERE id = ?\n"
            ."    with params [17]\n"
            .'/app/src/Repository/InvoiceRepository.php:88';

        $stripped = $this->stripper->strip($message);

        $this->assertStringContainsString('SELECT * FROM invoice WHERE id = ?', $stripped);
        $this->assertStringContainsString('with params [17]', $stripped);
        $this->assertStringNotContainsString('unchanged diff lines', $stripped);
    }

    /**
     * A message that does contain a real diff further down still gets its
     * indented context dropped as before; the fix only concerns messages with
     * no diff markers at all.
     */
    public function testIndentedContentBeforeARealDiffIsStillTreatedAsContext(): void
    {
        $message = "Failed asserting that two arrays are identical.\n"
            ."--- Expected\n+++ Actual\n@@ @@\n"
            .str_repeat("     'padding' => 'unchanged context line',\n", 10)
            ."-    'subtotal' => 1,\n"
            ."+    'subtotal' => 2,\n";

        $stripped = $this->stripper->strip($message);

        $this->assertStringContainsString('unchanged diff lines', $stripped);
    }

    /**
     * The vendor-frame pattern contains a '#'. Delimited with '#' it does not
     * compile, and preg_match then warns and returns false, which looks exactly
     * like "there were no vendor frames".
     */
    public function testTheVendorFramePatternCompiles(): void
    {
        $errors = [];
        set_error_handler(static function (int $number, string $message) use (&$errors): bool {
            $errors[] = $message;

            return true;
        });

        try {
            $stripped = $this->stripper->strip(
                "Failed asserting that false is true.\n"
                ."#0 /app/vendor/phpunit/phpunit/src/Framework/TestCase.php(1234): App\\Foo->bar()\n"
                .'/app/src/Foo.php:12'
            );
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors);
        $this->assertStringNotContainsString('vendor/phpunit', $stripped);
        $this->assertStringContainsString('/app/src/Foo.php:12', $stripped);
        $this->assertStringContainsString('vendor stack frames', $stripped);
    }
}
