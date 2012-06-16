<?php

class FormalTheory_RegularExpression_Token_Set extends FormalTheory_RegularExpression_Token
{
	
	private $_char_array;
	private $_is_positive;
	
	function __construct( array $char_array, $is_positive )
	{
		foreach( $char_array as $char ) {
			if( ! is_string( $char ) || strlen( $char ) !== 1 ) {
				throw new Exception( "non-char found in char array" );
			}
		}
		$this->_char_array = array_unique( $char_array );
		$this->_is_positive = (bool)$is_positive;
	}
	
	function __toString()
	{
		$string = "";
		$offset_array = array_map( "ord", $this->charArray() );
		if( count( $offset_array ) === 128 ) return ".";
		$current_run = array();
		$last_offset = NULL;
		foreach( $offset_array as $offset ) {
			if( $offset-1 !== $last_offset ) {
				if( count( $current_run ) > 2 ) {
					$first_offset = array_shift( $current_run );
					$string .=
						FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $first_offset ) ).
						"-".
						FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $last_offset ) );
				} else {
					$string .= implode( "", array_map( array( "FormalTheory_RegularExpression_Token_Constant", "escapeChar" ), array_map( "chr", $current_run ) ) );
				}
				$current_run = array();
			}
			$current_run[] = $offset;
			$last_offset = $offset;
		}
		if( count( $current_run ) > 2 ) {
			$first_offset = array_shift( $current_run );
			$string .=
				FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $first_offset ) ).
				"-".
				FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $last_offset ) );
		} else {
			$string .= implode( "", array_map( array( "FormalTheory_RegularExpression_Token_Constant", "escapeChar" ), array_map( "chr", $current_run ) ) );
		}
		return "[{$string}]";
	}
	
	function charArray()
	{
		return $this->_is_positive
			? $this->_char_array
			: array_diff( array_map( "chr", range( 0, 127 ) ), $this->_char_array );
	}
	
	function getMatches()
	{
		return array_map( function( $char ) {
			return FormalTheory_RegularExpression_Match::createFromString( $char );
		}, $this->charArray() );
	}
	
	function getFiniteAutomataClosure()
	{
		$char_array = $this->charArray();
		return function( $fa, $start_states, $end_states ) use ( $char_array ) {
			foreach( $char_array as $char ) {
				$start_states[1]->addTransition( $char, $end_states[2] );
				$start_states[2]->addTransition( $char, $end_states[2] );
			}
		};
	}
	
}

?>