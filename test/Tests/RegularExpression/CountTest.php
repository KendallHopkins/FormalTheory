<?php

class FormalTheory_RegularExpression_Tests_CountTest extends PHPUnit_Framework_TestCase
{
	
	function dataProviderForTestSimpleCount()
	{
		return array(
			array( "", NULL ),
			array( "$^", 0 ),
			array( "^$", 1 ),
			array( "^1*$", NULL ),
			array( "^1?$", 2 ),
			array( "^1{0,2}$", 3 ),
			array( "^1{0,9}$", 10 ),
			array( "^(1{1,3}){1,4}$", 12 ),
			array( "^(0|1){10}$", 1024 ),
			array( "^(0|1){9,10}$", 512 + 1024 ),
		);
	}
	
	/**
	 * @dataProvider dataProviderForTestSimpleCount
	 */
	
	function testSimpleCount( $regex_string, $expected_solution_count )
	{
		$lexer = new FormalTheory_RegularExpression_Lexer();
		$dfa = $lexer->lex( $regex_string )->getDFA();
		$this->assertSame( $dfa->countSolutions(), $expected_solution_count );
	}
	
}

?>