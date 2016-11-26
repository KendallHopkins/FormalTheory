<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token\Union;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Constant;
use FormalTheory\RegularExpression\Token\Set;

/*
 * Examples:
 * (1|2|3) -> [1-3]
 * (1|2|3|10) -> (10|[1-3])
 */
class SingleConstantUnionToSet extends Strategy
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
            if ($sub_token instanceof Constant || $sub_token instanceof Set) {
                if (++ $count >= 2) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    function run(Token $token)
    {
        $char_array = array();
        $sub_tokens = array();
        foreach ($token->getTokens() as $sub_token) {
            if ($sub_token instanceof Constant) {
                $char_array[] = $sub_token->getString();
            } else {
                $sub_tokens[] = $sub_token;
            }
        }
        $sub_tokens[] = new Set($char_array, TRUE);
        return new Union($sub_tokens);
    }
}

?>