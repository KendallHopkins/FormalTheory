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
		
		$start_states = $fa->createStates( 3 );
		$end_states = $fa->createStates( 3 );
		
		$fa->setStartState( $start_states[0] );
		$end_states[2]->setIsFinal( TRUE );
		
		$start_loop_state = $fa->createLoopState();
		$start_states[0]->addTransition( "", $start_loop_state );
		$start_loop_state->addTransition( "", $start_states[1] );
		
		$end_loop_state = $fa->createLoopState();
		$end_states[1]->addTransition( "", $end_loop_state );
		$end_loop_state->addTransition( "", $end_states[2] );
		
		$fa_closure = $this->getFiniteAutomataClosure();
		$fa_closure( $fa, $start_states, $end_states );
		$fa->trimOrphanStates();
		
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