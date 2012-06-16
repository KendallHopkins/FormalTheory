<?php

class FormalTheory_RegularExpression_Token_Repeat extends FormalTheory_RegularExpression_Token
{
	
	private $_token;
	private $_first_number;
	private $_second_number;
	
	function __construct( $token, $first_number, $second_number = NULL )
	{
		if( ! is_null( $second_number ) && $first_number > $second_number ) {
			throw new RuntimeException( "invalid repeat found" );
		}
		$this->_token = $token;
		$this->_first_number = $first_number;
		$this->_second_number = $second_number;
	}
	
	function __toString()
	{
		if( is_null( $this->_second_number ) ) {
			switch( $this->_first_number ) {
				case 0: return $this->_token."*";
				case 1: return $this->_token."+";
			}
		} else if( $this->_second_number === 1 ) {
			switch( $this->_first_number ) {
				case 0: return $this->_token."?";
				case 1: return $this->_token;
			}
		} else if( $this->_first_number && $this->_second_number === 0 ) {
			return "";
		}
		return $this->_token.'{'.$this->_first_number.','.$this->_second_number.'}';
	}
	
	function getToken()
	{
		return $this->_token;
	}
	
	function getMinNumber()
	{
		return $this->_first_number;
	}
	
	function getMaxNumber()
	{
		return $this->_second_number;
	}
	
	function getMatches()
	{
		$empty_match = FormalTheory_RegularExpression_Match::createFromString( "" );
		$token_matches = $this->_token->getMatches();
		$output = array();
		if( is_null( $this->_second_number ) ) {
			$converges = 0 === count( array_filter( self::crossProductMatchArray( $token_matches, $token_matches ), function( $match ) use ( $empty_match ) {
				return ! $match->isEqual( $empty_match );
			} ) );
			if( ! $converges ) {
				throw new RuntimeException( "unbounded repeat found" );
			}
			$output = $token_matches;
			if( $this->_first_number === 0 ) {
				$output[] = $empty_match;
			}
		} else {
			$current_repeat_matches = array( $empty_match );
			for( $i = 0; $i < $this->_second_number && $current_repeat_matches; $i++ ) {
				if( $i >= $this->_first_number ) {
					$output = array_merge( $output, $current_repeat_matches );
				}
				$current_repeat_matches = self::crossProductMatchArray( $current_repeat_matches, $token_matches );
			}
			$output = array_merge( $output, $current_repeat_matches );
		}
		
		return $output;
	}
	
	function getFiniteAutomataClosure()
	{
		$token = $this->_token;
		$first_number = $this->_first_number;
		$second_number = $this->_second_number;
		return function( $fa, $start_states, $end_states ) use ( $token, $first_number, $second_number ) {
			$fa_closure = $token->getFiniteAutomataClosure();
			$current_states = $start_states;
			$is_finite = ! is_null( $second_number );
			for( $i = 0; $i < $first_number; $i++ ) {
				$next_states = $fa->createStates( 4 );
				$fa_closure( $fa, $current_states, $next_states );
				$current_states = $next_states;
			}
			if( $is_finite ) {
				for( ; $i < $second_number + 1; $i++ ) {
					$current_states[0]->addTransition( "", $end_states[0] );
					$current_states[1]->addTransition( "", $end_states[1] );
					$current_states[2]->addTransition( "", $end_states[2] );
					$current_states[3]->addTransition( "", $end_states[3] );
					if( $i < $second_number ) {
						$next_states = $fa->createStates( 4 );
						$fa_closure( $fa, $current_states, $next_states );
						$current_states = $next_states;
					}
				}
			} else {
				$fa_closure( $fa, $current_states, $current_states );
				$current_states[0]->addTransition( "", $end_states[0] );
				$current_states[1]->addTransition( "", $end_states[1] );
				$current_states[2]->addTransition( "", $end_states[2] );
				$current_states[3]->addTransition( "", $end_states[3] );
			}
		};
	}
	
}

?>