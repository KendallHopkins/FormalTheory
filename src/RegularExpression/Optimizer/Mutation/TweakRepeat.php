<?php
namespace FormalTheory\RegularExpression\Optimizer\Mutation;

use FormalTheory\RegularExpression\Optimizer\Mutation;
use FormalTheory\RegularExpression\Token;
use FormalTheory\RegularExpression\Token\Repeat;

class TweakRepeat extends Mutation
{

    const COST = 0;

    function qualifiedClassNames()
    {
        return array(
            Repeat::class
        );
    }

    function qualifier(Token $token)
    {
        return TRUE;
    }

    function countOptions(Token $token)
    {
        return count(self::_getCombinations($token));
    }

    function run(Token $token, $option_index)
    {
        $pairs = self::_getCombinations($token);
        return new Repeat($token->getToken(), $pairs[$option_index][0], $pairs[$option_index][1]);
    }

    private static function _getCombinations(Token $token)
    {
        $min = $token->getMinNumber();
        $max = $token->getMaxNumber();
        $min_array = array(
            0,
            1
        );
        if ($min > 0)
            $min_array[] = $min - 1;
        $min_array[] = $min;
        $min_array[] = $min + 1;
        $max_array = array(
            1,
            NULL
        );
        if (! is_null($max)) {
            if ($max > 1)
                $max_array[] = $max - 1;
            if ($max > 0)
                $max_array[] = $max;
            $max_array[] = $max + 1;
        }
        $min_array = array_unique($min_array);
        $max_array = array_unique($max_array);
        $pairs = array();
        foreach ($min_array as $current_min) {
            foreach ($max_array as $current_max) {
                if (is_null($current_max) || $current_min <= $current_max) {
                    $pairs[] = array(
                        $current_min,
                        $current_max
                    );
                }
            }
        }
        return $pairs;
    }
}

?>