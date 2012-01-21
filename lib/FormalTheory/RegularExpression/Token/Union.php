<?php

class FormalTheory_RegularExpression_Token_Union extends FormalTheory_RegularExpression_Token
{
	
	private $_regex_array;
	
	function __construct( array $regex_array )
	{
		$this->_regex_array = $regex_array;
	}
	
	function __toString()
	{
		return implode( "|", $this->_regex_array );
	}
	
	function getRegexArray()
	{
		return $this->_regex_array;
	}
	
	function getMatches()
	{
		return call_user_func_array( "array_merge", array_map( function( $regex ) {
			return $regex->getMatches();
		}, $this->_regex_array ) );
	}
	
	function getFiniteAutomataClosure()
	{
		$regex_array = $this->_regex_array;
		return function( $fa, $start_states, $end_states ) use ( $regex_array ) {
			foreach( $regex_array as $regex ) {
				$current_start_starts = $fa->createStates( 4 );
				$current_end_starts = $fa->createStates( 4 );
				$start_states[0]->addTransition( "", $current_start_starts[0] );
				$start_states[1]->addTransition( "", $current_start_starts[1] );
				$start_states[2]->addTransition( "", $current_start_starts[2] );
				$start_states[3]->addTransition( "", $current_start_starts[3] );
				$current_end_starts[0]->addTransition( "", $end_states[0] );
				$current_end_starts[1]->addTransition( "", $end_states[1] );
				$current_end_starts[2]->addTransition( "", $end_states[2] );
				$current_end_starts[3]->addTransition( "", $end_states[3] );
				
				$fa_closure = $regex->getFiniteAutomataClosure();
				$fa_closure( $fa, $current_start_starts, $current_end_starts );
			}
		};
	}
	
}

?>