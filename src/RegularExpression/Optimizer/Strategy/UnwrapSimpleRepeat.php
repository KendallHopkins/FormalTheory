<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token;

/*
 * Examples:
 * a{1} -> a
 * a{0} ->
 */
class UnwrapSimpleRepeat extends Strategy
{

    const COST = 0;

    const SUCCESS = 1;

    function qualifiedClassNames()
    {
        return array(
            Repeat::class
        );
    }

    function qualifier(Token $token)
    {
        return $token->getMinNumber() === $token->getMaxNumber() && in_array($token->getMinNumber(), array(
            0,
            1
        ));
    }

    function run(Token $token)
    {
        switch ($token->getMinNumber()) {
            case 0:
                return new Regex(array(), FALSE);
            case 1:
                return $token->getToken();
        }
        throw new \RuntimeException("should never happen");
    }
}

?>