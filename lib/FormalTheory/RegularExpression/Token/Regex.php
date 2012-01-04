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
	
	function getTokens()
	{
		$temp_token_array = $this->_token_array;
		if( ! $this->_is_sub_regex ) {
			$first_token = array_shift( $temp_token_array );
			$last_token = array_pop( $temp_token_array );
			if( ! $first_token instanceof FormalTheory_RegularExpression_Token_Special || ! $first_token->isBOS() ) {
				throw new RuntimeException( "regex doesn't start with a BOS token" );
			}
			if( ! $last_token instanceof FormalTheory_RegularExpression_Token_Special || ! $last_token->isEOS() ) {
				throw new RuntimeException( "regex doesn't end with a EOS token" );
			}
		}
		return $temp_token_array;
	}
	
	function getMatches()
	{
		$matches = array( "" );
		foreach( $this->getTokens() as $token ) {
			if( $token instanceof FormalTheory_RegularExpression_Token_Special ) {
				throw new RuntimeException( "unexpected special found in middle of regex" );
			}
			$matches = FormalTheory_RegularExpression_Utility::crossProductStrinArrays( $matches, $token->getMatches() );
		}
		return $matches;
	}
	
	function getFiniteAutomataClosure()
	{
		$tokens = $this->getTokens();
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