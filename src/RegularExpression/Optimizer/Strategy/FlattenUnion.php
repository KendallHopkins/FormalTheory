<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token\Set;
use FormalTheory\RegularExpression\Token\Union;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Constant;

/*
 * Examples:
 * (1|(2|3)) -> (1|2|3)
 * ([1-3]|[3-5]) -> (1|2|3|4|5)
 */
class FlattenUnion extends Strategy
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
        $count = 0;
        foreach ($token->getTokens() as $sub_token) {
            if ($sub_token instanceof Union) {
                return TRUE;
            } else 
                if ($sub_token instanceof Set) {
                    // This prevents a loop where this Strategy and SingleConstantUnionToSet would loop
                    if (++ $count >= 2) {
                        return TRUE;
                    }
                }
        }
        return FALSE;
    }

    function run(Token $token)
    {
        $sub_tokens = array();
        foreach ($token->getTokens() as $sub_token) {
            if ($sub_token instanceof Union) {
                $sub_tokens = array_merge($sub_tokens, $sub_token->getTokens());
            } else 
                if ($sub_token instanceof Set) {
                    foreach ($sub_token->charArray() as $char) {
                        $sub_tokens[] = new Constant($char);
                    }
                } else {
                    $sub_tokens[] = $sub_token;
                }
        }
        return new Union($sub_tokens);
    }
}

?>