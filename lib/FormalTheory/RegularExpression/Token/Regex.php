<?php

class FormalTheory_RegularExpression_Token_Regex extends FormalTheory_RegularExpression_Token
{
	
	private $_token_array;
	private $_is_sub_regex;
	
	function __construct( array $token_array, $is_sub_regex )
	{
		$this->_token_array = array_values( $token_array );
		$this->_is_sub_regex = $is_sub_regex;
	}
	
	function __toString()
	{
		return implode( "", array_map( function( $token ) {
			if( $token instanceof FormalTheory_RegularExpression_Token_Regex || $token instanceof FormalTheory_RegularExpression_Token_Union ) {
				return "($token)";
			}
			return (string)$token;
		}, $this->_token_array ) );
	}
	
	function getMatches()
	{
		$matches = array( FormalTheory_RegularExpression_Match::createFromString( "" ) );
		foreach( $this->_token_array as $token ) {
			$matches = self::crossProductMatchArray( $matches, $token->getMatches() );
		}
		if( ! $this->_is_sub_regex ) {
			$matches = array_map( function( $match ) {
				return $match->getMatch();
			}, $matches );
		}
		return $matches;
	}
	
	function getFiniteAutomataClosure()
	{
		$tokens = $this->_token_array;
		return function( $fa, $start_state, $end_state ) use ( $tokens ) {
			$token_count = count( $tokens );
			$states = $token_count > 1 ? array_merge( array( $start_state ), $fa->createStates( $token_count - 1 ), array( $end_state ) ) : array( $start_state, $end_state );
			for( $i = 0; $i < $token_count; $i++ ) {
				$fa_closure = $tokens[$i]->getFiniteAutomataClosure();
				$fa_closure( $fa, $states[$i], $states[$i+1] );
			}
		};
	}
	
}

?>