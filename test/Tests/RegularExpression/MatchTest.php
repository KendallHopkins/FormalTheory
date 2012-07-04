<?php

class FormalTheory_RegularExpression_Tests_MatchTest extends PHPUnit_Framework_TestCase
{
	
	function dataProviderForTestSimpleRead()
	{
		$full_range = array_map( "chr", range( 0, 127 ) );
		$number_range = array( "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" );
		$word_range = array_merge(
			range( "A", "Z" ), range( "a", "z" ),
			$number_range, array( "_" )
		);
		return array(
			array( "^ab$", array( "ab" ) ),
			array( "^(ab)$", array( "ab" ) ),
			array( "(^ab$)", array( "ab" ) ),
			array( "(^1)+1$", array( "11" ) ),
			array( "^1()*$", array( "1" ) ),
			array( "^1(|)*$", array( "1" ) ),
			array( "^1(2$|$)", array( "12", "1" ) ),
			array( "^(ab|ba)$", array( "ab", "ba" ) ),
			array( "^(ab|(b|c)a)$", array( "ab", "ba", "ca" ) ),
			array( "^(ab|ba){0,2}$", array( "", "ab", "ba", "abab", "abba", "baab", "baba" ) ),
			array( "^(ab|ba){1,2}$", array( "ab", "ba", "abab", "abba", "baab", "baba" ) ),
			array( "^(ab|ba){2}$", array( "abab", "abba", "baab", "baba" ) ),
			array( "^hello?$", array( "hell", "hello" ) ),
			array( "^(0|1){3}$", array( "000", "001", "010", "011", "100", "101", "110", "111" ) ),
			array( "^[1-9][0-9]{0,1}$", array_map( function( $input ) { return (string)$input; }, range( 1, 99 ) ) ),
			array( '^\n$', array( "\n" ) ),
			array( '^\r$', array( "\r" ) ),
			array( '^\t$', array( "\t" ) ),
			array( '^[\\\\\\]a\\-]$', array( "\\", "]", "a", "-" ) ), //the regex is actually '^[\\\]a\-]$' after PHP string parsing
			array( '^[\\n-\\r]$', array_map( "chr", range( 10, 13 ) ) ),
			array( '^\*$', array( "*" ) ),
			array( '^$', array( "" ) ),
			array( '^.$', array_diff( $full_range, array( "\n" ) ) ),
			array( '^[a-z]$', range( "a", "z" ) ),
			array( '^[^a-z]$', array_diff( $full_range, range( "a", "z" ) ) ),
			array( '^\x61$', array( "a" ) ),
			array( '^[\x61\x62]$', array( "a", "b" ) ),
			array( '^[\x61-\x64]$', array( "a", "b", "c", "d" ) ),
			array( '^\w$', $word_range ),
			array( '^\W$', array_diff( $full_range, $word_range ) ),
			array( '^\d$', $number_range ),
			array( '^\D$', array_diff( $full_range, $number_range ) ),
			array( '^\s$', array( " ", "\t", "\n", "\r", "\f" ) ),
			array( '^\S$', array_diff( $full_range, array( " ", "\t", "\n", "\r", "\f" ) ) ),
			array( '^(1{2}){2}$', array( "1111" ) ),
			array( '^(1{2}){2$', array( "11{2" ) ),
			array( '^{2{2}$', array( "{22" ) ),
			array( '^{,1}$', array( "{,1}" ) ),
			array( '^{2, 2}$', array( "{2, 2}" ) ),
			array( '^{2,{2}}$', array( "{2,,}" ) ),
			array( '^\\{2}$', array( "{2}" ) ),
			array( '^\\\\{2}$', array( "\\\\" ) ),
			array( "^^$", array( "" ) ),
			array( "^$$", array( "" ) ),
			array( "^$ ", array() ),
			array( "^[- ]$", array( "-", " " ) ),
			array( "^[ -]$", array( "-", " " ) ),
		);
	}
	
	/**
	 * @dataProvider dataProviderForTestSimpleRead
	 */
	
	function testSimpleRead( $regex_string, $expected_matches_array )
	{
		$lexer = new FormalTheory_RegularExpression_Lexer();
		$actualy_matches_array = $lexer->lex( $regex_string )->getMatches();
		
		$extra = array_diff( $actualy_matches_array, $expected_matches_array );
		$missing = array_diff( $expected_matches_array, $actualy_matches_array );
		$this->assertSame( array(), array_values( array_map( "ord", $extra ) ), "extra matches" );
		$this->assertSame( array(), array_values( array_map( "ord", $missing ) ), "missing matches" );
		
		$false_matches = array_diff( $expected_matches_array, preg_filter( "~".$regex_string."~", '$0', $expected_matches_array ) );
		$this->assertSame( array(), array_values( array_map( "ord", $false_matches ) ), "Regex '$regex_string' had false matches." );
	}
	
	function dataProviderForTestSimpleReadFail()
	{
		return array(
			array( "", "RuntimeException", "regex doesn't start with a BOS token" ),
			array( "$", "RuntimeException", "regex doesn't start with a BOS token" ),
			array( "^", "RuntimeException", "regex doesn't end with a EOS token" ),
			array( "^a|b$", "RuntimeException", "regex doesn't end with a EOS token" ),
			array( "b$|^a", "RuntimeException", "regex doesn't start with a BOS token" ),
			array( "(^1)*1$", "RuntimeException", "regex doesn't start with a BOS token" ),
			array( "(^1)+1", "RuntimeException", "regex doesn't end with a EOS token" ),
			array( "^1(2|$)", "RuntimeException", "regex doesn't end with a EOS token" ),
			array( "^0+$", "RuntimeException", "unbounded repeat found" ),
			array( "^0{1,}$", "RuntimeException", "unbounded repeat found" ),
			array( "^0*$", "RuntimeException", "unbounded repeat found" ),
			array( "^0{0,}$", "RuntimeException", "unbounded repeat found" ),
		);
	}
	
	/**
	 * @dataProvider dataProviderForTestSimpleReadFail
	 */
	
	function testSimpleReadFail( $regex_string, $exception_class, $exception_message )
	{
		$lexer = new FormalTheory_RegularExpression_Lexer();
		$regex = $lexer->lex( $regex_string );
		
		$this->setExpectedException( $exception_class, $exception_message );
		$regex->getMatches();
	}
	
}

?>