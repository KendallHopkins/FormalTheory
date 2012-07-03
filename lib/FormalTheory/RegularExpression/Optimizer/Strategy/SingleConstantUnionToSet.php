<?php

/*
Examples:
(1|2|3) -> [1-3]
(1|2|3|10) -> (10|[1-3])
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_SingleConstantUnionToSet extends FormalTheory_RegularExpression_Optimizer_Strategy
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
		$count = 0;
		foreach( $token->getTokens() as $sub_token ) {
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Constant ) {
				if( ++$count >= 2 ) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$char_array = array();
		$sub_tokens = array();
		foreach( $token->getTokens() as $sub_token ) {
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Constant ) {
				$char_array[] = $sub_token->getString();
			} else {
				$sub_tokens[] = $sub_token;
			}
		}
		$sub_tokens[] = new FormalTheory_RegularExpression_Token_Set( $char_array, TRUE );
		return new FormalTheory_RegularExpression_Token_Union( $sub_tokens );
	}
	
}

?>