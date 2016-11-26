<?php
namespace FormalTheory\Tests\RegularExpression;

use FormalTheory\RegularExpression\Lexer;

class ToStringTest extends \PHPUnit_Framework_TestCase
{

    function dataProviderForTestSimpleRead()
    {
        return array(
            array(
                "ab"
            ),
            array(
                "(ab)",
                "ab"
            ),
            array(
                "(a)(b)",
                "ab"
            ),
            array(
                "(a(b|c)(d))",
                "a(b|c)d"
            ),
            array(
                "((a)(b))",
                "ab"
            ),
            array(
                "((ab))",
                "ab"
            ),
            array(
                "((ab))*",
                "(ab)*"
            ),
            array(
                "((a|b))",
                "(a|b)"
            ),
            array(
                "(a)|(b)",
                "(a|b)"
            ),
            array(
                "a|b",
                "(a|b)"
            ),
            array(
                "(a|b)"
            ),
            array(
                "^(a|b)$"
            ),
            array(
                "^a|b$",
                "(^a|b$)"
            ),
            array(
                "[ab]"
            ),
            array(
                "[ab]+"
            ),
            array(
                "[ab]*"
            ),
            array(
                "[ab]{2,}"
            ),
            array(
                "\d"
            ),
            array(
                "\D"
            ),
            array(
                "\w"
            ),
            array(
                "\W"
            ),
            array(
                "\s"
            ),
            array(
                "\S"
            ),
            array(
                '\n'
            ),
            array(
                '\r'
            ),
            array(
                '\t'
            ),
            array(
                '\v'
            ),
            array(
                "."
            ),
            array(
                "[1-5N-Za-m]"
            ),
            array(
                "[abcdefghijlmnopqrstuvwxz]",
                "[a-jl-xz]"
            ),
            array(
                "[zvnoerstblmwijguqdpfxahc]",
                "[a-jl-xz]"
            ),
            array(
                "[^]"
            ),
            array(
                "[\^]"
            ),
            array(
                "[^a]"
            ),
            array(
                "[^a-d]"
            ),
            array(
                "[^\^]"
            ),
            array(
                "[^^]",
                "[^\^]"
            ),
            array(
                "1{1,1}",
                "1"
            ),
            array(
                "1{0,1}",
                "1?"
            ),
            array(
                "1{1,}",
                "1+"
            ),
            array(
                "1{0,}",
                "1*"
            ),
            array(
                "1{0,0}",
                ""
            ),
            array(
                "^$"
            ),
            array(
                "^1$"
            ),
            array(
                "^[^]*$"
            )
        );
    }

    /**
     * @dataProvider dataProviderForTestSimpleRead
     */
    function testSimpleRead($regex_string, $expected_string = NULL)
    {
        if (is_null($expected_string))
            $expected_string = $regex_string;
        $lexer = new Lexer();
        $regex = $lexer->lex($regex_string);
        $regex_string = (string) $regex;
        $this->assertSame($expected_string, $regex_string);
        
        // $regex_after_tostring = $lexer->lex( $regex_string );
        // $this->assertTrue( $regex->getDFA()->compare( $regex_after_tostring->getDFA() ) );
    }
}

?>