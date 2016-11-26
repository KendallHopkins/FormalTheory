<?php
namespace FormalTheory\RegularExpression\Optimizer;

use FormalTheory\RegularExpression\Token;

abstract class Strategy
{
    
    // abstract const COST = (int); //estimated ms
    // abstract const SUCCESS = (float); //chance of operation resulting in correct optimization
    abstract function qualifiedClassNames();

    abstract function qualifier(Token $token);

    abstract function run(Token $token);
}

?>