<?php

class FormalTheory_FiniteAutomata
{
	
	const WALK_TYPE_BFS = "bfs";
	const WALK_TYPE_DFS = "dfs";
	
	const WALK_DIRECTION_DOWN = "down";
	const WALK_DIRECTION_UP = "up";
	
	const WALK_TRAVERSE = "traverse";
	const WALK_SKIP = "skip";
	const WALK_EXIT = "exit";
	
	const LAMBDA_TRANSITION = "λ";
	
	private $_alphabet = array();
	private $_states = array();
	private $_start_state = NULL;
	
	function __construct( array $alphabet )
	{
		$this->_alphabet = $alphabet;
	}
	
	function __clone()
	{
		reset( $this->_states );
		$old_fa = $this->_states ? current( $this->_states )->getFiniteAutomata() : NULL;
		$this->_states = array();
		$this->_start_state = $old_fa ? $this->importAutomata( $old_fa ) : NULL;
	}
	
	function getAlphabet()
	{
		return $this->_alphabet;
	}
	
	function createState()
	{
		$state = new FormalTheory_FiniteAutomata_State( $this );
		return $this->_states[$state->getHash()] = $state;
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
		$symbol_lookup[$this->_start_state->getHash()] = "S".($this->_start_state->getIsFinal() ? "*" : "");
		foreach( $this->_states as $state ) {
			if( $state === $this->_start_state ) continue;
			$symbol_lookup[$state->getHash()] = ($i++).($state->getIsFinal() ? "*" : "");
		}
		$output = "";
		foreach( $this->_states as $state ) {
			$transition_lookup_array = $state->getTransitionLookupArray();
			if( $transition_lookup_array ) {
				foreach( $transition_lookup_array as $transition_symbol => $next_states ) {
					foreach( $next_states as $next_state ) {
						$output .= $symbol_lookup[$state->getHash()]." -> ".($transition_symbol === "" ? self::LAMBDA_TRANSITION : $transition_symbol)." -> ".$symbol_lookup[$next_state->getHash()].PHP_EOL;
					}
				}
			} else {
				$output .= $symbol_lookup[$state->getHash()]." (no transitions)".PHP_EOL;
			}
		}
		return $output;
	}
	
	function displayAsDot()
	{
		$final_state_string = "";
		$non_final_state_string = "";
		$transition_string = "";
		
		$i = 1;
		$symbol_lookup = array();
		$symbol_lookup[$this->_start_state->getHash()] = "S";
		foreach( $this->_states as $state ) {
			if( $state === $this->_start_state ) continue;
			$symbol_lookup[$state->getHash()] = ($i++);
		}
		foreach( $symbol_lookup as $hash => $id_string ) {
			if( $this->_states[$hash]->getIsFinal() ) {
				$final_state_string .= $id_string." ";
			} else {
				$non_final_state_string .= $id_string." ";
			}
		}
		
		foreach( $this->_states as $state ) {
			$transition_lookup_array = $state->getTransitionLookupArray();
			if( $transition_lookup_array ) {
				foreach( $transition_lookup_array as $transition_symbol => $next_states ) {
					foreach( $next_states as $next_state ) {
						$transition_symbol_string = $transition_symbol === "" ? self::LAMBDA_TRANSITION : str_replace( "\\", "\\\\",  FormalTheory_RegularExpression_Token_Constant::escapeChar( (string)$transition_symbol ) );
						$transition_string .= <<<EOT
{$symbol_lookup[$state->getHash()]} -> {$symbol_lookup[$next_state->getHash()]} [ label = "$transition_symbol_string" ];

EOT;
					}
				}
			}
		}
		$start_type = $this->_start_state->getIsFinal() ? "doublecircle" : "circle";
		$output = <<<EOT
digraph finite_state_machine {
rankdir=LR;
node [label="S" shape = $start_type]; S;
node [label=""];
node [shape = doublecircle]; $final_state_string;
node [shape = circle]; $non_final_state_string;
$transition_string
}
EOT;
		return $output;
	}
	
	function isMatch( array $symbol_array )
	{
		$stack = array( array( $this->getStartState(), array(), array_reverse( $symbol_array ) ) );
		do {
			list( $current_state, $recently_visited_states, $remaining_symbols ) = array_pop( $stack );
			$recently_visited_states[$current_state->getHash()] = $current_state;
			foreach( $current_state->transitions( "" ) as $next_state_hash => $next_state ) {
				if( ! array_key_exists( $next_state_hash, $recently_visited_states ) ) {
					array_push( $stack, array( $next_state, $recently_visited_states, $remaining_symbols ) );
				}
			}
			if( $remaining_symbols ) {
				$next_symbol = array_pop( $remaining_symbols );
				$recently_visited_states = array();
				foreach( $current_state->transitions( $next_symbol ) as $next_state ) {
					array_push( $stack, array( $next_state, $recently_visited_states, $remaining_symbols ) );
				}
			} else if( $current_state->getIsFinal() ) {
				return TRUE;
			}
		} while( $stack );
		return FALSE;
	}
	
	function compileMatcher()
	{
		if( ! $this->isDeterministic() ) {
			throw new Exception( "must be deterministic" );
		}
		$lookup_arrays = array_map( function( $state ) {
			return array(
				$state->getIsFinal(),
				array_map( function( $states ) {
					return key( $states );
				}, $state->getTransitionLookupArray() )
			);
		}, $this->_states );
		
		foreach( $lookup_arrays as $state_hash => &$state_info ) {
			foreach( $state_info[1] as $transition_symbol => $transition_state_hash ) {
				$state_info[1][$transition_symbol] = &$lookup_arrays[$transition_state_hash];
			}
		}
		
		$current_state = $lookup_arrays[$this->getStartState()->getHash()];
		
		return function( array $symbol_array ) use ( $current_state ) {
			foreach( $symbol_array as $symbol ) {
				if( ! isset( $current_state[1][$symbol] ) ) return FALSE;
				$current_state = $current_state[1][$symbol];
			}
			return $current_state[0];
		};
	}
	
	function removeDeadStates()
	{
		$down_visited_states = new SplObjectStorage();
		
		$get_walk_closure = function( $visited_states ) {
			return function( $transition_symbol, $next_state ) use ( $visited_states ) {
				if( $visited_states->contains( $next_state ) ) {
					return FormalTheory_FiniteAutomata::WALK_SKIP;
				}
				$visited_states->attach( $next_state );
				return FormalTheory_FiniteAutomata::WALK_TRAVERSE;
			};
		};
		
		$this->getStartState()->walkWithClosure( $get_walk_closure( $down_visited_states ), self::WALK_TYPE_DFS, self::WALK_DIRECTION_DOWN, NULL, TRUE );
		
		$up_visited_states = new SplObjectStorage();
		$up_walk_closure = $get_walk_closure( $up_visited_states );
		array_map( function( $final_state ) use ( $up_walk_closure ) {
			$final_state->walkWithClosure( $up_walk_closure, FormalTheory_FiniteAutomata::WALK_TYPE_DFS, FormalTheory_FiniteAutomata::WALK_DIRECTION_UP, NULL, TRUE );
		}, array_filter( $this->_states, function( $state ) { return $state->getIsFinal(); } ) );
		
		$visited_states = new SplObjectStorage();
		foreach( $down_visited_states as $state ) {
			if( $up_visited_states->contains( $state ) ) {
				$visited_states->attach( $state );
			}
		}
		$visited_states->attach( $this->getStartState() );
		
		foreach( $this->_states as $key => $state ) {
			if( ! $visited_states->contains( $state ) ) {
				$this->_states[$key]->unlink();
				unset( $this->_states[$key] );
			}
		}
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
		
		$this->getStartState()->walkWithClosure( function( $transition_symbol, $next_state ) use ( $visited_states, &$valid_solution_exists ) {
			if( $next_state->getIsFinal() ) {
				$valid_solution_exists = TRUE;
				return FormalTheory_FiniteAutomata::WALK_EXIT;
			}
			if( $visited_states->contains( $next_state ) ) {
				return FormalTheory_FiniteAutomata::WALK_SKIP;
			}
			$visited_states->attach( $next_state );
			return FormalTheory_FiniteAutomata::WALK_TRAVERSE;
		}, self::WALK_TYPE_BFS, self::WALK_DIRECTION_DOWN, NULL, TRUE );
		
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
			$translation_lookup[$state->getHash()] = $new_state;
		}
		foreach( $finite_automata->_states as $state ) {
			$new_state = $translation_lookup[$state->getHash()];
			foreach( $state->getTransitionLookupArray() as $transition_symbol => $next_states ) {
				foreach( $next_states as $next_state ) {
					$new_state->addTransition( (string)$transition_symbol, $translation_lookup[$next_state->getHash()] );
				}
			}
		}
		return $translation_lookup[$finite_automata->getStartState()->getHash()];
	}
	
	function minimize()
	{
		if( ! $this->isDeterministic() ) {
			throw new Exception( "fa must be deterministic" );
		}
		
		$this->removeDeadStates();
		
		$duplicate_state_hash_array = self::_getDuplicateStateHashArray( $this->_states, $this->_alphabet );
		
		foreach( $duplicate_state_hash_array as $duplicate_state_hashes ) {
			$main_state = $this->_states[array_pop( $duplicate_state_hashes )];
			foreach( $duplicate_state_hashes as $duplicate_state_hash ) {
				$duplicate_state = $this->_states[$duplicate_state_hash];
				foreach( $duplicate_state->getTransitionRefArray() as $transition_symbol => $prev_states ) {
					foreach( $prev_states as $prev_state ) {
						$prev_state->addTransition( (string)$transition_symbol, $main_state );
					}
				}
				if( $this->getStartState()->getHash() === $duplicate_state->getHash() ) {
					$this->setStartState( $main_state );
				}
				$duplicate_state->unlink();
				unset( $this->_states[$duplicate_state->getHash()] );
			}
		}
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
		$fa = new self( $finite_automata->getAlphabet() );
		
		$reachable_without_transition_array = array_map( function( $state ) {
			$reachable_without_transition = array( $state );
			$state->walkWithClosure( function( $transition_symbol, $next_state ) use ( &$reachable_without_transition ) {
				if( in_array( $next_state, $reachable_without_transition, TRUE ) || $transition_symbol !== "" ) {
					return FormalTheory_FiniteAutomata::WALK_SKIP;
				}
				$reachable_without_transition[$next_state->getHash()] = $next_state;
				return FormalTheory_FiniteAutomata::WALK_TRAVERSE;
			}, FormalTheory_FiniteAutomata::WALK_TYPE_DFS, FormalTheory_FiniteAutomata::WALK_DIRECTION_DOWN );
			return $reachable_without_transition;
		}, $finite_automata->_states );
		
		$reachable_by_transition_array = array_map( function( $state ) use ( $reachable_without_transition_array ) {
			$first_level_transition_lookup_arrays = array_map( function( $state ) {
				$transition_lookup_array = $state->getTransitionLookupArray();
				unset( $transition_lookup_array[""] );
				return $transition_lookup_array;
			}, $reachable_without_transition_array[$state->getHash()] );
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
				foreach( $reachable_by_transition_array[$state->getHash()] as $transition_symbol => $reachable_states ) {
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
			$state_hash_array = array_unique( array_map( "spl_object_hash", $states ) );
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
		foreach( $finite_automata->_states as $state ) { $get_meta_state( array( $state ) ); }
		$new_start_state = $get_meta_state( $reachable_without_transition_array[$finite_automata->getStartState()->getHash()] );
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
		$fa = new self( $finite_automata1->getAlphabet() );
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
		$fa = new self( $finite_automata1->getAlphabet() );
		$translation_lookup = array();
		foreach( $finite_automata1->_states as $state1 ) {
	 		foreach( $finite_automata2->_states as $state2 ) {
	 			$new_state = $fa->createState();
				$new_state->setIsFinal( $state1->getIsFinal() && $state2->getIsFinal() );
				$translation_lookup[$state1->getHash()][$state2->getHash()] = $new_state;
	 		}
	 	}
	 	$fa->setStartState( $translation_lookup[$finite_automata1->getStartState()->getHash()][$finite_automata2->getStartState()->getHash()] );
	 	foreach( $finite_automata1->_states as $state1 ) {
	 		foreach( $finite_automata2->_states as $state2 ) {
				$new_state = $translation_lookup[$state1->getHash()][$state2->getHash()];
				foreach( $state1->getTransitionLookupArray() as $transition_symbol => $next_states1 ) {
					foreach( $state2->transitions( $transition_symbol ) as $next_state2 ) {
						foreach( $next_states1 as $next_state1 ) {
							$new_state->addTransition( (string)$transition_symbol, $translation_lookup[$next_state1->getHash()][$next_state2->getHash()] );
						}
					}
				}
				foreach( $state1->transitions( "" ) as $next_state1 ) {
					$new_state->addTransition( "", $translation_lookup[$next_state1->getHash()][$state2->getHash()] );
				}
				foreach( $state2->transitions( "" ) as $next_state2 ) {
					$new_state->addTransition( "", $translation_lookup[$state1->getHash()][$next_state2->getHash()] );
				}
	 		}
	 	}
	 	return $fa;
	}
	
	function isSubsetOf( self $fa )
	{
		return ! self::intersection( $this, self::negate( $fa ) )->validSolutionExists();
	}
	
	function isProperSubsetOf( self $fa )
	{
		return $this->isSubsetOf( $fa ) && ! $this->isSupersetOf( $fa );
	}
	
	function isSuperSetOf( self $fa )
	{
		return $fa->isSubsetOf( $this );
	}
	
	function isProperSupersetOf( self $fa )
	{
		return $fa->isProperSubsetOf( $this );
	}
	
	function compare( self $fa )
	{
		return $this->isDeterministic() && $fa->isDeterministic()
			? $this->compareByDistinguishable( $fa )
			: $this->compareBySubset( $fa );
	}
	
	function compareByDistinguishable( self $fa )
	{
		if( ! $this->isDeterministic() ) {
			throw new Exception( "\$this must be deterministic" );
		}
		if( ! $fa->isDeterministic() ) {
			throw new Exception( "\$fa must be deterministic" );
		}
		$start_state1 = $this->getStartState()->getHash();
		$start_state2 = $fa->getStartState()->getHash();
		if( $start_state1 === $start_state2 ) {
			return TRUE;
		}
		$states = $this->_states + $fa->_states;
		$alphabet = array_unique( array_merge( $this->_alphabet, $fa->_alphabet ) );
		$duplicate_state_hash_array = self::_getDuplicateStateHashArray( $states, $alphabet );
		foreach( $duplicate_state_hash_array as $duplicate_state_hashes ) {
			if( in_array( $start_state1, $duplicate_state_hashes ) ) {
				return in_array( $start_state2, $duplicate_state_hashes );
			}
		}
		return FALSE;
	}
	
	function compareBySubset( self $fa )
	{
		return $this->isSubsetOf( $fa ) && $fa->isSubsetOf( $this );
	}
	
	function countSolutions()
	{
		if( ! $this->isDeterministic() ) {
			throw new Exception( "fa must be deterministic" );
		}
		
		if( ! $this->validSolutionExists() ) {
			return 0;
		}
		
		$state_solutions = array_fill_keys( array_keys( $this->_states ), NULL );
		do {
			$did_make_change = FALSE;
			$not_done_state_solutions = array_filter( $state_solutions, "is_null" );
			foreach( $this->_states as $state ) {
				if( ! is_null( $state_solutions[$state->getHash()] ) ) continue;
				$has_not_done_transition = FALSE;
				foreach( $state->getTransitionLookupArray() as $transition_states ) {
					if( array_intersect_key( $not_done_state_solutions, $transition_states ) ) {
						$has_not_done_transition = TRUE;
					}
				}
				if( ! $has_not_done_transition ) {
					$did_make_change = TRUE;
					$done_state_solutions = array_filter( $state_solutions, function( $state_solution ) { return ! is_null( $state_solution ); } );
					$total = $state->getIsFinal() ? 1 : 0;
					foreach( $state->getTransitionLookupArray() as $transition_states ) {
						$total += array_sum( array_intersect_key( $done_state_solutions, $transition_states ) );
					}
					$state_solutions[$state->getHash()] = $total;
					break;
				}
			}
		} while( is_null( $state_solutions[$this->getStartState()->getHash()] ) && $did_make_change );
		return $state_solutions[$this->getStartState()->getHash()];
	}
	
	function getRegex()
	{
		if( array_diff( $this->getAlphabet(), array_map( "chr", range( 0, 127 ) ) ) ) {
			throw new LogicException( "alphabet contain non-regex symbols" );
		}
		$empty_table = array_fill_keys( array_merge( array_keys( $this->_states ), array( "final" ) ), array() );
		$inverse_lookup_array = function( $lookup_array ) use ( $empty_table ) {
			$table = $empty_table;
			foreach( $lookup_array as $transition => $states ) {
				foreach( array_keys( $states ) as $state_hash ) {
					$table[$state_hash][] = new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Constant( (string)$transition ) ), TRUE );
				}
			}
			return $table;
		};
		$transition_map = array_map( function( $state ) use ( $inverse_lookup_array ) {
			$to_table = $inverse_lookup_array( $state->getTransitionLookupArray() );
			$from_table = $inverse_lookup_array( $state->getTransitionRefArray() );
			$self_table = $to_table[$state->getHash()];
			unset( $to_table[$state->getHash()] );
			unset( $from_table[$state->getHash()] );
			return array( $to_table, $from_table, $self_table );
		}, $this->_states );
		
		$start_state_hash = $this->getStartState()->getHash();
		
		//create new unified final state
		$transition_map["final"] = array( $empty_table, $empty_table, array() );
		unset( $transition_map["final"][0]["final"] );
		unset( $transition_map["final"][1]["final"] );
		foreach( array_keys( array_filter( $this->_states, function( $state ) { return $state->getIsFinal(); } ) ) as $final_state_hash ) {
			$transition_map[$final_state_hash][0]["final"][] = new FormalTheory_RegularExpression_Token_Regex( array(), TRUE );
			$transition_map["final"][1][$final_state_hash][] = new FormalTheory_RegularExpression_Token_Regex( array(), TRUE );
		}
		
		$build_regex_from_array = function( array $array ) {
			switch( count( $array ) ) {
				case 0:	return NULL;
				case 1: return $array[0];
				default: return new FormalTheory_RegularExpression_Token_Union( $array );
			}
		};
		
		$state_hashes_to_remove = array_diff( array_keys( $transition_map ), array( $start_state_hash, "final" ) );
		
		// Order states to remove by least complex first
		usort( $state_hashes_to_remove, function( $state_hash1, $state_hash2 ) use ( $transition_map ) {
			$state1_count1 = count( array_filter( $transition_map[$state_hash1][0] ) );
			$state1_count2 = count( array_filter( $transition_map[$state_hash1][1] ) );
			$state1_count3 = count( array_filter( $transition_map[$state_hash1][2] ) );
			$state2_count1 = count( array_filter( $transition_map[$state_hash2][0] ) );
			$state2_count2 = count( array_filter( $transition_map[$state_hash2][1] ) );
			$state2_count3 = count( array_filter( $transition_map[$state_hash2][2] ) );
			return ( $state1_count1 + $state1_count2 + $state1_count3 ) > ( $state2_count1 + $state2_count2 + $state2_count3 )
				? 1
				: -1;
		} );
		
		foreach( $state_hashes_to_remove as $state_hash_to_remove ) {
			foreach( $transition_map[$state_hash_to_remove][0] as $next_state_hash => $next_transitions ) {
				foreach( $transition_map[$state_hash_to_remove][1] as $prev_state_hash => $prev_transitions ) {
					if( ! $next_transitions || ! $prev_transitions ) continue;
					$middle_regex = $build_regex_from_array( $transition_map[$state_hash_to_remove][2] );
					$new_path = new FormalTheory_RegularExpression_Token_Regex( array(
						$build_regex_from_array( $prev_transitions ),
						( is_null( $middle_regex )
							? new FormalTheory_RegularExpression_Token_Regex( array(), TRUE )
							: new FormalTheory_RegularExpression_Token_Regex( array( new FormalTheory_RegularExpression_Token_Repeat( $middle_regex, 0 ) ), TRUE )
						),
						$build_regex_from_array( $next_transitions )
					), TRUE );
					if( $next_state_hash === $prev_state_hash ) {
						$transition_map[$prev_state_hash][2][] = $new_path;
					} else {
						$transition_map[$prev_state_hash][0][$next_state_hash][] = $new_path;
						$transition_map[$next_state_hash][1][$prev_state_hash][] = $new_path;
					}
				}
			}
			unset( $transition_map[$state_hash_to_remove] );
			foreach( array_keys( $transition_map ) as $state_hash ) {
				unset( $transition_map[$state_hash][0][$state_hash_to_remove] );
				unset( $transition_map[$state_hash][1][$state_hash_to_remove] );
			}
		}
		$start_to_start = $build_regex_from_array( $transition_map[$start_state_hash][2] );
		$finish_to_finsh = $build_regex_from_array( $transition_map["final"][2] );
		$start_to_finish = $build_regex_from_array( $transition_map[$start_state_hash][0]["final"] );
		$finish_to_start = $build_regex_from_array( $transition_map["final"][0][$start_state_hash] );
		
		if( is_null( $start_to_finish ) ) {
			throw new LogicException( "DFA has no valid solutions" );
		}
		$main_pipe_regex = array();
		if( ! is_null( $start_to_start ) ) {
			$main_pipe_regex[] = new FormalTheory_RegularExpression_Token_Repeat( $start_to_start, 0 );
		}
		$main_pipe_regex[] = $start_to_finish;
		if( ! is_null( $finish_to_finsh ) ) {
			$main_pipe_regex[] = new FormalTheory_RegularExpression_Token_Repeat( $finish_to_finsh, 0 );
		}
		$output_regex = array( new FormalTheory_RegularExpression_Token_Special( "^" ) );
		if( ! is_null( $start_to_finish ) && ! is_null( $finish_to_start ) ) {
			$output_regex[] = new FormalTheory_RegularExpression_Token_Regex( array_merge( $main_pipe_regex, array( $finish_to_start ) ), TRUE );
		}
		$output_regex[] = new FormalTheory_RegularExpression_Token_Regex( $main_pipe_regex, TRUE );
		$output_regex[] = new FormalTheory_RegularExpression_Token_Special( "$" );
		return new FormalTheory_RegularExpression_Token_Regex( $output_regex, FALSE );
	}
	
	static private function _getDuplicateStateHashArray( array $states, array $alphabet )
	{
		$states = array_values( $states );
		$states[] = NULL; //add dead state
		$alphabet = array_values( $alphabet );
		
		$get_pair_hash = function( FormalTheory_FiniteAutomata_State $state1 = NULL, FormalTheory_FiniteAutomata_State $state2 = NULL ) {
			$state1_hash = $state1 ? $state1->getHash() : "<dead_state>";
			$state2_hash = $state2 ? $state2->getHash() : "<dead_state>";
			return $state1_hash > $state2_hash ? "{$state1_hash}_{$state2_hash}" : "{$state2_hash}_{$state1_hash}";
		};
		
		$get_pairs = function( array $array ) {
			$pairs = array();
			for( $i = 1; $i < count( $array ); $i++ ) {
				for( $j = 0; $j < $i; $j++ ) {
					$pairs[] = array( $array[$i], $array[$j] );
				}
			}
			return $pairs;
		};
		
		$state_pairs = $get_pairs( $states );
		$state_pair_hashes = array_map( function( $state_pair ) use ( $get_pair_hash ) {
			list( $state1, $state2 ) = $state_pair;
			return $get_pair_hash( $state1, $state2 );
		}, $state_pairs );
		$state_pair_lookup = array_combine( $state_pair_hashes, $state_pairs );
		$distinguishable_array = array_fill_keys( $state_pair_hashes, array() );
		
		$final_states = array();
		$non_final_states = array();
		foreach( $states as $state ) {
			if( $state && $state->getIsFinal() ) {
				$final_states[] = $state;
			} else {
				$non_final_states[] = $state;
			}
		}
		
		$mark_distinguishable = NULL;
		$mark_distinguishable = function( $state1, $state2 ) use ( &$distinguishable_array, $state_pair_lookup, $get_pair_hash, &$mark_distinguishable ) {
			$pair_hash = $get_pair_hash( $state1, $state2 );
			if( is_null( $distinguishable_array[$pair_hash] ) ) {
				throw new RuntimeException( "already marked" );
			}
			$markable_pair_hashes = array_keys( $distinguishable_array[$pair_hash] );
			$distinguishable_array[$pair_hash] = NULL;
			foreach( $markable_pair_hashes as $markable_pair_hash ) {
				if( ! is_null( $distinguishable_array[$markable_pair_hash] ) ) {
					list( $_state1, $_state2 ) = $state_pair_lookup[$markable_pair_hash];
					$mark_distinguishable( $_state1, $_state2 );
				}
			}
		};
		
		foreach( $final_states as $final_state ) {
			foreach( $non_final_states as $non_final_state ) {
				$mark_distinguishable( $final_state, $non_final_state );
			}
		}
		
		foreach( array_merge( $get_pairs( $final_states ), $get_pairs( $non_final_states ) ) as $state_pair ) {
			list( $state1, $state2 ) = $state_pair;
			foreach( $alphabet as $symbol ) {
				$next_state1 = $state1 ? $state1->transition( $symbol ) : NULL;
				$next_state2 = $state2 ? $state2->transition( $symbol ) : NULL;
				if( ($next_state1 ? $next_state1->getHash() : NULL) === ($next_state2 ? $next_state2->getHash() : NULL) ) continue;
				$next_state_pair_hash = $get_pair_hash( $next_state1, $next_state2 );
				if( is_null( $distinguishable_array[$next_state_pair_hash] ) ) {
					$mark_distinguishable( $state1, $state2 );
					break;
				} else {
					$state_pair_hash = $get_pair_hash( $state1, $state2 );
					if( $state_pair_hash !== $next_state_pair_hash ) {
						$distinguishable_array[$next_state_pair_hash][$state_pair_hash] = NULL;
					}
				}
			}
		}
		
		$equal_pairs = array();
		foreach( $distinguishable_array as $state_pair_hash => $data ) {
			if( ! is_null( $data ) ) {
				$equal_pairs[] = $state_pair_lookup[$state_pair_hash];
			}
		}
		$state_group_lookup = array();
		$i = 0;
		foreach( $equal_pairs as $equal_pair ) {
			list( $equal_state1, $equal_state2 ) = $equal_pair;
			if( is_null( $equal_state1 ) || is_null( $equal_state2 ) ) continue;
			$equal_state1_hash = $equal_state1->getHash();
			$equal_state2_hash = $equal_state2->getHash();
			if( array_key_exists( $equal_state1_hash, $state_group_lookup ) ) {
				$group_id = $state_group_lookup[$equal_state1_hash];
			} else if( array_key_exists( $equal_state2_hash, $state_group_lookup ) ) {
				$group_id = $state_group_lookup[$equal_state2_hash];
			} else {
				$group_id = $i++;
			}
			$state_group_lookup[$equal_state1_hash] = $group_id;
			$state_group_lookup[$equal_state2_hash] = $group_id;
		}
		$state_groups = array();
		$main_for_group_lookup = array();
		foreach( $state_group_lookup as $state_hash => $group_id ) {
			$state_groups[$group_id][] = $state_hash;
		}
		return $state_groups;
	}
	
}

?>