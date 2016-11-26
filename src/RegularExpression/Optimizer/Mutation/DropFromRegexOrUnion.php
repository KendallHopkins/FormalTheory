<?php
namespace FormalTheory\RegularExpression\Optimizer\Mutation;

use FormalTheory\RegularExpression\Optimizer\Mutation;
use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Union;

class DropFromRegexOrUnion extends Mutation
{

    const COST = 0;

    function qualifiedClassNames()
    {
        return array(
            Regex::class,
            Union::class
        );
    }

    function qualifier(Token $token)
    {
        return count($token->getTokens()) > 0;
    }

    function countOptions(Token $token)
    {
        return count($token->getTokens());
    }

    function run(Token $token, $option_index)
    {
        $sub_tokens = $token->getTokens();
        unset($sub_tokens[$option_index]);
        if ($token instanceof Regex) {
            return new Regex($sub_tokens, FALSE);
        } else 
            if ($token instanceof Union) {
                return new Union($sub_tokens, FALSE);
            } else {
                throw new \RuntimeException("unreachable");
            }
    }
}

?>