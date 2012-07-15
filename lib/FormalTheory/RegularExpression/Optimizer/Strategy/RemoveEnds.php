<?php

/*
Examples:
^0[^]*$ -> ^0
^[^]*0$ -> 0$
^0[^]+$ -> ^0[^]
^[^]+0$ -> [^]0$
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_RemoveEnds extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const COST = 0;
	const SUCCESS = 1;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = $token->getTokens();
		$sub_tokens_count = count( $sub_tokens );
		if( $sub_tokens_count < 2 ) return FALSE;
		return
			$sub_tokens[0] instanceof FormalTheory_RegularExpression_Token_Special ||
			$sub_tokens[$sub_tokens_count-1] instanceof FormalTheory_RegularExpression_Token_Special;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$did_change = FALSE;
		$sub_tokens = $token->getTokens();
		if( $sub_tokens[0] instanceof FormalTheory_RegularExpression_Token_Special &&
			$sub_tokens[0]->isBOS() &&
			$sub_tokens[1] instanceof FormalTheory_RegularExpression_Token_Repeat &&
			is_null( $sub_tokens[1]->getMaxNumber() ) &&
			$sub_tokens[1]->getToken() instanceof FormalTheory_RegularExpression_Token_Set &&
			(string)$sub_tokens[1]->getToken() === "[^]"
		) {
			$did_change = TRUE;
			array_shift( $sub_tokens );
			$repeat = array_shift( $sub_tokens );
			if( $repeat->getMinNumber() > 0 ) {
				array_unshift( $sub_tokens, new FormalTheory_RegularExpression_Token_Repeat( $repeat->getToken(), $repeat->getMinNumber(), $repeat->getMinNumber() ) );
			}
		}
		$sub_tokens_count = count( $sub_tokens );
		if( $sub_tokens[$sub_tokens_count-1] instanceof FormalTheory_RegularExpression_Token_Special &&
			$sub_tokens[$sub_tokens_count-1]->isEOS() &&
			$sub_tokens[$sub_tokens_count-2] instanceof FormalTheory_RegularExpression_Token_Repeat &&
			is_null( $sub_tokens[$sub_tokens_count-2]->getMaxNumber() ) &&
			$sub_tokens[$sub_tokens_count-2]->getToken() instanceof FormalTheory_RegularExpression_Token_Set &&
			(string)$sub_tokens[$sub_tokens_count-2]->getToken() === "[^]"
		) {
			$did_change = TRUE;
			array_pop( $sub_tokens );
			$repeat = array_pop( $sub_tokens );
			if( $repeat->getMinNumber() > 0 ) {
				array_push( $sub_tokens, new FormalTheory_RegularExpression_Token_Repeat( $repeat->getToken(), $repeat->getMinNumber(), $repeat->getMinNumber() ) );
			}
		}
		if( ! $did_change ) {
			return FALSE;
		}
		return new FormalTheory_RegularExpression_Token_Regex( $sub_tokens, FALSE );
	}
	
}

?>