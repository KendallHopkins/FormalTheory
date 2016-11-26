<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Repeat;

/*
 * Examples:
 * (1{1,2}){1,2} -> 1{1,4}
 * (1*)+ -> 1*
 * (1+)* -> 1*
 */
class FlattenRepeat extends Strategy
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
        $sub_token = $token->getToken();
        if (! $sub_token instanceof Repeat) {
            return FALSE;
        }
        if (is_null($sub_token->getMaxNumber()) || $token->getMinNumber() === $token->getMaxNumber()) {
            return TRUE;
        }
        return ($sub_token->getMaxNumber() * $token->getMinNumber()) + 1 >= $sub_token->getMinNumber() * ($token->getMinNumber() + 1);
    }

    function run(Token $token)
    {
        $sub_token = $token->getToken();
        return new Repeat($sub_token->getToken(), $token->getMinNumber() * $sub_token->getMinNumber(), is_null($token->getMaxNumber()) || is_null($sub_token->getMaxNumber()) ? NULL : $token->getMaxNumber() * $sub_token->getMaxNumber());
    }
}

?>