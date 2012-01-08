<?php

abstract class FormalTheory_RegularExpression_Token
{
	
	abstract function __toString();
	
	abstract function getMatches();
	
	static function crossProductMatchArray( $match_array1, $match_array2 )
	{
		$output = array();
		foreach( $match_array1 as $match1 ) {
			foreach( $match_array2 as $match2 ) {
				$result = FormalTheory_RegularExpression_Match::join( $match1, $match2 );
				if( $result === FALSE ) continue;
				$output[] = $result;
			}
		}
		return $output;
	}
	
	abstract function getFiniteAutomataClosure();
	
	function getNFA()
	{
		$fa = new FormalTheory_FiniteAutomata();
		$fa->setAlphabet( array_map( "chr", range( 0, 255 ) ) );
		list( $start, $end ) = $fa->createStates( 2 );
		$end->setIsFinal( TRUE );
		$fa->setStartState( $start );
		$fa_closure = $this->getFiniteAutomataClosure();
		$fa_closure( $fa, $start, $end );
		return $fa;
	}
	
	function getDFA()
	{
		$nfa = $this->getNFA();
		$dfa = $nfa->isDeterministic() ? $nfa : FormalTheory_FiniteAutomata::determinize( $nfa );
		$dfa->rewriteDuplicateStates();
		return $dfa;
	}
	
}

?>