<?php

/*
Examples:
a{1} -> a
a{0} -> 
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_UnwrapSimpleRepeat extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const COST = 0;
	const SUCCESS = 1;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Repeat" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return $token->getMinNumber() === $token->getMaxNumber() && in_array( $token->getMinNumber(), array( 0, 1 ) );
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		switch( $token->getMinNumber() ) {
			case 0: return new FormalTheory_RegularExpression_Token_Regex( array(), FALSE );
			case 1: return $token->getToken();
		}
		throw new RuntimeException( "should never happen" );
	}
	
}

?>