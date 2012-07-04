<?php

class FormalTheory_RegularExpression_Optimizer_Strategy_GroupRepeatsInRegex extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const IS_SAFE = TRUE;
	const COST = 0;
	const SUCCESS = .5;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = $token->getTokens();
		//TODO: check for repeats next to each other
		return count( $sub_tokens ) > 1;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = $token->getTokens();
		$sub_tokens_count = count( $sub_tokens );
		$is_equal = FALSE;
		for( $i = 0; $i + 1 < $sub_tokens_count; $i++ ) {
			if( $sub_tokens[$i] instanceof FormalTheory_RegularExpression_Token_Repeat &&
				$sub_tokens[$i+1] instanceof FormalTheory_RegularExpression_Token_Repeat &&
				$sub_tokens[$i]->getToken()->compare( $sub_tokens[$i+1]->getToken() )
			) {
				$is_equal = TRUE;
				$min_value = $sub_tokens[$i]->getMinNumber() + $sub_tokens[$i+1]->getMinNumber();
				$max_value = is_null( $sub_tokens[$i]->getMaxNumber() ) || is_null( $sub_tokens[$i+1]->getMaxNumber() )
					? NULL
					: $sub_tokens[$i]->getMaxNumber() + $sub_tokens[$i+1]->getMaxNumber();
				break;
			}
		}
		if( ! $is_equal ) return FALSE;
		$start_offset = $i;
		$end_offset = NULL;
		for( $i++; $i + 1 < $sub_tokens_count; $i++ ) {
			if( $sub_tokens[$i] instanceof FormalTheory_RegularExpression_Token_Repeat &&
				$sub_tokens[$i+1] instanceof FormalTheory_RegularExpression_Token_Repeat &&
				$sub_tokens[$i]->getToken()->compare( $sub_tokens[$i+1]->getToken() )
			) {
				$min_value += $sub_tokens[$i+1]->getMinNumber();
				if( ! is_null( $max_value ) ) {
					$max_value = is_null( $sub_tokens[$i+1]->getMaxNumber() ) ? NULL : $sub_tokens[$i+1]->getMaxNumber();
				}
			} else {
				$end_offset = $i;
				break;
			}
		}
		if( is_null( $end_offset ) ) {
			$end_offset = $sub_tokens_count - 1;
		}
		return new FormalTheory_RegularExpression_Token_Regex( array_merge(
			array_slice( $sub_tokens, 0, $start_offset ),
			array( new FormalTheory_RegularExpression_Token_Repeat( $sub_tokens[$start_offset]->getToken(), $min_value, $max_value ) ),
			array_slice( $sub_tokens, $end_offset + 1 )
		), FALSE );
	}
	
}

?>