<?php
namespace FormalTheory\Tests\RegularExpression;

use FormalTheory\RegularExpression\Lexer;

class SubsetTest extends \PHPUnit_Framework_TestCase
{

    function dataProviderForTestSubset()
    {
        return array(
            array(
                "",
                "",
                TRUE
            ),
            array(
                "$^",
                "",
                TRUE
            ),
            array(
                "$^",
                "^$",
                TRUE
            ),
            array(
                "^$",
                "",
                TRUE
            ),
            array(
                "^(0000)*$",
                "^(00)*$",
                TRUE
            ),
            array(
                "^(000)*$",
                "^(00)*$",
                FALSE
            ),
            array(
                "^(00)*$",
                "^(00)*$",
                TRUE
            ),
            array(
                "^0*$",
                "^(0|1)*$",
                TRUE
            ),
            array(
                "^(0|1)*$",
                "^0*$",
                FALSE
            ),
            array(
                "^1{0,5}$",
                "^1+$",
                FALSE
            ),
            array(
                "^a+$",
                "^(a*[bc])*$",
                FALSE
            )
        );
    }

    /**
     * @dataProvider dataProviderForTestSubset
     */
    function testSubset($regex_string1, $regex_string2, $expected_result)
    {
        $lexer = new Lexer();
        $nfa1 = $lexer->lex($regex_string1)->getNFA();
        $nfa2 = $lexer->lex($regex_string2)->getNFA();
        $this->assertSame($nfa1->isSubsetOf($nfa2), $expected_result);
    }

    function dataProviderForTestProperSubset()
    {
        return array(
            array(
                "",
                "",
                FALSE
            ),
            array(
                "$^",
                "",
                TRUE
            ),
            array(
                "$^",
                "^$",
                TRUE
            ),
            array(
                "^$",
                "",
                TRUE
            ),
            array(
                "^(0000)*$",
                "^(00)*$",
                TRUE
            ),
            array(
                "^(000)*$",
                "^(00)*$",
                FALSE
            ),
            array(
                "^(00)*$",
                "^(00)*$",
                FALSE
            ),
            array(
                "^0*$",
                "^(0|1)*$",
                TRUE
            ),
            array(
                "^(0|1)*$",
                "^0*$",
                FALSE
            ),
            array(
                "^1{0,5}$",
                "^1+$",
                FALSE
            )
        );
    }

    /**
     * @dataProvider dataProviderForTestProperSubset
     */
    function testProperSubset($regex_string1, $regex_string2, $expected_result)
    {
        $lexer = new Lexer();
        $nfa1 = $lexer->lex($regex_string1)->getNFA();
        $nfa2 = $lexer->lex($regex_string2)->getNFA();
        $this->assertSame($nfa1->isProperSubsetOf($nfa2), $expected_result);
    }
}

?>