<?php

class FormalTheory_RegularExpression_Tests_LexTest extends PHPUnit_Framework_TestCase
{
	
	function dataProviderForTestLex()
	{
		return array(
			array(
				"",
				new FormalTheory_RegularExpression_Token_Regex( array(), FALSE )
			),
			array(
				"()",
				new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Regex( array() ,TRUE ) ), FALSE )
			),
			array(
				"^$",
				new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Special( "^" ), new FormalTheory_RegularExpression_Token_Special( "$" ) ), FALSE )
			),
			array(
				"^a|b$",
				new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Union( array(
					new FormalTheory_RegularExpression_Token_Regex( array(
						new FormalTheory_RegularExpression_Token_Special( "^" ), new FormalTheory_RegularExpression_Token_Constant( "a" )
					), TRUE ), new FormalTheory_RegularExpression_Token_Regex( array(
						new FormalTheory_RegularExpression_Token_Constant( "b" ), new FormalTheory_RegularExpression_Token_Special( "$" )
					), TRUE )
				) ) ), FALSE )
			),
			array(
				"aa|bb",
				new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Union( array(
					new FormalTheory_RegularExpression_Token_Regex( array(
						new FormalTheory_RegularExpression_Token_Constant( "a" ), new FormalTheory_RegularExpression_Token_Constant( "a" )
					), TRUE ), new FormalTheory_RegularExpression_Token_Regex( array(
						new FormalTheory_RegularExpression_Token_Constant( "b" ), new FormalTheory_RegularExpression_Token_Constant( "b" )
					), TRUE )
				) ) ), FALSE )
			),
			array(
				"a|b{1,2}",
				new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Union( array(
					new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Constant( "a" ) ), TRUE ), new FormalTheory_RegularExpression_Token_Regex( array(
						new FormalTheory_RegularExpression_Token_Repeat( new FormalTheory_RegularExpression_Token_Constant( "b" ), 1, 2 )
					), TRUE )
				) ) ), FALSE )
			),
			array(
				"{",
				new FormalTheory_RegularExpression_Token_Regex( array(
					new FormalTheory_RegularExpression_Token_Constant( "{" )
				), FALSE )
			)
		);
	}
	
	/**
	 * @dataProvider dataProviderForTestLex
	 */
	
	function testLex( $regex_string, FormalTheory_RegularExpression_Token_Regex $expected_regex_object )
	{
		$lexer = new FormalTheory_RegularExpression_Lexer();
		$this->assertEquals( $expected_regex_object, $lexer->lex( $regex_string ) );
	}
	
	function dataProviderForTestLexFailure()
	{
		return array(
			array( '(', "FormalTheory_RegularExpression_Exception_Lex", "unexpected end" ),
			array( '( ', "FormalTheory_RegularExpression_Exception_Lex", "unexpected end" ),
			array( ' (', "FormalTheory_RegularExpression_Exception_Lex", "unexpected end" ),
			array( ')', "FormalTheory_RegularExpression_Exception_Lex", "unexpected symbol ')'" ),
			array( ') ', "FormalTheory_RegularExpression_Exception_Lex", "unexpected symbol ')'" ),
			array( ' )', "FormalTheory_RegularExpression_Exception_Lex", "unexpected symbol ')'" ),
			array( '[', "FormalTheory_RegularExpression_Exception_Lex", "unexpectedly found end while in set" ),
			array( '[ ', "FormalTheory_RegularExpression_Exception_Lex", "unexpectedly found end while in set" ),
			array( '^\x', "FormalTheory_RegularExpression_Exception_Lex", "unexpected end" ),
			array( '^\xa', "FormalTheory_RegularExpression_Exception_Lex", "unexpected end" ),
			array( '^\xxx$', "FormalTheory_RegularExpression_Exception_Lex", "unexpected non-hex character" ),
			array( '^\x', "FormalTheory_RegularExpression_Exception_Lex", "unexpected end" ),
			array( '^\xa', "FormalTheory_RegularExpression_Exception_Lex", "unexpected end" ),
			array( '^\xxx$', "FormalTheory_RegularExpression_Exception_Lex", "unexpected non-hex character" ),
			array( '^\xax$', "FormalTheory_RegularExpression_Exception_Lex", "unexpected non-hex character" ),
			array( '^{1}$', "FormalTheory_RegularExpression_Exception_Lex", "unexpected repeat" ),
			array( '^{1,2}$', "FormalTheory_RegularExpression_Exception_Lex", "unexpected repeat" ),
			array( '^1{2,1}$', "FormalTheory_RegularExpression_Exception_Lex", "repeat found with min higher than max" ),
			array( '^*$', "FormalTheory_RegularExpression_Exception_Lex", "unexpected repeat" ),
			array( '*', "FormalTheory_RegularExpression_Exception_Lex", "unexpected repeat" ),
			array( '^1{2}{2}$', "FormalTheory_RegularExpression_Exception_Lex", "unexpected repeat" ),
		);
	}
	
	/**
	 * @dataProvider dataProviderForTestLexFailure
	 */
	
	function testLexFailure( $regex, $exception_class, $exception_message )
	{
		$this->setExpectedException( $exception_class, $exception_message );
		$lexer = new FormalTheory_RegularExpression_Lexer();
		$lexer->lex( $regex );
	}
	
}

?>