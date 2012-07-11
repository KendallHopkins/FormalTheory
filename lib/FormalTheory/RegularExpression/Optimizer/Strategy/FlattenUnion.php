<?php

/*
Examples:
(1|(2|3)) -> (1|2|3)
([1-3]|[3-5]) -> (1|2|3|4|5)
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_FlattenUnion extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
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
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Union ) {
				return TRUE;
			} else if( $sub_token instanceof FormalTheory_RegularExpression_Token_Set ) {
				//This prevents a loop where this Strategy and FormalTheory_RegularExpression_Optimizer_Strategy_SingleConstantUnionToSet would loop
				if( ++$count >= 2 ) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = array();
		foreach( $token->getTokens() as $sub_token ) {
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Union ) {
				$sub_tokens = array_merge( $sub_tokens, $sub_token->getTokens() );
			} else if( $sub_token instanceof FormalTheory_RegularExpression_Token_Set ) {
				foreach( $sub_token->charArray() as $char ) {
					$sub_tokens[] = new FormalTheory_RegularExpression_Token_Constant( $char );
				}
			} else {
				$sub_tokens[] = $sub_token;
			}
		}
		return new FormalTheory_RegularExpression_Token_Union( $sub_tokens );
	}
	
}

?>