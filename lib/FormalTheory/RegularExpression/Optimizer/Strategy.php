<?php

abstract class FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	//abstract const IS_SAFE = (bool); //can chance value of regex
	//abstract const COST = (int); //estimated ms
	//abstract const SUCCESS = (float); //chance of operation resulting in correct optimization
	
	abstract function qualifiedClassNames();
	abstract function qualifier( FormalTheory_RegularExpression_Token $token );
	abstract function run( FormalTheory_RegularExpression_Token $token );
	
}

?>