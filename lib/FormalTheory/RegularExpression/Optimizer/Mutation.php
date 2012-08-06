<?php

abstract class FormalTheory_RegularExpression_Optimizer_Mutation
{
	
	//abstract const COST = (int); //estimated ms
	
	abstract function qualifiedClassNames();
	abstract function qualifier( FormalTheory_RegularExpression_Token $token );
	abstract function countOptions( FormalTheory_RegularExpression_Token $token );
	abstract function run( FormalTheory_RegularExpression_Token $token, $option_index );
	
}

?>