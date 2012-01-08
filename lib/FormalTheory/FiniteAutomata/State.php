<?php

class FormalTheory_FiniteAutomata_State
{
	
	private $_object_hash;
	private $_finite_automata;
	private $_is_final = FALSE;
	private $_transition_lookup_array = array();
	private $_transition_ref_array = array();
	
	function __construct( FormalTheory_FiniteAutomata $finite_automata )
	{
		$this->_object_hash = spl_object_hash( $this );
		$this->_finite_automata = $finite_automata;
	}
	
	function getFiniteAutomata()
	{
		return $this->_finite_automata;
	}
	
	function getIsFinal()
	{
		return $this->_is_final;
	}
	
	function setIsFinal( $is_final )
	{
		$this->_is_final = $is_final;
	}
	
	function hasTransition( $symbol, self $next_state )
	{
		return array_key_exists( $symbol, $this->_transition_lookup_array ) && array_key_exists( $next_state->_object_hash, $this->_transition_lookup_array[$symbol] );
	}
	
	function addTransition( $symbol, self $next_state )
	{
		if( ! is_string( $symbol ) ) {
			throw new Exception( "transition symbol must be a string" );
		}
		if( $this->hasTransition( $symbol, $next_state ) ) {
			throw new Exception( "transition already exists" );
		}
		if( $symbol !== "" && ! in_array( $symbol, $this->_finite_automata->getAlphabet(), TRUE ) ) {
			throw new Exception( "symbol isn't in fa's alphabet" );
		}
		if( ! array_key_exists( $next_state->_object_hash, $this->_finite_automata->getStates() ) ) {
			throw new Exception( "state isn't in fa" );
		}
		$this->_transition_lookup_array[$symbol][$next_state->_object_hash] = $next_state;
		$next_state->_transition_ref_array[$symbol][$this->_object_hash] = $this;
	}
	
	function deleteTransition( $symbol, self $next_state )
	{
		if( ! $this->hasTransition( $symbol, $next_state ) ) {
			throw new Exception( "transition doesn't exist" );
		}
		unset( $this->_transition_lookup_array[$symbol][$next_state->_object_hash] );
		if( ! $this->_transition_lookup_array[$symbol] ) {
			unset( $this->_transition_lookup_array[$symbol] );
		}
		unset( $next_state->_transition_ref_array[$symbol][$this->_object_hash] );
		if( ! $next_state->_transition_ref_array[$symbol] ) {
			unset( $next_state->_transition_ref_array[$symbol] );
		}
	}
	
	function unlink()
	{
		foreach( $this->_transition_lookup_array as $transition_symbol => $next_states ) {
			foreach( $next_states as $next_state ) {
				$this->deleteTransition( (string)$transition_symbol, $next_state );
			}
		}
		foreach( $this->_transition_ref_array as $transition_symbol => $prev_states ) {
			foreach( $prev_states as $prev_state ) {
				$prev_state->deleteTransition( (string)$transition_symbol, $this );
			}
		}
	}
	
	function getTransitionLookupArray()
	{
		return $this->_transition_lookup_array;
	}
	
	function getTransitionRefArray()
	{
		return $this->_transition_ref_array;
	}
	
	function transitions( $symbol )
	{
		return array_key_exists( $symbol, $this->_transition_lookup_array ) ? $this->_transition_lookup_array[$symbol] : array();
	}
	
	function isDeterministic()
	{
		if( array_key_exists( "", $this->_transition_lookup_array ) ) {
			return FALSE;
		}
		foreach( $this->_transition_lookup_array as $states ) {
			if( count( $states ) > 1 ) {
				return FALSE;
			}
		}
		return TRUE;
	}
	
	function walkWithClosure( Closure $closure, $type, $direction, $init_data = NULL, $include_self = FALSE )
	{
		$action_map = array(
			FormalTheory_FiniteAutomata::WALK_TYPE_BFS => "shift",
			FormalTheory_FiniteAutomata::WALK_TYPE_DFS => "pop",
		);
		$function_map = array(
			FormalTheory_FiniteAutomata::WALK_DIRECTION_DOWN => "getTransitionLookupArray",
			FormalTheory_FiniteAutomata::WALK_DIRECTION_UP => "getTransitionRefArray",
		);
		$states = new SplDoublyLinkedList();
		
		$walk_action = $action_map[$type];
		$walk_function = $function_map[$direction];
		
		$transition_symbol = "";
		$current_state = $this;
		$data = $init_data;
		while( TRUE ) {
			if( $current_state === $this && ! $include_self ) {
				$walk_type = FormalTheory_FiniteAutomata::WALK_TRAVERSE;
				$include_self = TRUE;
			} else {
				$walk_type = $closure( $transition_symbol, $current_state, $data );
			}
			switch( $walk_type ) {
				case FormalTheory_FiniteAutomata::WALK_TRAVERSE:
					foreach( $current_state->$walk_function() as $transition_symbol => $next_states ) {
						foreach( $next_states as $next_state ) {
							$states->push( array( (string)$transition_symbol, $next_state, $data ) );
						}
					}
					break;
				case FormalTheory_FiniteAutomata::WALK_SKIP: break;
				case FormalTheory_FiniteAutomata::WALK_EXIT: return;
				default: throw new Exception( "bad type" );
			}
			if( $states->isEmpty() ) {
				break;
			}
			$next_walk = $states->$walk_action();
			list( $transition_symbol, $current_state, $data ) = $next_walk;
		};
	}
	
}

?>