<?php

class FormalTheory_RegularExpression_Tests_OptimizeTest extends PHPUnit_Framework_TestCase
{
	
	function dataProviderForTestSimpleRead()
	{
		return array(
			array( "^(1|)$", "^1?$" ),
			array( "^(|1)$", "^1?$" ),
			array( "^(|00|11)$", "^(00|11)?$" ),
			array( "(a|(b|(c|d)))", "[a-d]" ),
			array( "((a|b)|(c|d))", "[a-d]" ),
			array( "(a|(b|c)|d)", "[a-d]" ),
			array( "(((a|b)|c)|d)", "[a-d]" ),
			array( "^(a|(b|c)|d)$", "^[a-d]$" ),
			array( "((1(a|b)|c)|d)", "1[ab]|[cd]" ),
			array( "^(1*)*$", "^1*$" ),
			array( "^(1{2,3}){4,5}$", "^1{8,15}$" ),
			array( "^(1{0,3}){4,5}$", "^1{0,15}$" ),
			array( "^(1*){4,5}$", "^1*$" ),
			array( "^(1{4,5})*$" ),
			array( "^(1|2|3)$", "^[1-3]$" ),
			array( "^(1|3)$", "^[13]$" ),
			array( "^(1|3|10)$", "^(10|[13])$" ),
			array( "^(1|3|5)$", "^[135]$" ),
			array( "^(1|2|3|4|5|6|7|8|9)$", "^[1-9]$" ),
			array( "^(0|1|2|3|4|5|6|7|8|9)$", "^\d$" ),
			array( "^(1|2|3|4|5|6|7|8|9|10)$", "^(10|[1-9])$" ),
			array( "^11(11)*$", "^(11)+$" ),
			array( "^(11)*11$", "^(11)+$" ),
			array( "^1(11)*1$", "^(11)+$" ),
			array( "^0(10)*1$", "^(01)+$" ),
			array( "^01(201)*2$", "^(012)+$" ),
			array( "^0(120)*12$", "^(012)+$" ),
			array( "^(11)*111$", "^(11)+1$" ),
			array( "^(11)*1111$", "^(11){2,}$" ),
			array( "^11(11)*11$", "^(11){2,}$" ),
			array( "^1{1}$", "^1$" ),
			array( "^1{0}$", "^$" ),
			array( "^(1{0}|1{1})$", "^1?$" ),
			array( "^(123|135)$", "^1(23|35)$" ),
			array( "^(335|125)$", "^(33|12)5$" ),
			array( "^(1*25*|1*35*|1*45*)$", "^1*[2-4]5*$" ),
			array( "^(1|11|1{3,})?$", "^1*$" ),
			array( "^(1{4,6}|1{3,5})$", "^1{3,6}$" ),
			array( "^1{4,6}1{3,5}$", "^1{7,11}$" ),
			array( "^1{2}1{3}1*$", "^1{5,}$" ),
			array( "^0[^]*$", "^0" ),
			array( "^[^]*0$", "0$" ),
			array( "^0[^]+$", "^0[^]" ),
			array( "^[^]+0$", "[^]0$" ),
			array( "^[^]*0[^]*$", "0" ),
			array( "^[^]*$", "" ),
			array( "^[^]+$", "[^]" ),
			array( "^[^]+[^]+$", "[^]{2}" ),
			array( "^(1|[23])$", "^[1-3]$" ),
			//unoptimizable
			array( "^(11)*$" ),
			array( "^10(01)*$" ),
			array( "^(01)*10$" ),
			array( "^0(01)*1$" ),
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
		$optimizer = new FormalTheory_RegularExpression_Optimizer();
		$optimized_regex = $optimizer->safe( $regex );
		$this->assertSame( $expected_string, (string)$optimized_regex );
	}
	
}

?>