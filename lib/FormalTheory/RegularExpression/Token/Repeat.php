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
		if( $this->_first_number === 0 && $this->_second_number === 0 ) {
			return "";
		}
		$should_be_grouped = $this->_token instanceof FormalTheory_RegularExpression_Token_Regex || $this->_token instanceof FormalTheory_RegularExpression_Token_Union || $this->_token instanceof FormalTheory_RegularExpression_Token_Repeat;
		$token_string = $should_be_grouped ? "({$this->_token})" : (string)$this->_token;
		if( is_null( $this->_second_number ) ) {
			switch( $this->_first_number ) {
				case 0: return "{$token_string}*";
				case 1: return "{$token_string}+";
			}
		} else if( $this->_second_number === 1 ) {
			switch( $this->_first_number ) {
				case 0: return "{$token_string}?";
				case 1: return $token_string;
			}
		} else if( $this->_first_number === $this->_second_number ) {
			if( strlen( $token_string )*$this->_first_number < strlen( $token_string )+3 ) {
				return str_repeat( $token_string, $this->_first_number );
			}
			return "{$token_string}{{$this->_first_number}}";
		}
		return "{$token_string}{{$this->_first_number},{$this->_second_number}}";
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
			$add_lambda_transition = function( $from_states, $to_states ) {
				$from_states[0]->addTransition( "", $to_states[0] );
				$from_states[1]->addTransition( "", $to_states[1] );
				$from_states[2]->addTransition( "", $to_states[2] );
				$from_states[3]->addTransition( "", $to_states[3] );
			};
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
					$add_lambda_transition( $current_states, $end_states );
					if( $i < $second_number ) {
						$next_states = $fa->createStates( 4 );
						$fa_closure( $fa, $current_states, $next_states );
						$current_states = $next_states;
					}
				}
			} else {
				$isolation_states = $fa->createStates( 4 );
				$add_lambda_transition( $current_states, $isolation_states );
				$fa_closure( $fa, $isolation_states, $isolation_states );
				$add_lambda_transition( $isolation_states, $end_states );
			}
		};
	}
	
	protected function _compare( $token )
	{
		return
			$this->_first_number === $token->_first_number &&
			$this->_second_number === $token->_second_number &&
			$this->_token->compare( $token->_token );
	}
	
}

?>