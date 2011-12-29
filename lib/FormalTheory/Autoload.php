<?php

class FormalTheory_Autoload
{
	
	const CLASS_PREFIX = "FormalTheory_";
	
	static function autoload( $class_name )
	{
		if( substr( $class_name, 0, strlen( self::CLASS_PREFIX ) ) !== self::CLASS_PREFIX ) {
			return FALSE;
		}
		require( dirname( __FILE__ )."/".str_replace( "_", "/", substr( $class_name, strlen( self::CLASS_PREFIX ) ) ).".php" );
		return TRUE;
	}
	
	static function register()
	{
		spl_autoload_register( array( __CLASS__, "autoload" ) );
	}
	
	static function unregister()
	{
		spl_autoload_unregister( array( __CLASS__, "autoload" ) );
	}
	
}

?>