<?php

class FormalTheory_RegularExpression_Lexer
{
	
	private $_regex_pieces;
	private $_current_offset;
	
	function lex( $regex_string )
	{
		$this->_regex_pieces = str_split( $regex_string );
		$this->_current_offset = 0;
		$output = $this->_lex( FALSE, TRUE );
		if( $output instanceof FormalTheory_RegularExpression_Token_Union ) {
			$output = new FormalTheory_RegularExpression_Token_Regex( array( $output ), FALSE );
		}
		if( $this->_current_offset !== count( $this->_regex_pieces ) )
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected end" );
		
		return $output;
	}
	
	private function _lex( $in_group = FALSE, $is_outer = FALSE )
	{
		$last_is_escape = FALSE;
		$tokens = array();
		if( $this->_current_offset >= count( $this->_regex_pieces ) ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected end" );
		}
		for( ; $this->_current_offset < count( $this->_regex_pieces ); $this->_current_offset++ ) {
			$current_piece = $this->_regex_pieces[$this->_current_offset];
			if( ! $last_is_escape ) {
				switch( $current_piece ) {
					case '\\':
						$last_is_escape = TRUE;
						break;
					case '(':
						$this->_current_offset++;
						$tokens[] = $this->_lex( TRUE );
						$this->_current_offset--;
						break;
					case ')':
						if( ! $in_group ) {
							throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected symbol ')'" );
						}
						$this->_current_offset++;
						$in_group = FALSE;
						break 2;
					case '[':
						$this->_current_offset++;
						$tokens[] = $this->_lex_set();
						break;
					case '{':
						$this->_current_offset++;
						$repeat_info = $this->_lex_repeat();
						if( $repeat_info ) {
							list( $min, $max ) = $repeat_info;
							self::_isReadyForRepeat( $tokens );
							$tokens[] = new FormalTheory_RegularExpression_Token_Repeat( array_pop( $tokens ), $min, $max );
						} else {
							$tokens[] = new FormalTheory_RegularExpression_Token_Constant( '{' );
						}
						$this->_current_offset--;
						break;
					case '.':
						$tokens[] = new FormalTheory_RegularExpression_Token_Set( array( "\n" ), FALSE );
						break;
					case '*':
						self::_isReadyForRepeat( $tokens );
						$tokens[] = new FormalTheory_RegularExpression_Token_Repeat( array_pop( $tokens ), 0, NULL );
						break;
					case '+':
						self::_isReadyForRepeat( $tokens );
						$tokens[] = new FormalTheory_RegularExpression_Token_Repeat( array_pop( $tokens ), 1, NULL );
						break;
					case '?':
						self::_isReadyForRepeat( $tokens );
						$tokens[] = new FormalTheory_RegularExpression_Token_Repeat( array_pop( $tokens ), 0, 1 );
						break;
					case '^':
					case '$':
						$tokens[] = new FormalTheory_RegularExpression_Token_Special( $current_piece );
						break;
					case '|':
						$tokens[] = "|";
						break;
					default:
						$tokens[] = new FormalTheory_RegularExpression_Token_Constant( $current_piece );
						break;
				}
			} else {
				$number_range = array( "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" );
				$word_range = array_merge(
					range( "A", "Z" ), range( "a", "z" ),
					$number_range, array( "_" ),
					array_map( "chr", range( 192, 214 ) ),
					array_map( "chr", range( 216, 246 ) ),
					array_map( "chr", range( 248, 255 ) )
				);
				switch( $current_piece ) {
					case "w":
						$tokens[] = new FormalTheory_RegularExpression_Token_Set( $word_range, TRUE );
						break;
					case "W":
						$tokens[] = new FormalTheory_RegularExpression_Token_Set( $word_range, FALSE );
						break;
					case "d":
						$tokens[] = new FormalTheory_RegularExpression_Token_Set( $number_range, TRUE );
						break;
					case "D":
						$tokens[] = new FormalTheory_RegularExpression_Token_Set( $number_range, FALSE );
						break;
					case "s":
						$tokens[] = new FormalTheory_RegularExpression_Token_Set( array( " ", "\t", "\n", "\r", "\f", chr( 160 ) ), TRUE );
						break;
					case "S":
						$tokens[] = new FormalTheory_RegularExpression_Token_Set( array( " ", "\t", "\n", "\r", "\f", chr( 160 ) ), FALSE );
						break;
					case "t":
						$tokens[] = new FormalTheory_RegularExpression_Token_Constant( "\t" );
						break;
					case "r":
						$tokens[] = new FormalTheory_RegularExpression_Token_Constant( "\r" );
						break;
					case "n":
						$tokens[] = new FormalTheory_RegularExpression_Token_Constant( "\n" );
						break;
					case "x":
						$tokens[] = new FormalTheory_RegularExpression_Token_Constant(
							$this->_lex_hex()
						);
						break;
					case "b": case "B": throw new RuntimeException( "not implemented" );
					default:
						$tokens[] = new FormalTheory_RegularExpression_Token_Constant( $current_piece );
						break;
				}
				$last_is_escape = FALSE;
			}
		}
		
		if( $in_group ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected end" );
		}
		
		if( in_array( '|', $tokens, TRUE ) ) {
			$regex_array = array();
			while( ( $offset = array_search( '|', $tokens, TRUE ) ) !== FALSE ) {
				$current_tokens = array_splice( $tokens, 0, $offset + 1 );
				array_pop( $current_tokens );
				$regex_array[] = new FormalTheory_RegularExpression_Token_Regex( $current_tokens, TRUE );
			}
			$regex_array[] = new FormalTheory_RegularExpression_Token_Regex( $tokens, TRUE );
			return new FormalTheory_RegularExpression_Token_Union( $regex_array );
		} else {
			return new FormalTheory_RegularExpression_Token_Regex( $tokens, ! $is_outer );
		}
	}
	
	private function _lex_hex()
	{
		if( $this->_current_offset+2 >= count( $this->_regex_pieces ) ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected end" );
		}
		
		if( ! ctype_xdigit( $this->_regex_pieces[$this->_current_offset+1] ) ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected non-hex character: ".$this->_regex_pieces[$this->_current_offset+1] );
		}
		if( ! ctype_xdigit( $this->_regex_pieces[$this->_current_offset+2] ) ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected non-hex character: ".$this->_regex_pieces[$this->_current_offset+2] );
		}
		
		$symbol = chr( hexdec( $this->_regex_pieces[$this->_current_offset+1].$this->_regex_pieces[$this->_current_offset+2] ) );
		$this->_current_offset += 2;
		return $symbol;	
	}
	
	private function _lex_set()
	{
		if( $this->_current_offset >= count( $this->_regex_pieces ) ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpectedly found end while in set" );
		}
		$is_negative = $this->_regex_pieces[$this->_current_offset] === "^";
		if( $is_negative ) $this->_current_offset++;
		$tokens = array();
		$last_is_escape = FALSE;
		$current_piece = NULL;
		for( ; $this->_current_offset < count( $this->_regex_pieces ); $this->_current_offset++ ) {
			$current_piece = $this->_regex_pieces[$this->_current_offset];
			if( ! $last_is_escape ) {
				switch( $current_piece ) {
					case '\\':
						$last_is_escape = TRUE;
						break;
					case '-':
						$prev_token = array_pop( $tokens );
						$this->_current_offset++;
						if( $this->_regex_pieces[$this->_current_offset] === '\\' ) {
							$this->_current_offset++;
							$next_token = $this->_lex_set_getEscaped( $this->_regex_pieces[$this->_current_offset] );
						} else {
							$next_token = $this->_regex_pieces[$this->_current_offset];
						}
						foreach( range( $prev_token, $next_token ) as $range_token ) {
							$tokens[] = (string)$range_token;
						}
						break;
					case ']':
						break 2;
					default:
						$tokens[] = $current_piece;
						break;
				}
			} else {
				$tokens[] = $this->_lex_set_getEscaped( $current_piece );
				$last_is_escape = FALSE;
			}
		}
		if( $current_piece !== ']' ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpectedly found end while in set" );
		}
		return new FormalTheory_RegularExpression_Token_Set( $tokens, ! $is_negative );
	}
	
	private function _lex_set_getEscaped( $char )
	{
		switch( $char ) {
			case 't': return "\t";
			case 'r': return "\r";
			case 'n': return "\n";
			case 'x': return $this->_lex_hex();
		}
		return $char;
	}
	
	private function _lex_repeat()
	{
		$start_offset = $this->_current_offset;
		$numbers = array( "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" );
		$first_number = NULL;
		$second_number = NULL;
		if( $this->_current_offset >= count( $this->_regex_pieces ) ) {
			$this->_current_offset = $start_offset;
			return NULL;
		}
		while( in_array( $this->_regex_pieces[$this->_current_offset], $numbers, TRUE ) ) {
			$first_number .= $this->_regex_pieces[$this->_current_offset];
			$this->_current_offset++;
		}
		if( is_null( $first_number ) ) {
			$this->_current_offset = $start_offset;
			return NULL;
		}
		if( ! in_array( $this->_regex_pieces[$this->_current_offset], array( ",", "}" ), TRUE ) ) {
			$this->_current_offset = $start_offset;
			return NULL;
		}
		if( $this->_regex_pieces[$this->_current_offset] === "," ) {
			$this->_current_offset++;
			while( in_array( $this->_regex_pieces[$this->_current_offset], $numbers, TRUE ) ) {
				$second_number .= $this->_regex_pieces[$this->_current_offset];
				$this->_current_offset++;
			}
			if( $this->_regex_pieces[$this->_current_offset] !== "}" ) {
				$this->_current_offset = $start_offset;
				return NULL;
			}
		} else {
			$second_number = $first_number;
		}
		$this->_current_offset++;
		settype( $first_number, "int" );
		if( ! is_null( $second_number ) ) {
			settype( $second_number, "int" );
			if( $first_number > $second_number ) {
				throw new FormalTheory_RegularExpression_Exception_Lex( "repeat found with min higher than max" );
			}
		}
		
		return array( $first_number, $second_number );
	}
	
	static private function _isReadyForRepeat( array $tokens )
	{
		if( ! $tokens ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected repeat" );
		}
		$last_token = end( $tokens );
		if( $last_token instanceof FormalTheory_RegularExpression_Token_Repeat ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected repeat" );
		}
		if( $last_token instanceof FormalTheory_RegularExpression_Token_Special ) {
			throw new FormalTheory_RegularExpression_Exception_Lex( "unexpected repeat" );
		}
	}
	
}

?>