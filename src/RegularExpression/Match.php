<?php
namespace FormalTheory\RegularExpression;

class Match
{

    private $_has_bos = FALSE;

    private $_has_eos = FALSE;

    private $_string = "";

    function getMatch()
    {
        if (! $this->_has_bos) {
            throw new \RuntimeException("regex doesn't start with a BOS token");
        }
        if (! $this->_has_eos) {
            throw new \RuntimeException("regex doesn't end with a EOS token");
        }
        return $this->_string;
    }

    function isEqual(self $match)
    {
        return $this->_has_bos === $match->_has_bos && $this->_has_eos === $match->_has_eos && $this->_string === $match->_string;
    }

    static function createFromString($string)
    {
        $match = new self();
        $match->_string = $string;
        return $match;
    }

    static function createFromBOS()
    {
        $match = new self();
        $match->_has_bos = TRUE;
        return $match;
    }

    static function createFromEOS()
    {
        $match = new self();
        $match->_has_eos = TRUE;
        return $match;
    }

    static function join(self $match1, self $match2)
    {
        if ($match1->_has_eos && ($match2->_string !== "" || $match2->_has_bos)) {
            return FALSE;
        }
        if ($match2->_has_bos && ($match1->_string !== "" || $match1->_has_eos)) {
            return FALSE;
        }
        $match = self::createFromString($match1->_string . $match2->_string);
        $match->_has_bos = $match1->_has_bos || $match2->_has_bos;
        $match->_has_eos = $match1->_has_eos || $match2->_has_eos;
        return $match;
    }
}

?>