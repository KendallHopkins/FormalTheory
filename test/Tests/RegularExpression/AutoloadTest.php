<?php

class FormalTheory_RegularExpression_Tests_AutoloadTest extends PHPUnit_Framework_TestCase
{
	
	function testAutoload()
	{
		$autoload_function = array( "FormalTheory_Autoload", "autoload" );
		$this->assertTrue( in_array( $autoload_function, spl_autoload_functions(), TRUE ) );
		FormalTheory_Autoload::unregister();
		$this->assertFalse( in_array( $autoload_function, spl_autoload_functions(), TRUE ) );
		FormalTheory_Autoload::register();
		$this->assertTrue( in_array( $autoload_function, spl_autoload_functions(), TRUE ) );
	}
	
	function testAutoload2()
	{
		$this->assertFalse( FormalTheory_Autoload::autoload( "_bad_class_" ) );
	}
	
}

?>