<?php

abstract class FormalTheory_RegularExpression_Token
{
	
	abstract function __toString();
	
	abstract function getMatches();
	
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
	
	function getFiniteAutomataClosure()
	{
		$matches = $this->getMatches();
		return function( $fa, $start_state, $end_state ) use ( $matches ) {
			$matches = array_unique( $matches );
			foreach( $matches as $match ) {
				$match_length = strlen( $match );
				if( $match_length === 0 ) {
					$start_state->addTransition( "", $end_state );
				} else {
					$current_state = $start_state;
					for( $i = 0; $i < $match_length - 1; $i++ ) {
						$next_state = $fa->createState();
						$current_state->addTransition( $match[$i], $next_state );
						$current_state = $next_state;
					}
					$current_state->addTransition( $match[$i], $end_state );
				}
			}
		};
	}
	
}

?>