<?php
namespace FormalTheory\RegularExpression\Optimizer\Strategy;

use FormalTheory\RegularExpression\Optimizer\Strategy;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token\Union;
use FormalTheory\RegularExpression\Token;

/*
 * Examples:
 * (123|135) -> 1(23|35)
 * (335|125) -> (33|12)5
 * (1*25*|1*35*|1*45*) -> 1*[2-4]5*
 */
class RemoveSharedEndsOfUnion extends Strategy
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
        $sub_tokens = $token->getTokens();
        return count($sub_tokens) > 1;
    }

    function run(Token $token)
    {
        $sub_tokens = $token->getTokens();
        $sub_tokens_array = array();
        foreach ($sub_tokens as $sub_token) {
            $sub_tokens_array[] = $sub_token instanceof Regex ? $sub_token->getTokens() : array(
                $sub_token
            );
        }
        $compare_offset = function (array $sub_tokens_array, $offset) {
            $first_sub_tokens = array_pop($sub_tokens_array);
            $first_sub_tokens_count = count($first_sub_tokens);
            if ($offset < 0) {
                $first_offset = $first_sub_tokens_count + $offset;
                if ($first_offset < 0) {
                    return FALSE;
                }
                foreach ($sub_tokens_array as $current_sub_tokens) {
                    $current_sub_tokens_count = count($current_sub_tokens);
                    $current_offset = $current_sub_tokens_count + $offset;
                    if ($current_offset < 0) {
                        return FALSE;
                    }
                    if (! $first_sub_tokens[$first_offset]->compare($current_sub_tokens[$current_offset])) {
                        return FALSE;
                    }
                }
            } else {
                if ($offset >= $first_sub_tokens_count) {
                    return FALSE;
                }
                foreach ($sub_tokens_array as $current_sub_tokens) {
                    if ($offset >= count($current_sub_tokens)) {
                        return FALSE;
                    }
                    if (! $first_sub_tokens[$offset]->compare($current_sub_tokens[$offset])) {
                        return FALSE;
                    }
                }
            }
            return TRUE;
        };
        
        if ($compare_offset($sub_tokens_array, 0)) {
            $first_item = $sub_tokens_array[0][0];
            $sub_regex_array = array_map(function ($sub_tokens) {
                array_shift($sub_tokens);
                return new Regex($sub_tokens, FALSE);
            }, $sub_tokens_array);
            return new Regex(array(
                $first_item,
                new Union($sub_regex_array)
            ), FALSE);
        }
        
        if ($compare_offset($sub_tokens_array, - 1)) {
            $last_item = $sub_tokens_array[0][count($sub_tokens_array[0]) - 1];
            $sub_regex_array = array_map(function ($sub_tokens) {
                array_pop($sub_tokens);
                return new Regex($sub_tokens, FALSE);
            }, $sub_tokens_array);
            return new Regex(array(
                new Union($sub_regex_array),
                $last_item
            ), FALSE);
        }
        
        return FALSE;
    }
}

?>