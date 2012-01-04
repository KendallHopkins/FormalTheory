<?php

class FormalTheory_RegularExpression_Token_Special extends FormalTheory_RegularExpression_Token
{
	
	const BOS = '^';
	const EOS = '$';
	
	private $_special;
	
	static function getList()
	{
		return array( self::BOS, self::EOS );
	}
	
	function __construct( $special )
	{
		if( ! in_array( $special, self::getList() ) ) {
			throw new RuntimeException( "bad special" );
		}
		$this->_special = $special;
	}
	
	function __toString()
	{
		return $this->_special;
	}
	
	function getMatches()
	{
		throw new RuntimeException( __FUNCTION__." can't be implemented for ".__CLASS__ );
	}
	
	function isBOS()
	{
		return $this->_special === self::BOS;
	}
	
	function isEOS()
	{
		return $this->_special === self::EOS;
	}
	
}

?>