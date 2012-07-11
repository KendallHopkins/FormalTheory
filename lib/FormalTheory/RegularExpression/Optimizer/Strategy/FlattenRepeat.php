<?php

/*
Examples:
(1{1,2}){1,2} -> 1{1,4}
(1*)+ -> 1*
(1+)* -> 1*
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_FlattenRepeat extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const COST = 0;
	const SUCCESS = 1;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Repeat" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		$sub_token = $token->getToken();
		if( ! $sub_token instanceof FormalTheory_RegularExpression_Token_Repeat ) {
			return FALSE;
		}
		if( is_null( $sub_token->getMaxNumber() ) || $token->getMinNumber() === $token->getMaxNumber() ) {
			return TRUE;
		}
		return ($sub_token->getMaxNumber()*$token->getMinNumber())+1 >= $sub_token->getMinNumber()*($token->getMinNumber()+1);
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$sub_token = $token->getToken();
		return new FormalTheory_RegularExpression_Token_Repeat(
			$sub_token->getToken(),
			$token->getMinNumber()*$sub_token->getMinNumber(),
			is_null( $token->getMaxNumber() ) || is_null( $sub_token->getMaxNumber() )
				? NULL
				: $token->getMaxNumber()*$sub_token->getMaxNumber()
		);
	}
	
}

?>