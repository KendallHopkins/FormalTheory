<?php

class FormalTheory_RegularExpression_Optimizer_Mutation_TweakRepeat extends FormalTheory_RegularExpression_Optimizer_Mutation
{
	
	const COST = 0;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Repeat" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		return TRUE;
	}
	
	function countOptions( FormalTheory_RegularExpression_Token $token )
	{
		return count( self::_getCombinations( $token ) );
	}
	
	function run( FormalTheory_RegularExpression_Token $token, $option_index )
	{
		$pairs = self::_getCombinations( $token );
		return new FormalTheory_RegularExpression_Token_Repeat( $token->getToken(), $pairs[$option_index][0], $pairs[$option_index][1] );
	}
	
	private static function _getCombinations( FormalTheory_RegularExpression_Token $token )
	{
		$min = $token->getMinNumber();
		$max = $token->getMaxNumber();
		$min_array = array( 0, 1 );
		if( $min > 0 ) $min_array[] = $min - 1;
		$min_array[] = $min;
		$min_array[] = $min + 1;
		$max_array = array( 1, NULL );
		if( ! is_null( $max ) ) {
			if( $max > 1 ) $max_array[] = $max - 1;
			if( $max > 0 ) $max_array[] = $max;
			$max_array[] = $max + 1;			
		}
		$min_array = array_unique( $min_array );
		$max_array = array_unique( $max_array );
		$pairs = array();
		foreach( $min_array as $current_min ) {
			foreach( $max_array as $current_max ) {
				if( is_null( $current_max ) || $current_min <= $current_max ) {
					$pairs[] = array( $current_min, $current_max );
				}
			}
		}
		return $pairs;
	}
	
}

?>