<?php

class FormalTheory_RegularExpression_Utility
{
	
	static function crossProductStrinArrays( $string_array1, $string_array2 )
	{
		$output = array();
		foreach( $string_array1 as $string1 ) {
			foreach( $string_array2 as $string2 ) {
				$output[] = $string1.$string2;
			}
		}
		return $output;
	}
	
}

?>