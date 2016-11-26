<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Regex;

/*
 * Examples:
 * (12(34)56) -> (123456)
 */
class FlattenRegex extends Strategy
{

    const COST = 0;

    const SUCCESS = 1;

    function qualifiedClassNames()
    {
        return array(
            Regex::class
        );
    }

    function qualifier(Token $token)
    {
        foreach ($token->getTokens() as $sub_token) {
            if ($sub_token instanceof Regex) {
                return TRUE;
            }
        }
        return FALSE;
    }

    function run(Token $token)
    {
        $sub_tokens = array();
        foreach ($token->getTokens() as $sub_token) {
            if ($sub_token instanceof Regex) {
                $sub_tokens = array_merge($sub_tokens, $sub_token->getTokens());
            } else {
                $sub_tokens[] = $sub_token;
            }
        }
        return new Regex($sub_tokens, FALSE);
    }
}

?>