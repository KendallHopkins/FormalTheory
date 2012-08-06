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
		$is_wild_repeat = function( FormalTheory_RegularExpression_Token $token ) {
			return
				$token instanceof FormalTheory_RegularExpression_Token_Repeat &&
				is_null( $token->getMaxNumber() ) &&
				$token->getToken() instanceof FormalTheory_RegularExpression_Token_Set &&
				(string)$token->getToken() === "[^]";
		};
		
		$sub_tokens = $token->getTokens();
		$sub_tokens_count = count( $sub_tokens );
		
		$start_is_removable =
			$sub_tokens[0] instanceof FormalTheory_RegularExpression_Token_Special &&
			$sub_tokens[0]->isBOS() &&
			$is_wild_repeat( $sub_tokens[1] );
		
		$end_is_removeable = $sub_tokens[$sub_tokens_count-1] instanceof FormalTheory_RegularExpression_Token_Special &&
			$sub_tokens[$sub_tokens_count-1]->isEOS() &&
			$is_wild_repeat( $sub_tokens[$sub_tokens_count-2] );
		
		if( ! $start_is_removable && ! $end_is_removeable ) {
			return FALSE;
		}
		
		$wildcard_shared = $start_is_removable && $end_is_removeable && $sub_tokens_count === 3;
		
		if( $start_is_removable ) {
			$did_change = TRUE;
			array_shift( $sub_tokens );
			if( ! $wildcard_shared ) {
				$repeat = array_shift( $sub_tokens );
				if( $repeat->getMinNumber() > 0 ) {
					array_unshift( $sub_tokens, new FormalTheory_RegularExpression_Token_Repeat( $repeat->getToken(), $repeat->getMinNumber(), $repeat->getMinNumber() ) );
				}
			}
		}
		if( $end_is_removeable ) {
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