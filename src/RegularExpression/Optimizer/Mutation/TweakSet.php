<?php
namespace FormalTheory\RegularExpression\Optimizer\Mutation;

use FormalTheory\RegularExpression\Optimizer\Mutation;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Set;

class TweakSet extends Mutation
{

    const COST = 0;

    function qualifiedClassNames()
    {
        return array(
            Set::class
        );
    }

    function qualifier(Token $token)
    {
        return TRUE;
    }

    function countOptions(Token $token)
    {
        return 128;
    }

    function run(Token $token, $option_index)
    {
        $char_array = $token->charArray();
        $tweaked_char = chr($option_index);
        $offset = array_search($tweaked_char, $char_array, TRUE);
        if ($offset === FALSE) {
            $char_array[] = $tweaked_char;
        } else {
            unset($char_array[$offset]);
        }
        return new Set($char_array, TRUE);
    }
}

?>