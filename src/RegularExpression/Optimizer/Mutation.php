<?php
namespace FormalTheory\RegularExpression\Optimizer;

use FormalTheory\RegularExpression\Token;

abstract class Mutation
{
    
    // abstract const COST = (int); //estimated ms
    abstract function qualifiedClassNames();

    abstract function qualifier(Token $token);

    abstract function countOptions(Token $token);

    abstract function run(Token $token, $option_index);
}

?>