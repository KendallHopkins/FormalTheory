<?php

/*
Examples:
(|1) -> 1?
(|1|2) -> (1|2)?
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_LambdaInUnion extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const IS_SAFE = TRUE;
	const COST = 0;
	const SUCCESS = 1;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Union" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		foreach( $token->getTokens() as $sub_token ) {
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Regex && ! $sub_token->getTokens() ) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$new_union = new FormalTheory_RegularExpression_Token_Union( array_filter( $token->getTokens(), function( $sub_token ) {
			return ( ! $sub_token instanceof FormalTheory_RegularExpression_Token_Regex ) || $sub_token->getTokens();
		} ) );
		return new FormalTheory_RegularExpression_Token_Repeat( $new_union, 0, 1 );
	}
	
}

?>