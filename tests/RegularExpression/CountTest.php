<?php
namespace FormalTheory\Tests\RegularExpression;

use FormalTheory\RegularExpression\Lexer;

class CountTest extends \PHPUnit_Framework_TestCase
{

    function dataProviderForTestSimpleCount()
    {
        return array(
            array(
                "",
                NULL
            ),
            array(
                "$^",
                0
            ),
            array(
                "^$",
                1
            ),
            array(
                "^1*$",
                NULL
            ),
            array(
                "^1?$",
                2
            ),
            array(
                "^1{0,2}$",
                3
            ),
            array(
                "^1{0,9}$",
                10
            ),
            array(
                "^(1{1,3}){1,3}$",
                9
            ),
            array(
                "^(0|1){5}$",
                32
            ),
            array(
                "^(0|1){4,5}$",
                16 + 32
            )
        );
    }

    /**
     * @dataProvider dataProviderForTestSimpleCount
     */
    function testSimpleCount($regex_string, $expected_solution_count)
    {
        $lexer = new Lexer();
        $dfa = $lexer->lex($regex_string)->getDFA();
        $this->assertSame($dfa->countSolutions(), $expected_solution_count);
    }
}

?>