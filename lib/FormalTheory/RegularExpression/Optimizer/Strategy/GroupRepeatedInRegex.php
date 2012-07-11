<?php

/*
Examples:
11 -> 1{2}
11111 -> 1{5}
121212 -> (12){3}
*/

//TODO: implement stride

class FormalTheory_RegularExpression_Optimizer_Strategy_GroupRepeatedInRegex extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const COST = 0;
	const SUCCESS = .5;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = $token->getTokens();
		return count( $sub_tokens ) > 1;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = $token->getTokens();
		$sub_tokens_count = count( $sub_tokens );
		$is_equal = FALSE;
		for( $i = 0; $i + 1 < $sub_tokens_count; $i++ ) {
			if( $sub_tokens[$i]->compare( $sub_tokens[$i+1] ) ) {
				$is_equal = TRUE;
				break;
			}
		}
		if( ! $is_equal ) return FALSE;
		$start_offset = $i;
		$end_offset = NULL;
		for( $i++; $i + 1 < $sub_tokens_count; $i++ ) {
			if( ! $sub_tokens[$i]->compare( $sub_tokens[$i+1] ) ) {
				$end_offset = $i;
				break;
			}
		}
		if( is_null( $end_offset ) ) {
			$end_offset = $sub_tokens_count - 1;
		}
		return new FormalTheory_RegularExpression_Token_Regex( array_merge(
			array_slice( $sub_tokens, 0, $start_offset ),
			array( new FormalTheory_RegularExpression_Token_Repeat( $sub_tokens[$start_offset], $end_offset-$start_offset+1, $end_offset-$start_offset+1 ) ),
			array_slice( $sub_tokens, $end_offset + 1 )
		), FALSE );
	}
	
}

?>