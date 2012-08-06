<?php

class FormalTheory_RegularExpression_Optimizer_Mutation_ChangeConstant extends FormalTheory_RegularExpression_Optimizer_Mutation
{
	
	const COST = 0;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Constant" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return TRUE;
	}
	
	function countOptions( FormalTheory_RegularExpression_Token $token )
	{
		return 127;
	}
	
	function run( FormalTheory_RegularExpression_Token $token, $option_index )
	{
		if( $option_index >= ord( $token->getString() ) ) $option_index++;
		return new FormalTheory_RegularExpression_Token_Constant( chr( $option_index ) );
	}
	
}

?>