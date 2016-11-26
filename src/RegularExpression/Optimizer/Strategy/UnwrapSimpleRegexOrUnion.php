<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token\Union;
use FormalTheory\RegularExpression\Token;

/*
 * Examples:
 * 12()34 -> 1234
 * 12(a)34 -> 12a34
 * 12(a*)34 -> 12a*34
 */
class UnwrapSimpleRegexOrUnion extends Strategy
{

    const COST = 0;

    const SUCCESS = 1;

    function qualifiedClassNames()
    {
        return array(
            Regex::class,
            Union::class
        );
    }

    function qualifier(Token $token)
    {
        return count($token->getTokens()) === 1;
    }

    function run(Token $token)
    {
        $sub_tokens = $token->getTokens();
        return $sub_tokens[0];
    }
}

?>