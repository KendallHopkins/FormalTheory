<?php

/*
Examples:
12()34 -> 1234
12(a)34 -> 12a34
12(a*)34 -> 12a*34
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_UnwrapSimpleRegexOrUnion extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const COST = 0;
	const SUCCESS = 1;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex", "FormalTheory_RegularExpression_Token_Union" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return count( $token->getTokens() ) === 1;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = $token->getTokens();
		return $sub_tokens[0];
	}
	
}

?>