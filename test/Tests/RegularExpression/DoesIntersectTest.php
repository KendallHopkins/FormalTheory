<?php

class FormalTheory_RegularExpression_Tests_DoesIntersectTest extends PHPUnit_Framework_TestCase
{
	
	function dataProviderForTestDoesIntersect()
	{
		return array(
			array( '^1{6,10}$', '^1{2,6}$', TRUE ),
			array( '^1{6,10}$', '^1{2,5}$', FALSE ),
			array( '^1{6,10}$', '^0{2,6}$', FALSE ),
			array( '^1*$', '^1{100}$', TRUE ),
			array( '^1(11)*$', '^(11)*$', FALSE ),
			array( '^(11)*1$', '^(11)*$', FALSE ),
			array( '^1(11)*$', '^(11111)*$', TRUE ),
			array( '^.*111.*$', '^.*000.*$', TRUE ),
			array( '1', '0', TRUE ),
			array( '^[a-z]*$', '^[A-Z]*$', TRUE ),
			array( '^.*1$', '^.*0$', FALSE ),
			array( '1$', '0$', FALSE ),
			array( '^1', '0$', TRUE ),
			array( '^1', '^0', FALSE ),
			array( '^(1|2){10}3(4|5){10}$', '^1.*2.*3.*4.*5$', TRUE ),
			array( '^[1-9][0-9]*(\.[0-9]+)?$', '^3.14159265$', TRUE ),
			array( '^[1-9][0-9]*(\.[0-9]+)?$', '^42$', TRUE ),
			array( '1^0', '^.*$', FALSE ),
			array( '1$0', '^.*$', FALSE ),
			array( '1^0', '', FALSE ),
			array( '1$0', '', FALSE ),
		);
	}
	
	/**
	 * @dataProvider dataProviderForTestDoesIntersect
	 */
	
	function testDoesIntersect( $regex_string_1, $regex_string_2, $expected_does_intersect )
	{
		$lexer = new FormalTheory_RegularExpression_Lexer();
		$nfa1 = $lexer->lex( $regex_string_1 )->getNFA();
		$nfa2 = $lexer->lex( $regex_string_2 )->getNFA();
		$this->assertSame( $expected_does_intersect, FormalTheory_FiniteAutomata::intersection( $nfa1, $nfa2 )->validSolutionExists() );
		$this->assertSame( $expected_does_intersect, FormalTheory_FiniteAutomata::intersection( $nfa2, $nfa1 )->validSolutionExists() );
	}
	
}

?>