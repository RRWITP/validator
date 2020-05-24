<?php

namespace CharlotteDunois\Validation;

use CharlotteDunois\Validation\Rules\Callback;
use PHPUnit\Framework\TestCase;

/**
 * Class CallbackRuleTest
 *
 * @author  Charlotte Dunois
 * @see     https://charuru.moe
 * @license https://github.com/CharlotteDunois/Validator/blob/master/LICENSE
 * @package CharlotteDunois\Validation
 */
final class CallbackRuleTest extends TestCase
{
    public function testPrototype(): void
    {
        $prototype = Callback::prototype(static function (?string $a = null): ?string {
            return $a;
        });
        $this->assertSame('?string?=?string', $prototype);
    }

    public function testPrototypeNoSignature(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Callback::prototype(static function () {});
    }

    public function testPrototypeArray(): void
    {
        $prototype = Callback::prototype(array($this, 'prototyping'));
        $this->assertSame('?string?', $prototype);
    }

    public function prototyping(?string $a = null)
    {
        //
    }
}
