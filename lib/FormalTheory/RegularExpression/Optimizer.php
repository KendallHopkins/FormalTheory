<?php

class FormalTheory_RegularExpression_Optimizer
{
	
	private $_strategies = NULL;
	private $_strategies_by_qualified_class_name = NULL;
	
	static function getStrategyClassNames()
	{
		static $classes = NULL;
		if( is_null( $classes ) ) {
			$removePrefix = function ( $prefix, $string ) {
				$prefix_length = strlen( $prefix );
				if( substr( $string, 0, $prefix_length ) !== $prefix ) {
					throw new RuntimeException( "\$prefix doesn't match: $prefix - $string" );
				}
				return substr( $string, $prefix_length );
			};
			$folder_path = realpath( __DIR__."/Optimizer/Strategy" );
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $folder_path ) );
			$iterator = new RegexIterator( $iterator, '/^(.+)\.php$/i', RecursiveRegexIterator::GET_MATCH );
			$classes = array();
			foreach( $iterator as $file ) {
				$classes[] = "FormalTheory_RegularExpression_Optimizer_Strategy_".str_replace( "/", "_", $removePrefix( $folder_path."/", $file[1] ) );
			}
		}
		return $classes;
	}
	
	function __construct( array $strategy_class_names = NULL )
	{
		if( is_null( $strategy_class_names ) ) {
			$strategy_class_names = self::getStrategyClassNames();
		}
		$this->_strategies = array();
		$this->_strategies_by_qualified_class_name = array();
		foreach( $strategy_class_names as $strategy_class_name ) {
			$strategy = new $strategy_class_name();
			$this->_strategies[] = $strategy;
			foreach( $strategy->qualifiedClassNames() as $qualified_class_name ) {
				$this->_strategies_by_qualified_class_name[$qualified_class_name][] = $strategy;				
			}
		}
	}
	
	function safe( FormalTheory_RegularExpression_Token $token )
	{
		do {
			$has_changed = FALSE;
			$token_class = get_class( $token );
			
			switch( $token_class ) {
				case "FormalTheory_RegularExpression_Token_Regex":
				case "FormalTheory_RegularExpression_Token_Union":
					$token = new $token_class( array_map( array( $this, "safe" ), $token->getTokens() ), FALSE );
					break;
				case "FormalTheory_RegularExpression_Token_Repeat":
					$token = new FormalTheory_RegularExpression_Token_Repeat( $this->safe( $token->getToken() ), $token->getMinNumber(), $token->getMaxNumber() );
					break;
				case "FormalTheory_RegularExpression_Token_Special":
				case "FormalTheory_RegularExpression_Token_Constant":
				case "FormalTheory_RegularExpression_Token_Set":
					break;
				default:
					throw new RuntimeException( "bad class: $token_class" );
			}
			
			if( array_key_exists( $token_class, $this->_strategies_by_qualified_class_name ) ) {
				foreach( $this->_strategies_by_qualified_class_name[$token_class] as $strategy ) {
					if( $strategy::IS_SAFE && $strategy->qualifier( $token ) ) {
						$new_token = $strategy->run( $token );
						if( $new_token === FALSE ) continue;
						if( ! $new_token instanceof FormalTheory_RegularExpression_Token ) {
							throw new RuntimeException( get_class( $strategy )." returned a non class: ".var_export( $new_token, TRUE ) );
						}
						$has_changed = TRUE;
						$token = $new_token;
						break;
					}
				}
			}
		} while( $has_changed );
		return $token;
	}
	
	static function findAllSubTokens( FormalTheory_RegularExpression_Token $token )
	{
		$tokens = array();
		$token_class = get_class( $token );
		switch( $token_class ) {
			case "FormalTheory_RegularExpression_Token_Regex":
			case "FormalTheory_RegularExpression_Token_Union":
				foreach( $token->getTokens() as $sub_token ) {
					$tokens = array_merge( $tokens, array( $sub_token ), self::findAllSubTokens( $sub_token ) );
				}
				break;
				break;
			case "FormalTheory_RegularExpression_Token_Repeat":
				$sub_token = $token->getToken();
				$tokens = array_merge( $tokens, array( $sub_token ), self::findAllSubTokens( $sub_token ) );
				break;
			case "FormalTheory_RegularExpression_Token_Special":
			case "FormalTheory_RegularExpression_Token_Constant":
			case "FormalTheory_RegularExpression_Token_Set":
				break;
			default:
				throw new RuntimeException( "bad class: $token_class" );
		}
		return $tokens;
	}
	
}

?>