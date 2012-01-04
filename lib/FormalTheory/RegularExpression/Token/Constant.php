<?php

class FormalTheory_RegularExpression_Token_Constant extends FormalTheory_RegularExpression_Token
{
	
	private $_string;
	
	function __construct( $string )
	{
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
		return array( $this->_string );
	}
	
}

?>