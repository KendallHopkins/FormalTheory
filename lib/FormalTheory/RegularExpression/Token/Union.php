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
		return function( $fa, $start_state, $end_state ) use ( $regex_array ) {
			foreach( $regex_array as $regex ) {
				$fa_closure = $regex->getFiniteAutomataClosure();
				$fa_closure( $fa, $start_state, $end_state );
			}
		};
	}
	
}

?>