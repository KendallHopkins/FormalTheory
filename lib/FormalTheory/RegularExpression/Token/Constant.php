<?php

class FormalTheory_RegularExpression_Token_Constant extends FormalTheory_RegularExpression_Token
{
	
	private $_string;
	
	static function escapeChar( $char )
	{
		if( ! is_string( $char ) || strlen( $char ) !== 1 ) {
			throw new RuntimeException( "bad string variable" );
		}
		switch( $char ) {
			case "\n": return '\n';
			case "\t": return '\t';
			case "\r": return '\r';
			case "\v": return '\v';
			case "^": case "$":
			case "*": case "+": case "?":
			case ".": case "|": case "\\":
			case "(": case ")":
			case "[": case "]":
			case "{": /* case "{" */
				return "\\{$char}";
		}
		if( ctype_print( $char ) ) {
			return $char;
		}
		$hex = dechex( ord( $char ) );
		if( strlen( $hex ) === 1 ) $hex = "0{$hex}";
		return "\\x{$hex}";
	}
	
	function __construct( $string )
	{
		if( ! is_string( $string ) || strlen( $string ) !== 1 ) {
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
		return self::escapeChar( $this->_string );
	}
	
	function getMatches()
	{
		return array( FormalTheory_RegularExpression_Match::createFromString( $this->_string ) );
	}
	
	function getFiniteAutomataClosure()
	{
		$string = $this->_string;
		return function( $fa, $start_states, $end_states ) use ( $string ) {
			$start_states[1]->addTransition( $string, $end_states[2] );
			$start_states[2]->addTransition( $string, $end_states[2] );
		};
	}
	
	protected function _compare( $token )
	{
		return $this->_string === $token->_string;
	}
	
}

?>