<?php

class FormalTheory_RegularExpression_Token_Regex extends FormalTheory_RegularExpression_Token
{
	
	private $_token_array;
	private $_is_sub_regex;
	
	function __construct( array $token_array, $is_sub_regex )
	{
		foreach( $token_array as $token ) {
			if( ! $token instanceof FormalTheory_RegularExpression_Token ) {
				throw new RuntimeException( "regex can only take token: ".var_export( $token, TRUE ) );
			}
		}
		$this->_token_array = array_values( $token_array );
		$this->_is_sub_regex = $is_sub_regex;
	}
	
	function getTokens()
	{
		return $this->_token_array;
	}
	
	function __toString()
	{
		return implode( "", array_map( function( $token ) {
			if( $token instanceof FormalTheory_RegularExpression_Token_Union ) {
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
		return function( $fa, $start_states, $end_states ) use ( $tokens ) {
			$token_count = count( $tokens );
			if( $token_count === 0 ) {
				$start_states[0]->addTransition( "", $end_states[0] );
				$start_states[1]->addTransition( "", $end_states[1] );
				$start_states[2]->addTransition( "", $end_states[2] );
				$start_states[3]->addTransition( "", $end_states[3] );
			} else {
				$states = array( $start_states );
				for( $i = 0; $i < $token_count - 1; $i++ ) {
					$states[] = $fa->createStates( 4 );
				}
				$states[] = $end_states;
				for( $i = 0; $i < $token_count; $i++ ) {
					$fa_closure = $tokens[$i]->getFiniteAutomataClosure();
					$fa_closure( $fa, $states[$i], $states[$i+1] );
				}
			}
		};
	}
	
	protected function _compare( $token )
	{
		if( count( $this->_token_array ) !== count( $token->_token_array ) ) {
			return FALSE;
		}
		for( $i = 0; $i < count( $this->_token_array ); $i++ ) {
			if( ! $this->_token_array[$i]->compare( $token->_token_array[$i] ) ) {
				return FALSE;
			}
		}
		return TRUE;
	}
	
}

?>