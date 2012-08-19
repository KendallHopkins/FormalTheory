<?php

class FormalTheory_RegularExpression_Token_Set extends FormalTheory_RegularExpression_Token
{
	
	private $_char_array;
	private $_is_positive;
	
	static function getGroups()
	{
		static $groups = NULL;
		if( is_null( $groups ) ) {
			$number_range = array( "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" );
			$word_range = array_merge(
				range( "A", "Z" ), range( "a", "z" ),
				$number_range, array( "_" )
			);
			$space_range = array( " ", "\t", "\n", "\r", "\f" );
			$groups = array(
				"w" => $word_range,
				"d" => $number_range,
				"s" => $space_range,
			);
		}
		return $groups;
	}
	
	static function getInverseGroups()
	{
		static $inverse_groups = NULL;
		if( is_null( $inverse_groups ) ) {
			$full_range = array_map( "chr", range( 0, 127 ) );
			$inverse_groups = array();
			foreach( self::getGroups() as $group_char => $group_symbols ) {
				$inverse_groups[strtoupper( $group_char )] = array_diff( $full_range, $group_symbols );
			}
			$inverse_groups = array_reverse( $inverse_groups, TRUE );
		}
		return $inverse_groups;
	}
	
	static function newFromGroupChar( $group_char )
	{
		$groups = self::getGroups();
		if( array_key_exists( $group_char, $groups ) ) {
			return new self( $groups[$group_char], TRUE );
		}
		if( array_key_exists( strtolower( $group_char ), $groups ) ) {
			return new self( $groups[strtolower( $group_char )], FALSE );
		}
		throw new RuntimeException( "bad \$group_char" );
	}
	
	function __construct( array $char_array, $is_positive )
	{
		foreach( $char_array as $char ) {
			if( ! is_string( $char ) || strlen( $char ) !== 1 ) {
				throw new Exception( "non-char found in char array" );
			}
		}
		sort( $char_array );
		$this->_char_array = array_unique( $char_array );
		$this->_is_positive = (bool)$is_positive;
	}
	
	function _toString()
	{
		$char_array = $this->charArray();
		if( count( $char_array ) === 127 && ! in_array( "\n", $char_array ) ) return ".";
		
		$normal_set = $this->___toString( $char_array, FALSE );
		$inverse_set = $this->___toString( $char_array, TRUE );
		return strlen( $normal_set ) <= strlen( $inverse_set ) ? $normal_set : $inverse_set;
	}
	
	private function ___toString( array $char_array, $should_inverse )
	{
		$string = "";
		if( $should_inverse ) {
			$char_array = array_diff( array_map( "chr", range( 0, 127 ) ), $char_array );
		}
		$all_groups = self::getInverseGroups() + self::getGroups();
		foreach( $all_groups as $group_char => $group_symbols ) {
			if( count( array_intersect( $char_array, $group_symbols ) ) === count( $group_symbols ) ) {
				$char_array = array_diff( $char_array, $group_symbols );
				$string .= "\\{$group_char}";
			}
		}
		
		$offset_array = array_map( "ord", $char_array );
		$current_run = array();
		$last_offset = NULL;
		foreach( $offset_array as $offset ) {
			if( $offset-1 !== $last_offset ) {
				if( count( $current_run ) > 2 ) {
					$first_offset = array_shift( $current_run );
					$string .=
						FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $first_offset ) ).
						"-".
						FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $last_offset ) );
				} else {
					$string .= implode( "", array_map( array( "FormalTheory_RegularExpression_Token_Constant", "escapeChar" ), array_map( "chr", $current_run ) ) );
				}
				$current_run = array();
			}
			$current_run[] = $offset;
			$last_offset = $offset;
		}
		if( count( $current_run ) > 2 ) {
			$first_offset = array_shift( $current_run );
			$string .=
				FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $first_offset ) ).
				"-".
				FormalTheory_RegularExpression_Token_Constant::escapeChar( chr( $last_offset ) );
		} else {
			$string .= implode( "", array_map( array( "FormalTheory_RegularExpression_Token_Constant", "escapeChar" ), array_map( "chr", $current_run ) ) );
		}
		if( $should_inverse ) {
			return "[^{$string}]";
		} else {
			$is_simple = strlen( $string ) === 1 || preg_match( "/^\\\\(".implode( "|", array_keys( $all_groups ) ).")$/", $string );
			return $is_simple ? $string : "[{$string}]";
		}
	}
	
	function charArray()
	{
		return $this->_is_positive
			? $this->_char_array
			: array_diff( array_map( "chr", range( 0, 127 ) ), $this->_char_array );
	}
	
	function getMatches()
	{
		return array_map( function( $char ) {
			return FormalTheory_RegularExpression_Match::createFromString( $char );
		}, $this->charArray() );
	}
	
	function getFiniteAutomataClosure()
	{
		$char_array = $this->charArray();
		return function( $fa, $start_states, $end_states ) use ( $char_array ) {
			foreach( $char_array as $char ) {
				$start_states[1]->addTransition( $char, $end_states[2] );
				$start_states[2]->addTransition( $char, $end_states[2] );
			}
		};
	}
	
	protected function _compare( $token )
	{
		return $this->charArray() === $token->charArray();
	}
	
}

?>