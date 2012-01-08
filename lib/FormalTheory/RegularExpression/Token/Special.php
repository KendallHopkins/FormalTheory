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
		$special = $this->_special;
		return function( $fa, $start_states, $end_states ) use ( $special ) {
			switch( $special ) {
				case FormalTheory_RegularExpression_Token_Special::BOS:
					$start_states[0]->addTransition( "", $end_states[1] );
					break;
				case FormalTheory_RegularExpression_Token_Special::EOS:
					$start_states[1]->addTransition( "", $end_states[2] );
					$start_states[2]->addTransition( "", $end_states[2] );
					break;
				default: throw new Exception( "should be unreachable" );
			}
		};
	}
	
}

?>