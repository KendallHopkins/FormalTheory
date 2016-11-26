<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Repeat;
use FormalTheory\RegularExpression\Token\Special;
use FormalTheory\RegularExpression\Token\Set;

/*
 * Examples:
 * ^0[^]*$ -> ^0
 * ^[^]*0$ -> 0$
 * ^0[^]+$ -> ^0[^]
 * ^[^]+0$ -> [^]0$
 */
class RemoveEnds extends Strategy
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
        $sub_tokens = $token->getTokens();
        $sub_tokens_count = count($sub_tokens);
        if ($sub_tokens_count < 2)
            return FALSE;
        return $sub_tokens[0] instanceof Special || $sub_tokens[$sub_tokens_count - 1] instanceof Special;
    }

    function run(Token $token)
    {
        $is_wild_repeat = function (Token $token) {
            return $token instanceof Repeat && is_null($token->getMaxNumber()) && $token->getToken() instanceof Set && (string) $token->getToken() === "[^]";
        };
        
        $sub_tokens = $token->getTokens();
        $sub_tokens_count = count($sub_tokens);
        
        $start_is_removable = $sub_tokens[0] instanceof Special && $sub_tokens[0]->isBOS() && $is_wild_repeat($sub_tokens[1]);
        
        $end_is_removeable = $sub_tokens[$sub_tokens_count - 1] instanceof Special && $sub_tokens[$sub_tokens_count - 1]->isEOS() && $is_wild_repeat($sub_tokens[$sub_tokens_count - 2]);
        
        if (! $start_is_removable && ! $end_is_removeable) {
            return FALSE;
        }
        
        $wildcard_shared = $start_is_removable && $end_is_removeable && $sub_tokens_count === 3;
        
        if ($start_is_removable) {
            $did_change = TRUE;
            array_shift($sub_tokens);
            if (! $wildcard_shared) {
                $repeat = array_shift($sub_tokens);
                if ($repeat->getMinNumber() > 0) {
                    array_unshift($sub_tokens, new Repeat($repeat->getToken(), $repeat->getMinNumber(), $repeat->getMinNumber()));
                }
            }
        }
        if ($end_is_removeable) {
            $did_change = TRUE;
            array_pop($sub_tokens);
            $repeat = array_pop($sub_tokens);
            if ($repeat->getMinNumber() > 0) {
                array_push($sub_tokens, new Repeat($repeat->getToken(), $repeat->getMinNumber(), $repeat->getMinNumber()));
            }
        }
        if (! $did_change) {
            return FALSE;
        }
        return new Regex($sub_tokens, FALSE);
    }
}

?>