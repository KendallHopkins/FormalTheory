<?php

class FormalTheory_RegularExpression_Tests_ToStringTest extends PHPUnit_Framework_TestCase
{
	
	function dataProviderForTestSimpleRead()
	{
		return array(
			array( "ab" ),
			array( "(ab)" ),
			array( "a|b", "(a|b)" ),
			array( "(a|b)" ),
			array( "[ab]" ),
			array( "[ab]+" ),
			array( "[ab]*" ),
			array( "[ab]{2,}" ),
			array( "\d" ),
			array( "\D" ),
			array( "\w" ),
			array( "\W" ),
			array( "\s" ),
			array( "\S" ),
			array( '\n' ),
			array( '\r' ),
			array( '\t' ),
			array( '\v' ),
			array( '.' ),
			array( "(a|(b|(c|d)))" ),
		);
	}
	
	/**
	 * @dataProvider dataProviderForTestSimpleRead
	 */
	
	function testSimpleRead( $regex_string, $expected_string = NULL )
	{
		if( is_null( $expected_string ) ) $expected_string = $regex_string;
		$lexer = new FormalTheory_RegularExpression_Lexer();
		$regex = $lexer->lex( $regex_string );
		$this->assertSame( $expected_string, (string)$regex );
	}
	
}

?>