<?php
namespace FormalTheory\RegularExpression\Token;

use FormalTheory\RegularExpression\Token;

class Union extends Token
{

    private $_regex_array;

    function __construct(array $regex_array)
    {
        foreach ($regex_array as $regex) {
            if (! $regex instanceof Token) {
                throw new RuntimeException("union can only take tokens: " . var_export($regex, TRUE));
            }
        }
        $this->_regex_array = array_values($regex_array);
    }

    function _toString()
    {
        return implode("|", $this->_regex_array);
    }

    function getTokens()
    {
        return $this->_regex_array;
    }

    function getMatches()
    {
        return call_user_func_array("array_merge", array_map(function ($regex) {
            return $regex->getMatches();
        }, $this->_regex_array));
    }

    function getFiniteAutomataClosure()
    {
        $regex_array = $this->_regex_array;
        return function ($fa, $start_states, $end_states) use($regex_array) {
            foreach ($regex_array as $regex) {
                $current_start_starts = $fa->createStates(4);
                $current_end_starts = $fa->createStates(4);
                $start_states[0]->addTransition("", $current_start_starts[0]);
                $start_states[1]->addTransition("", $current_start_starts[1]);
                $start_states[2]->addTransition("", $current_start_starts[2]);
                $start_states[3]->addTransition("", $current_start_starts[3]);
                $current_end_starts[0]->addTransition("", $end_states[0]);
                $current_end_starts[1]->addTransition("", $end_states[1]);
                $current_end_starts[2]->addTransition("", $end_states[2]);
                $current_end_starts[3]->addTransition("", $end_states[3]);
                
                $fa_closure = $regex->getFiniteAutomataClosure();
                $fa_closure($fa, $current_start_starts, $current_end_starts);
            }
        };
    }

    protected function _compare($token)
    {
        $this_done = array();
        $token_done = array();
        foreach ($this->_regex_array as $i => $sub_token1) {
            if (in_array($i, $this_done))
                continue;
            for ($a = $i + 1; $a < count($this->_regex_array); $a ++) {
                if ($sub_token1->compare($this->_regex_array[$a])) {
                    $this_done[] = $a;
                }
            }
            $did_match = FALSE;
            foreach ($token->_regex_array as $j => $sub_token2) {
                if (in_array($j, $token_done))
                    continue;
                if ($sub_token1->compare($sub_token2)) {
                    $did_match = TRUE;
                    $token_done[] = $j;
                }
            }
            if (! $did_match) {
                return FALSE;
            }
        }
        return count($token_done) === count($token->_regex_array);
    }
}

?>