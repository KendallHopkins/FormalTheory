<?php

/*
Examples:
1111* -> 1{3,}
11*11* -> 1{2,}
11*(11)+ -> 1{3,}
1+(11)+ -> 1{3,}
11(11)* -> (11)+
111(11)* -> 1(11)+
1(11)*1 -> (11)+
1(21)*2 -> (12)+
(111)+(11)+ -> 1{5} (maybe)
*/

class FormalTheory_RegularExpression_Optimizer_Strategy_FolderIntoRepeat extends FormalTheory_RegularExpression_Optimizer_Strategy
{
	
	const IS_SAFE = TRUE;
	const COST = 0;
	const SUCCESS = .5;
	
	function qualifiedClassNames()
	{
		return array( "FormalTheory_RegularExpression_Token_Regex" );
	}
	
	function qualifier( FormalTheory_RegularExpression_Token $token )
	{
		$sub_tokens = $token->getTokens();
		if( count( $sub_tokens ) <= 1 ) {
			return FALSE;
		}
		foreach( $sub_tokens as $sub_token ) {
			if( $sub_token instanceof FormalTheory_RegularExpression_Token_Repeat ) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	function run( FormalTheory_RegularExpression_Token $token )
	{
		$true_mod = function( $n, $d ) {
			$result = $n % $d;
			if( $result < 0 ) {
				$result += abs( $d );
    		}
    		return $result;
		};
		
		$sub_tokens = $token->getTokens();
		$sub_token_count = count( $sub_tokens );
		$repeat_offsets = array_keys( array_filter( $sub_tokens, function( $token ) {
			return $token instanceof FormalTheory_RegularExpression_Token_Repeat;
		} ) );
		foreach( $repeat_offsets as $repeat_offset ) {
			$current_repeat = $sub_tokens[$repeat_offset];
			$current_repeat_token = $current_repeat->getToken();
			switch( get_class( $current_repeat_token ) ) {
				case "FormalTheory_RegularExpression_Token_Regex":
					$match_array = $current_repeat_token->getTokens();
					break;
				case "FormalTheory_RegularExpression_Token_Union":
				case "FormalTheory_RegularExpression_Token_Set":
				case "FormalTheory_RegularExpression_Token_Constant":
				case "FormalTheory_RegularExpression_Token_Repeat":
				case "FormalTheory_RegularExpression_Token_Special":
					$match_array = array( $current_repeat_token );
					break;
				default:
					throw new RuntimeException( "bad class: ".get_class( $current_repeat_token ) );
			}
			$match_array_count = count( $match_array );
			
			$matched_before = 0;
			$matched_after = 0;
			for( $i = -1; $repeat_offset + $i >= 0; $i-- ) {
				if( ! $sub_tokens[$repeat_offset + $i]->compare( $match_array[$true_mod( $i, $match_array_count )] ) ) {
					break;
				}
				$matched_before++;
			}
			for( $i = 1; $repeat_offset + $i < $sub_token_count; $i++ ) {
				if( ! $sub_tokens[$repeat_offset + $i]->compare( $match_array[$true_mod( $i - 1, $match_array_count )] ) ) {
					break;
				}
				$matched_after++;
			}
			if( $matched_before + $matched_after >= $match_array_count ) {
				$passes = (int)floor( ($matched_before+$matched_after) / $match_array_count );
				$extra = ($matched_before+$matched_after) % $match_array_count;
				if( $matched_after > 0 && $extra > 0 ) {
					if( $matched_after >= $extra ) {
						$matched_after -= $extra;
						$extra = 0;
					} else {
						$extra -= $matched_after;
						$matched_after = 0;
					}
				}
				if( $matched_before > 0 && $extra > 0 ) {
					$matched_before -= $extra;
				}
				$new_tokens = array_merge(
					array_slice( $sub_tokens, 0, $repeat_offset - $matched_before ),
					array( new FormalTheory_RegularExpression_Token_Repeat(
						new FormalTheory_RegularExpression_Token_Regex(
							array_merge(
								array_slice( $match_array, $matched_after % $match_array_count ),
								array_slice( $match_array, 0, $matched_after % $match_array_count )
							)
						, FALSE ),
						$current_repeat->getMinNumber() + $passes,
						is_null( $current_repeat->getMaxNumber() )
							? NULL
							: $current_repeat->getMaxNumber() + $passes
					) ),
					array_slice( $sub_tokens, $repeat_offset + 1 + $matched_after )
				);
				return new FormalTheory_RegularExpression_Token_Regex( $new_tokens, FALSE );
			}
		}
		return FALSE;
	}
	
}

?>