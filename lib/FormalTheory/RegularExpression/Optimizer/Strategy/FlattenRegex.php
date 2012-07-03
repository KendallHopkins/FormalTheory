<?php

/*
Examples:
(12(34)56) -> (123456)
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_FlattenRegex extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const IS_SAFE = TRUE;
	const COST = 0;
	const SUCCESS = 1;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		foreach( $token->getTokens() as $sub_token ) {
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Regex ) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = array();
		foreach( $token->getTokens() as $sub_token ) {
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Regex ) {
				$sub_tokens = array_merge( $sub_tokens, $sub_token->getTokens() );
			} else {
				$sub_tokens[] = $sub_token;
			}
		}
		return new FormalTheory_RegularExpression_Token_Regex( $sub_tokens, FALSE );
	}
	
}

?>