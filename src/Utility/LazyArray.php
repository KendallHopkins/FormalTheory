<?php
namespace FormalTheory\Utility;

class LazyArray implements ArrayAccess, Iterator, Countable
{

    private $_array_value = array();

    private $_array_closure = array();

    private $_size = 0;

    function appendClosure(Closure $closure)
    {
        $this->_array_closure[$this->_size ++] = $closure;
    }

    function appendValue($value)
    {
        $this->_array_value[$this->_size ++] = $value;
    }
    
    // ArrayAccess
    public function offsetExists($offset)
    {
        if ($offset < 0)
            return FALSE;
        return $offset < $this->_size;
    }

    public function offsetGet($offset)
    {
        if (! $this->offsetExists($offset))
            throw new \RuntimeException("invalid offset");
        if (array_key_exists($offset, $this->_array_closure)) {
            $this->_array_value[$offset] = $this->_array_closure[$offset]();
            unset($this->_array_closure[$offset]);
        }
        return $this->_array_value[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException("can't set offset directly");
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException("can't unset offset directly");
    }
    
    // Iterator
    private $_position = 0;

    public function rewind()
    {
        $this->_position = 0;
    }

    public function current()
    {
        return $this->offsetGet($this->_position);
    }

    public function key()
    {
        return $this->_position;
    }

    public function next()
    {
        $this->_position ++;
    }

    public function valid()
    {
        return $this->_position < $this->_size;
    }
    
    // Countable
    public function count()
    {
        return $this->_size;
    }
}

?>