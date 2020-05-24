<?php

namespace CharlotteDunois\Validation;

use CharlotteDunois\Validation\Languages\EnglishLanguage;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Class ValidatorTest
 *
 * @author  Charlotte Dunois
 * @see     https://charuru.moe
 * @licens  https://github.com/CharlotteDunois/Validator/blob/master/LICENSE
 * @package CharlotteDunois\Validation
 */
final class ValidatorTest extends TestCase
{
    /**
     * Setup the needed logic vor every test
     *
     * @return void
     */
    public function setUp(): void
    {
        Validator::setDefaultLanguage(EnglishLanguage::class);
    }

    /**
     * The logc that breakdown the needed things after every test
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($_FILES['test']);
    }

    public function testAddRule(): void
    {
        $class = (new class() implements RuleInterface {
            public function validate($value, $key, $fields, $options, $exists, Validator $validator) {
                if($value === true) {
                    return true;
                }

                return 'Given value is not boolean true';
            }
        });

        $arrname = explode('\\', get_class($class));
        $name = \strtolower(array_pop($arrname));

        Validator::addRule($class);

        $validator = Validator::make(['true-val' => true, 'other' => 'helloworld'], ['true-val' => $name, 'other' => 'string']);
        $this->assertTrue($validator->throw(LogicException::class));
    }

    public function testSetDefaultLanguage(): void
    {
        $lang = (new class() implements LanguageInterface {
            public function getTranslation(string $key, array $replacements = []): string {
                return 'ok';
            }
        });

        Validator::setDefaultLanguage(\get_class($lang));

        $validator = Validator::make(['other' => 5], ['other' => 'string']);

        $this->assertFalse($validator->passes());
        $this->assertSame(['other' => 'ok'], $validator->errors());
    }

    function testSetDefaultLanguageUnknownClass() {
        $lang = (new class() implements LanguageInterface {
            public function getTranslation(string $key, array $replacements = array()) {
                return 'ok';
            }
        });

        $this->expectException(\InvalidArgumentException::class);
        Validator::setDefaultLanguage('abc');
    }

    public function testSetDefaultLanguageInvalidClass(): void
    {
        $lang = (new class() { });

        $this->expectException(\InvalidArgumentException::class);
        Validator::setDefaultLanguage(get_class($lang));
    }

    public function testSetLanguage(): void
    {
        $lang = (new class() implements LanguageInterface {
            public function getTranslation(string $key, array $replacements = array()): string {
                return 'ok';
            }
        });

        $validator = Validator::make(['other' => 5], ['other' => 'string']);
        $vrt = $validator->setLanguage($lang);

        $this->assertSame($validator, $vrt);
        $this->assertFalse($validator->passes());
        $this->assertSame(array('other' => 'ok'), $validator->errors());
    }

    function testValidatorConstructorNoRules() {
        $fields = array(
            'other' => 'hi'
        );

        $rules = array(
            'other' => 'string'
        );

        $validator = (new class($fields, $rules, 'en') extends Validator {
            function __construct(array $fields, array $rules, string $lang) {
                static::$rulesets = null;
                static::$typeRules = array();

                parent::__construct($fields, $rules, $lang);
            }
        });

        $this->assertTrue($validator->passes());
    }

    function testThingsEmpty() {
        $fields = array();

        $rules = array(
            'justsomething' => 'activeurl|after|alphadash|array|before|between|date|dateformat|different:username|digits:5|dimensions:min_width=1280x720|file:test|float|image|ip|lowercase|mimetypes:image/*|nowhitespace|regex:/.*/i|same|size:5|uppercase|url',
            'username' => 'string|alpha|required',
            'password' => 'string|alphanum|min:6|confirmed:confirmed',
            'email' => 'email|filled',
            'read_rules' => 'present|accepted',
            'json' => 'json',
            'age' => 'integer|min:16|max:40',
            'age_string' => 'numeric|in:16,17,18,19,20',
            'deez' => 'array:integer|distinct',
            'fun' => 'function',
            'callback2' => 'callback',
            'callable' => 'callable',
            'class' => 'class:\stdClass',
            'class_object' => 'class:\\stdClass=object',
            'class_string' => 'class:\\stdClass=string',
            'class_extends' => 'class:\\PHPUnit\\Framework\\TestCase',
            'null' => 'nullable|boolean'
        );

        $validator = Validator::make($fields, $rules);

        $this->assertTrue($validator->fails());
    }

    function testAccepted() {
        $validator = Validator::make(
            array('test' => 'yes'),
            array('test' => 'accepted')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 0),
            array('test' => 'accepted')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testActiveURL() {
        $validator = Validator::make(
            array('test' => 'github.com'),
            array('test' => 'activeurl')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'failure.local'),
            array('test' => 'activeurl')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testAfter() {
        $validator = Validator::make(
            array('test' => '2010-01-02'),
            array('test' => 'after:2010-01-01')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => '2009-12-31'),
            array('test' => 'after:2010-01-01')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testAlpha() {
        $validator = Validator::make(
            array('test' => 'yes'),
            array('test' => 'alpha')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'yes-'),
            array('test' => 'alpha')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testAlpaDash() {
        $validator = Validator::make(
            array('test' => 'yes-'),
            array('test' => 'alphadash')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'yes09'),
            array('test' => 'alphadash')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testAlphaNum() {
        $validator = Validator::make(
            array('test' => 'yes5'),
            array('test' => 'alphanum')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'yes.'),
            array('test' => 'alphanum')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testArray() {
        $validator = Validator::make(
            array('test' => array()),
            array('test' => 'array')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $validator2 = Validator::make(
            array('test' => array('hi')),
            array('test' => 'array:string')
        );

        $this->assertTrue($validator2->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator3 = Validator::make(
            array('test' => null),
            array('test' => 'array')
        );

        $this->assertTrue($validator3->throw(LogicException::class));
    }

    function testArray2() {
        $this->expectException(LogicException::class);

        $validator4 = Validator::make(
            array('test' => array(5.2)),
            array('test' => 'array:bool')
        );

        $this->assertFalse($validator4->throw(LogicException::class));
    }

    function testBefore() {
        $validator = Validator::make(
            array('test' => '2009-12-31'),
            array('test' => 'before:2010-01-01')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => '2010-01-02'),
            array('test' => 'before:2010-01-01')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testBetween() {
        $validator = Validator::make(
            array('test' => 1),
            array('test' => 'between:0,2')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 3),
            array('test' => 'between:0,2')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testBoolean() {
        $validator = Validator::make(
            array('test' => true),
            array('test' => 'boolean')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => null),
            array('test' => 'boolean')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testCallable() {
        $validator = Validator::make(
            array('test' => 'var_dump'),
            array('test' => 'callable')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'callable')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testCallback(): void {
        $validator = Validator::make(
            array('test' => function (?string $a = null): ?int {}),
            array('test' => 'callback:?string?=?int')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $validator2 = Validator::make(
            array('test' => array(self::class, 'testCallback')),
            array('test' => 'callback:=void')
        );

        $this->assertTrue($validator2->throw(LogicException::class));
    }

    function testCallbackWildcard(): void {
        $validator = Validator::make(
            array('test' => function (?string $a = null, $b = null): ?int {}),
            array('test' => 'callback:,=?int')
        );

        $this->assertTrue($validator->throw(LogicException::class));
    }

    function testCallbackLessParams() {
        $validator = Validator::make(
            array('test' => function (): int {}),
            array('test' => 'callback:?string?=int')
        );

        $this->assertTrue($validator->throw(LogicException::class));
    }

    function testCallbackNoCallableFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'callback:=void')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testCallbackNoOptionsFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => 'var_dump'),
            array('test' => 'callback')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testCallbackMoreParamsFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => function (string $a, int $b) {}),
            array('test' => 'callback:string=void')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testCallbackParamTypeFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => function (string $a) {}),
            array('test' => 'callback:int=void')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testCallbackNotNullableParamTypeFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => function (string $a) {}),
            array('test' => 'callback:?string=void')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testCallbackNoReturnFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => function (string $a) {}),
            array('test' => 'callback:string=void')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testCallbackNoMatchingReturnFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => function (string $a): int {}),
            array('test' => 'callback:string=void')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testCallbackNoMatchingNullReturnFailure() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => function (string $a): int {}),
            array('test' => 'callback:string=?int')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testClassAnyString() {
        $validator = Validator::make(
            array('test' => \stdClass::class),
            array('test' => 'class:\\stdClass')
        );

        $this->assertTrue($validator->throw(LogicException::class));
    }

    function testClassAnyObject() {
        $validator = Validator::make(
            array('test' => (new \stdClass())),
            array('test' => 'class:\\stdClass')
        );

        $this->assertTrue($validator->throw(LogicException::class));
    }

    function testClassObject() {
        $validator = Validator::make(
            array('test' => (new \stdClass())),
            array('test' => 'class:\\stdClass=object')
        );

        $this->assertTrue($validator->throw(LogicException::class));
    }

    function testClassString() {
        $validator = Validator::make(
            array('test' => \stdClass::class),
            array('test' => 'class:\\stdClass=string')
        );

        $this->assertTrue($validator->throw(LogicException::class));
    }

    function testClassFail() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => 'muffin'),
            array('test' => 'class:\\stdClass')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testClassWrongObject() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => (new \stdClass())),
            array('test' => 'class:\\ArrayObject')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testClassInvalidTypeString() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => (new \stdClass())),
            array('test' => 'class:\\stdClass=string')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testClassInvalidTypeObject() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => \stdClass::class),
            array('test' => 'class:\\stdClass=object')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testClassInvalidArg() {
        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => 5),
            array('test' => 'class:\\stdClass')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testConfirmed() {
        $validator = Validator::make(
            array('test' => 'hi', 'test_confirmation' => 'hi'),
            array('test' => 'confirmed')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 3),
            array('test' => 'confirmed')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testDate() {
        $validator = Validator::make(
            array('test' => '2010-01-01'),
            array('test' => 'date')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'date')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testDateFormat() {
        $validator = Validator::make(
            array('test' => '01.01.2010'),
            array('test' => 'dateformat:d.m.Y')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => '2010-01-01'),
            array('test' => 'dateformat:d.m.Y')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testDifferent() {
        $validator = Validator::make(
            array('test' => 'var_dump', 'test2' => 'hi'),
            array('test' => 'different:test2')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'var_dump', 'test2' => 'var_dump'),
            array('test' => 'different:test2')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testDigits() {
        $validator = Validator::make(
            array('test' => '500'),
            array('test' => 'digits:3')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => '20'),
            array('test' => 'digits:3')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testDimensions() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:min_width=10,min_height=10,width=32,height=32,max_width=40,max_height=40,ratio=1')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile.png', 'error' => 0);

        $validator2 = Validator::make(
            array(),
            array('test' => 'dimensions:ratio=1/1')
        );

        $this->assertTrue($validator2->throw(LogicException::class));

        $this->expectException(LogicException::class);

        unset($_FILES['test']);

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:min_width=40')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDimensions2() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:min_height=40')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDimensions3() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:width=40')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDimensions4() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:height=40')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDimensions5() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:max_width=10')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDimensions6() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:max_height=10')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDimensions7() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'dimensions:ratio=0.5')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDimensions8() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile2.png', 'error' => 0);

        $validator = Validator::make(
            array(),
            array('test' => 'dimensions:ratio=0.5')
        );

        $this->assertFalse($validator->throw(LogicException::class));

        unset($_FILES['test']);
    }

    function testDimensions9() {

        $this->expectException(LogicException::class);

        $validator = Validator::make(
            array('test' => null),
            array('test' => 'dimensions:')
        );

        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testDistinct() {
        $validator = Validator::make(
            array('test' => array(0, 1)),
            array('test' => 'distinct')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => array(0, 0)),
            array('test' => 'distinct')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testEmail() {
        $validator = Validator::make(
            array('test' => 'email@test.com'),
            array('test' => 'email')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'email')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testFile() {
        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile.png', 'error' => 0);

        $validator = Validator::make(
            array(),
            array('test' => 'file')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        unset($_FILES['test']);

        $validator2 = Validator::make(
            array(),
            array('test' => 'file')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testFilled() {
        $validator = Validator::make(
            array('test' => 'var_dump'),
            array('test' => 'filled')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 0),
            array('test' => 'filled')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testFloat() {
        $validator = Validator::make(
            array('test' => 5.2),
            array('test' => 'float')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'float')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testFunction() {
        $validator = Validator::make(
            array('test' => function () {}),
            array('test' => 'function')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'function')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testImage() {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'image')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile.png', 'error' => 0);

        $validator2 = Validator::make(
            array(),
            array('test' => 'image')
        );

        $this->assertTrue($validator2->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile2.png', 'error' => 0);

        $validator3 = Validator::make(
            array(),
            array('test' => 'image')
        );

        $this->assertFalse($validator3->throw(LogicException::class));

        unset($_FILES['test']);
    }

    function testImage2() {
        $this->expectException(LogicException::class);

        $validator4 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'image')
        );

        $this->assertFalse($validator4->throw(LogicException::class));
    }

    function testIn() {
        $validator = Validator::make(
            array('test' => '5'),
            array('test' => 'in:5,4')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => '1'),
            array('test' => 'in:5,4')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testInteger() {
        $validator = Validator::make(
            array('test' => 5),
            array('test' => 'integer')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'integer')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testIP() {
        $validator = Validator::make(
            array('test' => '192.168.1.1'),
            array('test' => 'ip')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'what is this'),
            array('test' => 'ip')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testJSON() {
        $validator = Validator::make(
            array('test' => '{"help":true}'),
            array('test' => 'json')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => ''),
            array('test' => 'json')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testLowercase() {
        $validator = Validator::make(
            array('test' => 'ha'),
            array('test' => 'lowercase')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(
            array('test' => 'HA'),
            array('test' => 'lowercase')
        );

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    function testMax() {
        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile.png', 'error' => 0);

        $validator = Validator::make(
            array(),
            array('test' => 'max:6')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        unset($_FILES['test']);

        $validator2 = Validator::make(
            array('test' => array(2, 5, 30)),
            array('test' => 'max:6')
        );

        $this->assertTrue($validator2->throw(LogicException::class));

        $validator3 = Validator::make(
            array('test' => 5),
            array('test' => 'max:6')
        );

        $this->assertTrue($validator3->throw(LogicException::class));

        $validator4 = Validator::make(
            array('test' => 'abcd'),
            array('test' => 'max:6')
        );

        $this->assertTrue($validator4->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator5 = Validator::make(
            array('test' => 5),
            array('test' => 'max:4')
        );

        $this->assertFalse($validator5->throw(LogicException::class));
    }

    public function testMax2(): void
    {
        $this->expectException(LogicException::class);

        $validator = Validator::make(['test' => 'uiasufisa'], ['test' => 'max:4']);
        $this->assertFalse($validator->throw(LogicException::class));
    }

    function testMimeTypes() {

        $file = file_get_contents(__DIR__.'/testfile.png');

        $validator = Validator::make(
            array('test' => $file),
            array('test' => 'mimetypes:image/*')
        );

        $this->assertTrue($validator->throw(LogicException::class));

        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile.png', 'error' => 0);

        $validator2 = Validator::make(
            array(),
            array('test' => 'mimetypes:*/*')
        );

        $this->assertTrue($validator2->throw(LogicException::class));

        unset($_FILES['test']);

        $this->expectException(LogicException::class);

        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile2.png', 'error' => 0);

        $validator3 = Validator::make(
            array(),
            array('test' => 'mimetypes:')
        );

        $this->assertFalse($validator3->throw(LogicException::class));

        unset($_FILES['test']);
    }

    public function testMimeTypes2(): void
    {
        $file = file_get_contents(__DIR__.'/testfile.png');

        $this->expectException(LogicException::class);

        $validator4 = Validator::make(['test' => $file], ['test' => 'mimetypes:']);
        $this->assertFalse($validator4->throw(LogicException::class));
    }

    public function testMin(): void
    {
        $_FILES['test'] = array('tmp_name' => __DIR__.'/testfile.png', 'error' => 0);

        $validator = Validator::make([], ['test' => 'min:1']);

        $this->assertTrue($validator->throw(LogicException::class));

        unset($_FILES['test']);

        $validator2 = Validator::make(['test' => [2, 5, 30]], ['test' => 'min:1']);
        $this->assertTrue($validator2->throw(LogicException::class));

        $validator3 = Validator::make(['test' => 5], ['test' => 'min:1']);
        $this->assertTrue($validator3->throw(LogicException::class));

        $validator4 = Validator::make(['test' => 'abcd'], ['test' => 'min:1']);
        $this->assertTrue($validator4->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator5 = Validator::make(['test' => 5], ['test' => 'min:6']);
        $this->assertFalse($validator5->throw(LogicException::class));
    }

    public function testMin2(): void
    {
        $this->expectException(LogicException::class);

        $validator = Validator::make(['test' => 'abc'], ['test' => 'min:4']);
        $this->assertFalse($validator->throw(LogicException::class));
    }

    public function testNoWhitespace(): void
    {
        $validator = Validator::make(['test' => 'hi'], ['test' => 'nowhitespace']);
        $this->assertTrue($validator->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator2 = Validator::make(['test' => 'what is this'], ['test' => 'nowhitespace']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testNullable(): void
    {
        $validator = Validator::make(['test' => null], ['test' => 'nullable']);
        $this->assertTrue($validator->throw(LogicException::class));

        $validator2 = Validator::make(['test' => null], ['test' => 'nullable|numeric']);
        $this->assertTrue($validator2->throw(LogicException::class));
    }

    public function testNumeric(): void
    {
        $validator = Validator::make(['test' => '5'], ['test' => 'numeric']);

        $this->assertTrue($validator->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator2 = Validator::make(['test' => 'what is this'], ['test' => 'numeric']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testPresent(): void
    {
        $validator = Validator::make(['test' => 5], ['test' => 'present']);
        $this->assertTrue($validator->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator2 = Validator::make([], ['test' => 'present']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testRegex(): void
    {
        $validator = Validator::make(['test' => 5], ['test' => 'regex:/\\d+/']);
        $this->assertTrue($validator->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator2 = Validator::make(['test' => 'what is this'], ['test' => 'regex:/\\d+/']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testRequired(): void
    {
        $validator = Validator::make(['test' => 5], ['test' => 'required']);
        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);
        $validator2 = Validator::make(['test' => null], ['test' => 'required']);

        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testSame(): void
    {
        $validator = Validator::make(['test' => 5, 'test2' => 5], ['test' => 'same:test2']);
        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(['test' => 5, 'test2' => 4], ['test' => 'same:test2']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testSize(): void
    {
        $_FILES['test'] = ['tmp_name' => __DIR__.'/testfile.png', 'error' => 0];

        $validator = Validator::make([], ['test' => 'size:2']);
        $this->assertTrue($validator->throw(LogicException::class));

        unset($_FILES['test']);

        $validator2 = Validator::make(['test' => [0, 1, 2, 3, 4]], ['test' => 'size:5']);
        $this->assertTrue($validator2->throw(LogicException::class));

        $validator3 = Validator::make(['test' => 5], ['test' => 'size:5']);
        $this->assertTrue($validator3->throw(LogicException::class));

        $validator4 = Validator::make(['test' => 'hello'], ['test' => 'size:5']);
        $this->assertTrue($validator4->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator5 = Validator::make(['test' => 'hi'], ['test' => 'size:5']);
        $this->assertFalse($validator5->throw(LogicException::class));
    }

    public function testString(): void
    {
        $validator = Validator::make(['test' => 'hello'], ['test' => 'string']);
        $this->assertTrue($validator->throw(LogicException::class));

        $this->expectException(LogicException::class);

        $validator2 = Validator::make(['test' => 5], ['test' => 'string']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testUppercase(): void
    {
        $validator = Validator::make(['test' => 'API'], ['test' => 'uppercase']);
        $this->assertTrue($validator->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator2 = Validator::make(['test' => 'hello'], ['test' => 'uppercase']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testURL(): void
    {
        $validator = Validator::make(['test' => 'https://github.com'], ['test' => 'url']);
        $this->assertTrue($validator->throw(LogicException::class));
        $this->expectException(LogicException::class);

        $validator2 = Validator::make(['test' => 'hello'], ['test' => 'url']);
        $this->assertFalse($validator2->throw(LogicException::class));
    }

    public function testFailNullableRule(): void
    {
        $validator = Validator::make(['test' => null], ['test' => 'string']);
        $this->assertFalse($validator->passes());
    }

    public function testFailNullableRule2(): void
    {
        $validator = Validator::make(['test' => null], ['test' => 'between:0,1']);
        $this->assertFalse($validator->passes());
    }

    public function testFailNullableRule3(): void
    {
        $validator = Validator::make(['test' => 5], ['test' => 'nullable|between:0,1']);
        $this->assertFalse($validator->passes());
    }

    public function testInvalidRule(): void
    {
        $this->expectException(\RuntimeException::class);
        Validator::make(['field' => 'int'], ['field' => 'int'])->throw();
    }

    public function testLanguageFun(): void
    {
        $validator = Validator::make(array(), array());

        $this->assertSame('test', $validator->language('test'));
        $this->assertSame('Is smaller / before than 1', $validator->language('formvalidator_make_before', array('{0}' => '1')));
        $this->assertSame([], $validator->errors());
    }

    public function testUnknownField(): void
    {
        $validator = Validator::make(['ha' => 'string'], [], true);

        $this->assertTrue($validator->fails());
        $this->assertSame(['ha' => 'Is an unknown field'], $validator->errors());
    }

    public function testUnknownFieldThrow(): void
    {
        $validator = Validator::make(['ha' => 'string'], [], true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"ha" is an unknown field');

        $validator->throw(LogicException::class);
    }
}
