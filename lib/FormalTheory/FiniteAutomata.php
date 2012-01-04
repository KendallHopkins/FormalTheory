<?php

class FormalTheory_FiniteAutomata
{
	
	const WALK_TYPE_BFS = "bfs";
	const WALK_TYPE_DFS = "dfs";
	
	const WALK_TRAVERSE = "traverse";
	const WALK_SKIP = "skip";
	const WALK_EXIT = "exit";
	
	private $_alphabet = array();
	private $_states = array();
	private $_start_state = NULL;
	
	function __clone()
	{
		reset( $this->_states );
		$old_fa = $this->_states ? current( $this->_states )->getFiniteAutomata() : NULL;
		$this->_states = array();
		$this->_start_state = $old_fa ? $this->importAutomata( $old_fa ) : NULL;
	}
	
	function setAlphabet( array $alphabet )
	{
		$this->_alphabet = $alphabet;
	}
	
	function getAlphabet()
	{
		return $this->_alphabet;
	}
	
	function createState()
	{
		$state = new FormalTheory_FiniteAutomata_State( $this );
		return $this->_states[spl_object_hash($state)] = $state;
	}
	
	function createStates( $n )
	{
		$output = array();
		for( $i = 0; $i < $n; $i++ ) {
			$output[] = self::createState();
		}
		return $output;
	}
	
	function setStartState( FormalTheory_FiniteAutomata_State $state )
	{
		$this->_start_state = $state;
	}
	
	function getStartState()
	{
		return $this->_start_state;
	}
	
	function getStates()
	{
		return $this->_states;
	}
	
	function count()
	{
		return count( $this->_states );
	}
	
	function display()
	{
		$i = 1;
		$symbol_lookup = array();
		$symbol_lookup[spl_object_hash( $this->_start_state )] = "S".($this->_start_state->getIsFinal() ? "*" : "");
		foreach( $this->_states as $state ) {
			if( $state === $this->_start_state ) continue;
			$symbol_lookup[spl_object_hash( $state )] = ($i++).($state->getIsFinal() ? "*" : "");
		}
		$output = "";
		foreach( $this->_states as $state ) {
			$transition_lookup_array = $state->getTransitionLookupArray();
			if( $transition_lookup_array ) {
				foreach( $transition_lookup_array as $transition_symbol => $next_states ) {
					foreach( $next_states as $next_state ) {
						$output .= $symbol_lookup[spl_object_hash( $state )]." -> ".($transition_symbol === "" ? "λ" : $transition_symbol)." -> ".$symbol_lookup[spl_object_hash( $next_state )].PHP_EOL;
					}
				}
			} else {
				$output .= $symbol_lookup[spl_object_hash( $state )]." (no transitions)".PHP_EOL;
			}
		}
		return $output;
	}
	
	private function walkWithClosure( Closure $closure, $type, $init_data = NULL )
	{
		if( ! $this->getStartState() ) {
			throw new Exception( "no start state" );
		}
		$this->getStartState()->walkWithClosure( $closure, $type, $init_data, TRUE );
	}
	
	function isMatch( $symbol_array )
	{
		$is_match = FALSE;
		$this->walkWithClosure( function( $transition_symbol, $current_state, &$data ) use ( $symbol_array, &$is_match ) {
			if( is_null( $data ) ) {
				$data = array_reverse( $symbol_array );
			}
			if( $transition_symbol !== "" && $transition_symbol !== array_pop( $data ) ) {
				return FormalTheory_FiniteAutomata::WALK_SKIP;
			}
			if( ! $data ) {
				if( $current_state->getIsFinal() ) {
					$is_match = TRUE;	
					return FormalTheory_FiniteAutomata::WALK_EXIT;
				}
				return FormalTheory_FiniteAutomata::WALK_SKIP;
			}
			return FormalTheory_FiniteAutomata::WALK_TRAVERSE;
		}, self::WALK_TYPE_DFS );
		return $is_match;
	}
	
	function trimOrphanStates()
	{
		$visited_states = new SplObjectStorage();
		
		$this->walkWithClosure( function( $transition_symbol, $next_state ) use ( $visited_states ) {
			if( $visited_states->contains( $next_state ) ) {
				return FormalTheory_FiniteAutomata::WALK_SKIP;
			}
			$visited_states->attach( $next_state );
			return FormalTheory_FiniteAutomata::WALK_TRAVERSE;
		}, self::WALK_TYPE_BFS );
		
		foreach( $this->_states as $key => $state ) {
			if( ! $visited_states->contains( $state ) ) {
				unset( $this->_states[$key] );
			}
		}
	}
	
	function rewriteDuplicateStates()
	{
		do {
			$replace_array = array();
			$state_lookup = array();
			foreach( $this->_states as $state_hash => $state ) {
				$is_final = $state->getIsFinal();
				$transition_lookup_array = $state->getTransitionLookupArray();
				$transition_lookup_array = array_map( "array_keys", $transition_lookup_array );
				array_map( "sort", $transition_lookup_array );
				ksort( $transition_lookup_array );
				$lookup_string = serialize( array( "is_final" => $is_final, "transitions" => $transition_lookup_array ) );
				if( array_key_exists( $lookup_string, $state_lookup ) ) {
					$replace_array[$state_hash] = $state_lookup[$lookup_string];
				} else {
					$state_lookup[$lookup_string] = $state;
				}
			}
			unset( $state_lookup );
			if( $replace_array ) {
				foreach( $replace_array as $replaced_state_hash => $replacement_state ) {
					$replaced_state = $this->_states[$replaced_state_hash];
					foreach( $replaced_state->getTransitionRefArray() as $transition_symbol => $ref_states ) {
						foreach( $ref_states as $ref_state ) {
							$ref_state->deleteTransition( (string)$transition_symbol, $replaced_state );
							$ref_state->addTransition( (string)$transition_symbol, $replacement_state );
						}
					}
					unset( $this->_states[$replaced_state_hash] );
				}
				$start_state_hash = spl_object_hash( $this->getStartState() );
				if( array_key_exists( $start_state_hash, $replace_array ) ) {
					$this->setStartState( $replace_array[$start_state_hash] );
				}
			}
		} while( $replace_array );
	}
	
	function addFailureState()
	{
		$failure_state = NULL;
		$this_ = $this;
		$get_failure_state = function() use ( &$failure_state, $this_ ) {
			if( is_null( $failure_state ) ) {
				$failure_state = $this_->createState();
				foreach( $this_->getAlphabet() as $symbol ) {
					$failure_state->addTransition( $symbol, $failure_state );
				}
			}
			return $failure_state;
		};
		foreach( $this->_states as $state ) {
			foreach( $this->_alphabet as $symbol ) {
				if( ! $state->transitions( $symbol ) ) {
					$state->addTransition( $symbol, $get_failure_state() );
				}
			}
		}
	}
	
	function validSolutionExists()
	{
		$valid_solution_exists = FALSE;
		$visited_states = new SplObjectStorage();
		
		$this->walkWithClosure( function( $transition_symbol, $next_state ) use ( $visited_states, &$valid_solution_exists ) {
			if( $next_state->getIsFinal() ) {
				$valid_solution_exists = TRUE;
				return FormalTheory_FiniteAutomata::WALK_EXIT;
			}
			if( $visited_states->contains( $next_state ) ) {
				return FormalTheory_FiniteAutomata::WALK_SKIP;
			}
			$visited_states->attach( $next_state );
			return FormalTheory_FiniteAutomata::WALK_TRAVERSE;
		}, self::WALK_TYPE_BFS );
		
		return $valid_solution_exists;
	}
	
	function importAutomata( self $finite_automata )
	{
		if( $finite_automata->getAlphabet() !== $this->getAlphabet() ) {
			throw new Exception( "different alphabet" );
		}
		$translation_lookup = array();
		foreach( $finite_automata->_states as $state ) {
			$new_state = $this->createState();
			$new_state->setIsFinal( $state->getIsFinal() );
			$translation_lookup[spl_object_hash( $state )] = $new_state;
		}
		foreach( $finite_automata->_states as $state ) {
			$new_state = $translation_lookup[spl_object_hash( $state )];
			foreach( $state->getTransitionLookupArray() as $transition_symbol => $next_states ) {
				foreach( $next_states as $next_state ) {
					$new_state->addTransition( (string)$transition_symbol, $translation_lookup[spl_object_hash( $next_state )] );
				}
			}
		}
		return $translation_lookup[spl_object_hash( $finite_automata->getStartState() )];
	}
	
	function isDeterministic()
	{
		foreach( $this->_states as $state ) {
			if( ! $state->isDeterministic() ) {
				return FALSE;
			}
		}
		return TRUE;
	}
	
	static function determinize( self $finite_automata )
	{
		if( $finite_automata->isDeterministic() ) {
			throw new Exception( "already deterministic" );
		}
		$fa = new self();
		$fa->setAlphabet( $finite_automata->getAlphabet() );
		
		$reachable_without_transition_array = array_map( function( $state ) {
			$reachable_without_transition = array( $state );
			$state->walkWithClosure( function( $transition_symbol, $next_state ) use ( &$reachable_without_transition ) {
				if( in_array( $next_state, $reachable_without_transition, TRUE ) || $transition_symbol !== "" ) {
					return FormalTheory_FiniteAutomata::WALK_SKIP;
				}
				$reachable_without_transition[spl_object_hash( $next_state )] = $next_state;
				return FormalTheory_FiniteAutomata::WALK_TRAVERSE;
			}, FormalTheory_FiniteAutomata::WALK_TYPE_BFS );
			return $reachable_without_transition;
		}, $finite_automata->_states );
		
		$reachable_by_transition_array = array_map( function( $state ) use ( $reachable_without_transition_array ) {
			$first_level_transition_lookup_arrays = array_map( function( $state ) {
				$transition_lookup_array = $state->getTransitionLookupArray();
				unset( $transition_lookup_array[""] );
				return $transition_lookup_array;
			}, $reachable_without_transition_array[spl_object_hash( $state )] );
			$reachable_by_symbol = array();
			foreach( $first_level_transition_lookup_arrays as $first_level_transition_lookup_array ) {
				foreach( $first_level_transition_lookup_array as $transition_symbol => $next_states ) {
					if( ! array_key_exists( $transition_symbol, $reachable_by_symbol ) ) {
						$reachable_by_symbol[$transition_symbol] = array();
					}
					foreach( array_keys( $next_states ) as $next_state_hash ) {
						$reachable_by_symbol[$transition_symbol] = array_merge( $reachable_by_symbol[$transition_symbol], $reachable_without_transition_array[$next_state_hash] );
					}
				}
			}
			return $reachable_by_symbol;
		}, $finite_automata->_states );
		
		$calculate_reachable = function( array $states ) use ( $reachable_by_transition_array ) {
			$merged_reachable_states = array();
			foreach( $states as $state ) {
				foreach( $reachable_by_transition_array[spl_object_hash( $state )] as $transition_symbol => $reachable_states ) {
					$merged_reachable_states[$transition_symbol] = array_key_exists( $transition_symbol, $merged_reachable_states )
						? array_merge( $merged_reachable_states[$transition_symbol], $reachable_states )
						: $reachable_states;
				}
			}
			return $merged_reachable_states;
		};
		
		$states_to_process = array();
		$lookup_array = array();
		$get_meta_state = function( array $states ) use ( &$lookup_array, &$states_to_process, $fa ) {
			$state_hash_array = array_map( "spl_object_hash", $states );
			sort( $state_hash_array );
			$states_hash = serialize( $state_hash_array );
			if( ! array_key_exists( $states_hash, $lookup_array ) ) {
				$new_state = $fa->createState();
				$is_final = FALSE;
				foreach( $states as $current_state ) {
					if( $current_state->getIsFinal() ) {
						$is_final = TRUE;
						break;
					}
				}
				$new_state->setIsFinal( $is_final );
				$lookup_array[$states_hash] = $new_state;
				$states_to_process[] = $states;
			}
			return $lookup_array[$states_hash];
		};
		$new_start_state = $get_meta_state( $reachable_without_transition_array[spl_object_hash( $finite_automata->getStartState() )] );
		$fa->setStartState( $new_start_state );
		while( $current_states = array_pop( $states_to_process ) ) {
			$current_state = $get_meta_state( $current_states );
			foreach( $calculate_reachable( $current_states ) as $transition_symbol => $reachable_states ) {
				$target_state = $get_meta_state( $reachable_states );
				$current_state->addTransition( (string)$transition_symbol, $target_state );
			}
		}
		
		return $fa;
	}
	
	static function negate( self $finite_automata )
	{
		$fa = $finite_automata->isDeterministic() ? clone $finite_automata : self::determinize( $finite_automata );
		$fa->addFailureState();
		foreach( $fa->_states as $state ) {
			$state->setIsFinal( ! $state->getIsFinal() );
		}
		return $fa;
	}
	
	static function union( self $finite_automata1, self $finite_automata2 )
	{
		if( $finite_automata1->getAlphabet() !== $finite_automata2->getAlphabet() ) {
			throw new Exception( "different alphabet" );
		}
		$fa = new self();
		$fa->setAlphabet( $finite_automata1->getAlphabet() );
		$top = $fa->createState();
		$fa->setStartState( $top );
		$start_state1 = $fa->importAutomata( $finite_automata1 );
		$start_state2 = $fa->importAutomata( $finite_automata2 );
		$top->addTransition( "", $start_state1 );
		$top->addTransition( "", $start_state2 );
		return $fa;
	}
	
	static function intersection( self $finite_automata1, self $finite_automata2 )
	{
		return self::intersectionByCartesianProductMachine( $finite_automata1, $finite_automata2 );
	}

	static function intersectionByDeMorgan( self $finite_automata1, self $finite_automata2 )
	{
		if( $finite_automata1->getAlphabet() !== $finite_automata2->getAlphabet() ) {
			throw new Exception( "different alphabet" );
		}
		return self::negate( self::determinize( self::union( self::negate( $finite_automata1 ), self::negate( $finite_automata2 ) ) ) );
	}
	
	static function intersectionByCartesianProductMachine( self $finite_automata1, self $finite_automata2 )
	{
		if( $finite_automata1->getAlphabet() !== $finite_automata2->getAlphabet() ) {
			throw new Exception( "different alphabet" );
		}
		$fa = new self();
		$fa->setAlphabet( $finite_automata1->getAlphabet() );
		$translation_lookup = array();
		foreach( $finite_automata1->_states as $state1 ) {
	 		foreach( $finite_automata2->_states as $state2 ) {
	 			$new_state = $fa->createState();
				$new_state->setIsFinal( $state1->getIsFinal() && $state2->getIsFinal() );
				$translation_lookup[spl_object_hash( $state1 )][spl_object_hash( $state2 )] = $new_state;
	 		}
	 	}
	 	$fa->setStartState( $translation_lookup[spl_object_hash( $finite_automata1->getStartState() )][spl_object_hash( $finite_automata2->getStartState() )] );
	 	foreach( $finite_automata1->_states as $state1 ) {
	 		foreach( $finite_automata2->_states as $state2 ) {
				$new_state = $translation_lookup[spl_object_hash( $state1 )][spl_object_hash( $state2 )];
				foreach( $state1->getTransitionLookupArray() as $transition_symbol => $next_states1 ) {
					foreach( $state2->transitions( $transition_symbol ) as $next_state2 ) {
						foreach( $next_states1 as $next_state1 ) {
							$new_state->addTransition( (string)$transition_symbol, $translation_lookup[spl_object_hash( $next_state1 )][spl_object_hash( $next_state2 )] );
						}
					}
				}
				foreach( $state1->transitions( "" ) as $next_state1 ) {
					$new_state->addTransition( "", $translation_lookup[spl_object_hash( $next_state1 )][spl_object_hash( $state2 )] );
				}
				foreach( $state2->transitions( "" ) as $next_state2 ) {
					$new_state->addTransition( "", $translation_lookup[spl_object_hash( $state1 )][spl_object_hash( $next_state2 )] );
				}
	 		}
	 	}
	 	return $fa;
	}
	
}

?>