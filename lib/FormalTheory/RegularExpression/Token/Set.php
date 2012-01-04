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
		return "[".implode( "", $this->charArray() )."]";
	}
	
	function charArray()
	{
		return $this->_is_positive
			? $this->_char_array
			: array_diff( array_map( "chr", range( 0, 255 ) ), $this->_char_array );
	}
	
	function getMatches()
	{
		return $this->charArray();
	}
	
}

?>