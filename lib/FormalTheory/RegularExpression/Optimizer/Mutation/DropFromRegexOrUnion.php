<?php

class FormalTheory_RegularExpression_Optimizer_Mutation_DropFromRegexOrUnion extends FormalTheory_RegularExpression_Optimizer_Mutation
{
	
	const COST = 0;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex", "FormalTheory_RegularExpression_Token_Union" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return count( $token->getTokens() ) > 0;
	}
	
	function countOptions( FormalTheory_RegularExpression_Token $token )
	{
		return count( $token->getTokens() );
	}
	
	function run( FormalTheory_RegularExpression_Token $token, $option_index )
	{
		$sub_tokens = $token->getTokens();
		unset( $sub_tokens[$option_index] );
		if( $token instanceof FormalTheory_RegularExpression_Token_Regex ) {
			return new FormalTheory_RegularExpression_Token_Regex( $sub_tokens, FALSE );
		} else if( $token instanceof FormalTheory_RegularExpression_Token_Union ) {
			return new FormalTheory_RegularExpression_Token_Union( $sub_tokens, FALSE );
		} else {
			throw new RuntimeException( "unreachable" );
		}
	}
	
}

?>