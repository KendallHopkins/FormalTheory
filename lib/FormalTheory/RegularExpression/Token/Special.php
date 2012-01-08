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
		$lookup = array(
			self::BOS => "createFromBOS",
			self::EOS => "createFromEOS"
		);
		$function_name = $lookup[$this->_special];
		return array( FormalTheory_RegularExpression_Match::$function_name() );
	}
	
	function isBOS()
	{
		return $this->_special === self::BOS;
	}
	
	function isEOS()
	{
		return $this->_special === self::EOS;
	}
	
	function getFiniteAutomataClosure()
	{
		return function( $fa, $start_state, $end_state ) {
			$start_state->addTransition( "", $end_state );
		};
	}
	
}

?>