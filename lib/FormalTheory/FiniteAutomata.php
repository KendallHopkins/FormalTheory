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
	
	function createLoopState()
	{
		$state = $this->createState();
		foreach( $this->_alphabet as $symbol ) {
			$state->addTransition( $symbol, $state );
		}
		return $state;
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
		$this->addFailureState();
		$final_states = array_values( array_filter( $this->_states, function( $state ) {
			return $state->getIsFinal();
		} ) );
		$non_final_states = array_values( array_filter( $this->_states, function( $state ) {
			return ! $state->getIsFinal();
		} ) );
		$distinguishable_array = array();
		$state_hashes = array_keys( $this->_states );
		for( $i = 1; $i < count( $state_hashes ); $i++ ) {
			for( $j = 0; $j < $i; $j++ ) {
				if( $state_hashes[$i] > $state_hashes[$j] ) {
					$distinguishable_array[$state_hashes[$i]][$state_hashes[$j]] = FALSE;
				} else {
					$distinguishable_array[$state_hashes[$j]][$state_hashes[$i]] = FALSE;
				}
			}
		}
		$get_is_distinguishable = function( FormalTheory_FiniteAutomata_State $state1, FormalTheory_FiniteAutomata_State $state2 ) use ( &$distinguishable_array ) {
			$state1_hash = $state1->getHash();
			$state2_hash = $state2->getHash();
			if( $state1_hash === $state2_hash ) {
				throw new RuntimeException( "don't ask if the same state is distinguishable" );
			}
			return $state1_hash > $state2_hash
				? $distinguishable_array[$state1_hash][$state2_hash]
				: $distinguishable_array[$state2_hash][$state1_hash];
		};
		$mark_distinguishable = function( FormalTheory_FiniteAutomata_State $state1, FormalTheory_FiniteAutomata_State $state2 ) use ( &$distinguishable_array ) {
			$state1_hash = $state1->getHash();
			$state2_hash = $state2->getHash();
			if( $state1_hash === $state2_hash ) {
				throw new RuntimeException( "don't ask if the same state is distinguishable" );
			}
			if( $state1_hash > $state2_hash ) {
				if( $distinguishable_array[$state1_hash][$state2_hash] ) {
					throw new RuntimeException( "already marked" );
				}
				$distinguishable_array[$state1_hash][$state2_hash] = TRUE;
			} else {
				if( $distinguishable_array[$state2_hash][$state1_hash] ) {
					throw new RuntimeException( "already marked" );
				}
				$distinguishable_array[$state2_hash][$state1_hash] = TRUE;
			}
		};
		
		foreach( $final_states as $final_state ) {
			foreach( $non_final_states as $non_final_state ) {
				$mark_distinguishable( $final_state, $non_final_state );
			}
		}
		$pairs = array();
		for( $i = 1; $i < count( $final_states ); $i++ ) {
			for( $j = 0; $j < $i; $j++ ) {
				if( ! $get_is_distinguishable( $final_states[$i], $final_states[$j] ) ) {
					$pairs[] = array( $final_states[$i], $final_states[$j] );
				}
			}
		}
		for( $i = 1; $i < count( $non_final_states ); $i++ ) {
			for( $j = 0; $j < $i; $j++ ) {
				if( ! $get_is_distinguishable( $non_final_states[$i], $non_final_states[$j] ) ) {
					$pairs[] = array( $non_final_states[$i], $non_final_states[$j] );
				}
			}
		}
		do {
			$did_mark = FALSE;
			foreach( $pairs as $i => $pair ) {
				list( $state1, $state2 ) = $pair;
				foreach( $this->getAlphabet() as $symbol ) {
					list( $next_state1 ) = array_values( $state1->transitions( $symbol ) );
					list( $next_state2 ) = array_values( $state2->transitions( $symbol ) );
					if( $next_state1 !== $next_state2 ) {
						if( $get_is_distinguishable( $next_state1, $next_state2 ) ) {
							$mark_distinguishable( $state1, $state2 );
							unset( $pairs[$i] );
							$did_mark = TRUE;
							break 1;
						}
					}
				}
			}
		} while( $did_mark );
		
		krsort( $distinguishable_array );
		foreach( $distinguishable_array as $state1_hash => $state2_hashes ) {
			$state1 = $this->_states[$state1_hash];
			foreach( $state2_hashes as $state2_hash => $is_distinguishable ) {
				if( ! $is_distinguishable ) {
					$state2 = $this->_states[$state2_hash];
					foreach( $state2->getTransitionRefArray() as $transition_symbol => $prev_states ) {
						foreach( $prev_states as $prev_state ) {
							$prev_state->addTransition( (string)$transition_symbol, $state1 );
						}
					}
					if( $this->getStartState()->getHash() === $state2->getHash() ) {
						$this->setStartState( $state1 );
					}
					$state2->unlink();
				}
			}
		}
		
		$this->removeDeadStates();
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
	
	function compare( self $fa )
	{
		return self::compareByNegationAndIntersection( $fa );
	}
	
	function compareByNegationAndIntersection( self $fa )
	{
		return
			! self::intersection( $this, self::negate( $fa ) )->validSolutionExists() &&
			! self::intersection( self::negate( $this ), $fa )->validSolutionExists();
	}
	
	function getRegex()
	{
		if( array_diff( $this->getAlphabet(), array_map( "chr", range( 0, 255 ) ) ) ) {
			throw new LogicException( "alphabet contain non-regex symbols" );
		}
		$empty_table = array_fill_keys( array_merge( array_keys( $this->_states ), array( "final" ) ), array() );
		$inverse_lookup_array = function( $lookup_array ) use ( $empty_table ) {
			$table = $empty_table;
			foreach( $lookup_array as $transition => $states ) {
				foreach( array_keys( $states ) as $state_hash ) {
					$table[$state_hash][] = $transition;
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
			$transition_map[$final_state_hash][0]["final"][] = "";
			$transition_map["final"][1][$final_state_hash][] = "";
		}
		
		$build_regex_from_array = function( array $array ) {
			switch( count( $array ) ) {
				case 0:	return NULL;
				case 1: return $array[0];
				default: return "(".implode( "|", $array ).")";
			}
		};
		
		$test = array_diff( array_keys( $transition_map ), array( $start_state_hash, "final" ) );
		shuffle( $test );
		foreach( $test as $state_hash_to_remove ) {
			foreach( $transition_map[$state_hash_to_remove][0] as $next_state_hash => $next_transitions ) {
				foreach( $transition_map[$state_hash_to_remove][1] as $prev_state_hash => $prev_transitions ) {
					if( ! $next_transitions || ! $prev_transitions ) continue;
					$middle_regex = $build_regex_from_array( $transition_map[$state_hash_to_remove][2] );
					$new_path =
						$build_regex_from_array( $prev_transitions ).
						( is_null( $middle_regex ) ? "" : "(".$middle_regex.")*" ).
						$build_regex_from_array( $next_transitions );
					
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
		$main_pipe_regex = "";
		if( ! is_null( $start_to_start ) ) {
			$main_pipe_regex .= "(".$start_to_start.")*";
		}
		$main_pipe_regex .= $start_to_finish;
		if( ! is_null( $finish_to_finsh ) ) {
			$main_pipe_regex .= "(".$finish_to_finsh.")*";
		}
		$output_regex = "^";
		if( ! is_null( $start_to_finish ) && ! is_null( $finish_to_start ) ) {
			$output_regex .= "(".$main_pipe_regex.$finish_to_start.")*";
		}
		$output_regex .= $main_pipe_regex."$";
		return $output_regex;
	}
	
}

?>