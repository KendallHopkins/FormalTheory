<?php

class FormalTheory_RegularExpression_Optimizer_Mutation_TweakSet extends FormalTheory_RegularExpression_Optimizer_Mutation
{
	
	const COST = 0;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Set" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return TRUE;
	}
	
	function countOptions( FormalTheory_RegularExpression_Token $token )
	{
		return 128;
	}
	
	function run( FormalTheory_RegularExpression_Token $token, $option_index )
	{
		$char_array = $token->charArray();
		$tweaked_char = chr( $option_index );
		$offset = array_search( $tweaked_char, $char_array, TRUE );
		if( $offset === FALSE ) {
			$char_array[] = $tweaked_char;
		} else {
			unset( $char_array[$offset] );
		}
		return new FormalTheory_RegularExpression_Token_Set( $char_array, TRUE );
	}
	
}

?>