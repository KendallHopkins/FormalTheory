<?php

class FormalTheory_RegularExpression_Token_Repeat extends FormalTheory_RegularExpression_Token
{
	
	private $_token;
	private $_first_number;
	private $_second_number;
	
	function __construct( $token, $first_number, $second_number = NULL )
	{
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
		} else if( $this->_first_number === 0 && $this->_second_number === 1 ) {
			return $this->_token."?";
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
		if( is_null( $this->_second_number ) ) {
			throw new RuntimeException( "unbounded repeat found" );
		}
		$output = array();
		$current_repeat_matches = array( "" );
		$token_matches = $this->_token->getMatches();
		for( $i = 0; $i < $this->_second_number; $i++ ) {
			if( $i >= $this->_first_number ) {
				$output = array_merge( $output, $current_repeat_matches );
			}
			$current_repeat_matches = FormalTheory_RegularExpression_Utility::crossProductStrinArrays( $current_repeat_matches, $token_matches );
		}
		$output = array_merge( $output, $current_repeat_matches );
		return $output;
	}
	
	function getFiniteAutomataClosure()
	{
		$token = $this->_token;
		$first_number = $this->_first_number;
		$second_number = $this->_second_number;
		return function( $fa, $start_state, $end_state ) use ( $token, $first_number, $second_number ) {
			$fa_closure = $token->getFiniteAutomataClosure();
			$current_state = $start_state;
			$is_finite = ! is_null( $second_number );
			for( $i = 0; $i < $first_number; $i++ ) {
				$next_state = $fa->createState();
				$fa_closure( $fa, $current_state, $next_state );
				$current_state = $next_state;
			}
			if( $is_finite ) {
				for( ; $i < $second_number + 1; $i++ ) {
					$current_state->addTransition( "", $end_state );
					if( $i < $second_number ) {
						$next_state = $fa->createState();
						$fa_closure( $fa, $current_state, $next_state );
						$current_state = $next_state;
					}
				}
			} else {
				$fa_closure( $fa, $current_state, $current_state );
				$current_state->addTransition( "", $end_state );
			}
		};
	}
	
}

?>