<?php

class FormalTheory_RegularExpression_Optimizer_Mutation_SwapInRegex extends FormalTheory_RegularExpression_Optimizer_Mutation
{
	
	const COST = 0;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return count( $token->getTokens() ) > 1;
	}
	
	function countOptions( FormalTheory_RegularExpression_Token $token )
	{
		$count = count( $token->getTokens() );
		return (($count-1)*$count)/2;
	}
	
	function run( FormalTheory_RegularExpression_Token $token, $option_index )
	{
		$sub_tokens = $token->getTokens();
		$count = count( $sub_tokens ) - 1;
		$first_offset = 0;
		while( $option_index + $first_offset >= $count ) {
			$option_index -= $count - $first_offset++;
		}
		$second_offset = $first_offset + $option_index + 1;
		list( $sub_tokens[$second_offset], $sub_tokens[$first_offset] ) = array( $sub_tokens[$first_offset], $sub_tokens[$second_offset] );
		return new FormalTheory_RegularExpression_Token_Regex( $sub_tokens, FALSE );
	}
	
}

?>