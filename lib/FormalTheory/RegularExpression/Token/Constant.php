<?php

class FormalTheory_RegularExpression_Token_Constant extends FormalTheory_RegularExpression_Token
{
	
	private $_string;
	
	function __construct( $string )
	{
		if( ! is_string( $string ) && strlen( $string ) !== 1 ) {
			throw new RuntimeException( "bad string variable" );
		}
		$this->_string = $string;
	}
	
	function getString()
	{
		return $this->_string;
	}
	
	function __toString()
	{
		return $this->_string;
	}
	
	function getMatches()
	{
		return array( FormalTheory_RegularExpression_Match::createFromString( $this->_string ) );
	}
	
	function getFiniteAutomataClosure()
	{
		$string = $this->_string;
		return function( $fa, $start_state, $end_state ) use ( $string ) {
			$start_state->addTransition( $string, $end_state );
		};
	}
	
}

?>