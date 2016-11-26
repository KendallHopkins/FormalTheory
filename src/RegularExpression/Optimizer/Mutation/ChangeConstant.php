<?php
namespace FormalTheory\RegularExpression\Optimizer\Mutation;

use FormalTheory\RegularExpression\Token\Constant;
use FormalTheory\RegularExpression\Optimizer\Mutation;
use FormalTheory\RegularExpression\Token;

class ChangeConstant extends Mutation
{

    const COST = 0;

    function qualifiedClassNames()
    {
        return array(
            Constant::class
        );
    }

    function qualifier(Token $token)
    {
        return TRUE;
    }

    function countOptions(Token $token)
    {
        return 127;
    }

    function run(Token $token, $option_index)
    {
        if ($option_index >= ord($token->getString()))
            $option_index ++;
        return new Constant(chr($option_index));
    }
}

?>