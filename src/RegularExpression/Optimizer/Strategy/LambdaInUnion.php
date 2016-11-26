<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token\Repeat;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Union;

/*
 * Examples:
 * (|1) -> 1?
 * (|1|2) -> (1|2)?
 */
class LambdaInUnion extends Strategy
{

    const COST = 0;

    const SUCCESS = 1;

    function qualifiedClassNames()
    {
        return array(
            Union::class
        );
    }

    function qualifier(Token $token)
    {
        foreach ($token->getTokens() as $sub_token) {
            if ($sub_token instanceof Regex && ! $sub_token->getTokens()) {
                return TRUE;
            }
        }
        return FALSE;
    }

    function run(Token $token)
    {
        $new_union = new Union(array_filter($token->getTokens(), function ($sub_token) {
            return (! $sub_token instanceof Regex) || $sub_token->getTokens();
        }));
        return new Repeat($new_union, 0, 1);
    }
}

?>