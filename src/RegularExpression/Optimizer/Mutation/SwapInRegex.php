<?php
namespace FormalTheory\RegularExpression\Optimizer\Mutation;

use FormalTheory\RegularExpression\Optimizer\Mutation;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Regex;

class SwapInRegex extends Mutation
{

    const COST = 0;

    function qualifiedClassNames()
    {
        return array(
            Regex::class
        );
    }

    function qualifier(Token $token)
    {
        return count($token->getTokens()) > 1;
    }

    function countOptions(Token $token)
    {
        $count = count($token->getTokens());
        return (($count - 1) * $count) / 2;
    }

    function run(Token $token, $option_index)
    {
        $sub_tokens = $token->getTokens();
        $count = count($sub_tokens) - 1;
        $first_offset = 0;
        while ($option_index + $first_offset >= $count) {
            $option_index -= $count - $first_offset ++;
        }
        $second_offset = $first_offset + $option_index + 1;
        list ($sub_tokens[$second_offset], $sub_tokens[$first_offset]) = array(
            $sub_tokens[$first_offset],
            $sub_tokens[$second_offset]
        );
        return new Regex($sub_tokens, FALSE);
    }
}

?>