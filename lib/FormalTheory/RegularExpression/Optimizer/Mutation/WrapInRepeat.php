<?php

class FormalTheory_RegularExpression_Optimizer_Mutation_WrapInRepeat extends FormalTheory_RegularExpression_Optimizer_Mutation
{
	
	const COST = 0;
	
	function qualifiedClassNames()
	{
		return array(
			"FormalTheory_RegularExpression_Token_Constant",
			"FormalTheory_RegularExpression_Token_Regex",
			"FormalTheory_RegularExpression_Token_Repeat",
			"FormalTheory_RegularExpression_Token_Set",
			"FormalTheory_RegularExpression_Token_Union",
		);
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return TRUE;
	}
	
	function countOptions( FormalTheory_RegularExpression_Token $token )
	{
		return count( self::_getCombinations() );
	}
	
	function run( FormalTheory_RegularExpression_Token $token, $option_index )
	{
		$pairs = self::_getCombinations();
		return new FormalTheory_RegularExpression_Token_Repeat( $token, $pairs[$option_index][0], $pairs[$option_index][1] );
	}
	
	static private function _getCombinations()
	{
		return array(
			array( 0, 1 ),
			array( 0, NULL ),
			array( 1, NULL ),
		);
	}
	
}

?>