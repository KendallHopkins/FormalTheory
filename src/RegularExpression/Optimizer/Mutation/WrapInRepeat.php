<?php
namespace FormalTheory\RegularExpression\Optimizer\Mutation;

use FormalTheory\RegularExpression\Optimizer\Mutation;
use FormalTheory\RegularExpression\Token\Repeat;
use FormalTheory\RegularExpression\Token\Set;
use FormalTheory\RegularExpression\Token\Union;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token\Constant;
use FormalTheory\RegularExpression\Token;

class WrapInRepeat extends Mutation
{

    const COST = 0;

    function qualifiedClassNames()
    {
        return array(
            Constant::class,
            Regex::class,
            Repeat::class,
            Set::class,
            Union::class
        );
    }

    function qualifier(Token $token)
    {
        return TRUE;
    }

    function countOptions(Token $token)
    {
        return count(self::_getCombinations());
    }

    function run(Token $token, $option_index)
    {
        $pairs = self::_getCombinations();
        return new Repeat($token, $pairs[$option_index][0], $pairs[$option_index][1]);
    }

    static private function _getCombinations()
    {
        return array(
            array(
                0,
                1
            ),
            array(
                0,
                NULL
            ),
            array(
                1,
                NULL
            )
        );
    }
}

?>