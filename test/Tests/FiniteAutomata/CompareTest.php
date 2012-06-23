<?php

class FormalTheory_FiniteAutomata_Tests_CompareTest extends PHPUnit_Framework_TestCase
{
	
	function testBasic()
	{
		$dfa1 = new FormalTheory_FiniteAutomata( array( "0" ) );
		list( $s1, $s2 ) = $dfa1->createStates( 5 );
		$dfa1->setStartState( $s1 );
		$s1->addTransition( "0", $s2 );
		$s2->addTransition( "0", $s1 );
		$s1->setIsFinal( TRUE );
		
		$dfa2 = new FormalTheory_FiniteAutomata( array( "0" ) );
		list( $s1, $s2, $s3, $s4 ) = $dfa2->createStates( 5 );
		$dfa2->setStartState( $s1 );
		$s1->addTransition( "0", $s2 );
		$s2->addTransition( "0", $s3 );
		$s3->addTransition( "0", $s4 );
		$s4->addTransition( "0", $s1 );
		$s1->setIsFinal( TRUE );
		$s3->setIsFinal( TRUE );
		
		$this->assertTrue( $dfa1->compare( $dfa2 ) );
		$s3->setIsFinal( FALSE );
		$this->assertFalse( $dfa1->compare( $dfa2 ) );
	}
	
}

?>