<?php
namespace FormalTheory\Tests\RegularExpression;

use FormalTheory\RegularExpression\Lexer;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token\Special;
use FormalTheory\RegularExpression\Token\Constant;
use FormalTheory\RegularExpression\Token\Repeat;
use FormalTheory\RegularExpression\Exception\LexException;
use FormalTheory\RegularExpression\Token\Union;

class LexTest extends \PHPUnit_Framework_TestCase
{

    function dataProviderForTestLex()
    {
        return array(
            array(
                "",
                new Regex(array(), FALSE)
            ),
            array(
                "()",
                new Regex(array(
                    new Regex(array(), TRUE)
                ), FALSE)
            ),
            array(
                "^$",
                new Regex(array(
                    new Special("^"),
                    new Special("$")
                ), FALSE)
            ),
            array(
                "^a|b$",
                new Regex(array(
                    new Union(array(
                        new Regex(array(
                            new Special("^"),
                            new Constant("a")
                        ), TRUE),
                        new Regex(array(
                            new Constant("b"),
                            new Special("$")
                        ), TRUE)
                    ))
                ), FALSE)
            ),
            array(
                "aa|bb",
                new Regex(array(
                    new Union(array(
                        new Regex(array(
                            new Constant("a"),
                            new Constant("a")
                        ), TRUE),
                        new Regex(array(
                            new Constant("b"),
                            new Constant("b")
                        ), TRUE)
                    ))
                ), FALSE)
            ),
            array(
                "a|b{1,2}",
                new Regex(array(
                    new Union(array(
                        new Regex(array(
                            new Constant("a")
                        ), TRUE),
                        new Regex(array(
                            new Repeat(new Constant("b"), 1, 2)
                        ), TRUE)
                    ))
                ), FALSE)
            ),
            array(
                "{",
                new Regex(array(
                    new Constant("{")
                ), FALSE)
            )
        );
    }

    /**
     * @dataProvider dataProviderForTestLex
     */
    function testLex($regex_string, Regex $expected_regex_object)
    {
        $lexer = new Lexer();
        $this->assertEquals($expected_regex_object, $lexer->lex($regex_string));
    }

    function dataProviderForTestLexFailure()
    {
        return array(
            array(
                '(',
                LexException::class,
                "unexpected end"
            ),
            array(
                '( ',
                LexException::class,
                "unexpected end"
            ),
            array(
                ' (',
                LexException::class,
                "unexpected end"
            ),
            array(
                ')',
                LexException::class,
                "unexpected symbol ')'"
            ),
            array(
                ') ',
                LexException::class,
                "unexpected symbol ')'"
            ),
            array(
                ' )',
                LexException::class,
                "unexpected symbol ')'"
            ),
            array(
                '[',
                LexException::class,
                "unexpectedly found end while in set"
            ),
            array(
                '[ ',
                LexException::class,
                "unexpectedly found end while in set"
            ),
            array(
                '^\x',
                LexException::class,
                "unexpected end"
            ),
            array(
                '^\xa',
                LexException::class,
                "unexpected end"
            ),
            array(
                '^\xxx$',
                LexException::class,
                "unexpected non-hex character"
            ),
            array(
                '^\x',
                LexException::class,
                "unexpected end"
            ),
            array(
                '^\xa',
                LexException::class,
                "unexpected end"
            ),
            array(
                '^\xxx$',
                LexException::class,
                "unexpected non-hex character"
            ),
            array(
                '^\xax$',
                LexException::class,
                "unexpected non-hex character"
            ),
            array(
                '^{1}$',
                LexException::class,
                "unexpected repeat"
            ),
            array(
                '^{1,2}$',
                LexException::class,
                "unexpected repeat"
            ),
            array(
                '^1{2,1}$',
                LexException::class,
                "repeat found with min higher than max"
            ),
            array(
                '^*$',
                LexException::class,
                "unexpected repeat"
            ),
            array(
                '*',
                LexException::class,
                "unexpected repeat"
            ),
            array(
                '^1{2}{2}$',
                LexException::class,
                "unexpected repeat"
            )
        );
    }

    /**
     * @dataProvider dataProviderForTestLexFailure
     */
    function testLexFailure($regex, $exception_class, $exception_message)
    {
        $this->setExpectedException($exception_class, $exception_message);
        $lexer = new Lexer();
        $lexer->lex($regex);
    }
}

?>