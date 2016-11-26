<?php
namespace FormalTheory\Tests\FiniteAutomata;

use FormalTheory\FiniteAutomata;

class MinimizeTest extends \PHPUnit_Framework_TestCase
{

    function testBookExample()
    {
        // Fig 4.8 from Introduction to Automata Theory, Languages and Computation
        $fa = new FiniteAutomata(array(
            "0",
            "1"
        ));
        list ($a, $b, $c, $d, $e, $f, $g, $h) = $fa->createStates(8);
        $fa->setStartState($a);
        $c->setIsFinal(TRUE);
        
        $a->addTransition("0", $b);
        $a->addTransition("1", $f);
        $b->addTransition("0", $g);
        $b->addTransition("1", $c);
        $c->addTransition("0", $a);
        $c->addTransition("1", $c);
        $d->addTransition("0", $c);
        $d->addTransition("1", $g);
        $e->addTransition("0", $h);
        $e->addTransition("1", $f);
        $f->addTransition("0", $c);
        $f->addTransition("1", $g);
        $g->addTransition("0", $g);
        $g->addTransition("1", $e);
        $h->addTransition("0", $g);
        $h->addTransition("1", $c);
        
        $this->assertSame(8, $fa->count());
        $fa->minimize();
        $this->assertSame(5, $fa->count());
    }

    function testSimple()
    {
        $fa = new FiniteAutomata(array(
            "0",
            "1"
        ));
        list ($a, $b, $c) = $fa->createStates(3);
        $fa->setStartState($a);
        $b->setIsFinal(TRUE);
        $c->setIsFinal(TRUE);
        
        $a->addTransition("0", $b);
        $a->addTransition("1", $c);
        $b->addTransition("1", $c);
        $c->addTransition("1", $b);
        
        $this->assertSame(3, $fa->count());
        $fa->minimize();
        $this->assertSame(2, $fa->count());
    }

    function testSimple2()
    {
        $fa = new FiniteAutomata(array(
            "0"
        ));
        list ($a, $b) = $fa->createStates(2);
        $fa->setStartState($a);
        $a->setIsFinal(TRUE);
        $b->setIsFinal(TRUE);
        
        $a->addTransition("0", $b);
        $b->addTransition("0", $a);
        
        $this->assertSame(2, $fa->count());
        $fa->minimize();
        $this->assertSame(1, $fa->count());
    }

    function testSimple3()
    {
        $fa = new FiniteAutomata(array(
            "0"
        ));
        list ($a, $b) = $fa->createStates(2);
        $fa->setStartState($a);
        $a->setIsFinal(TRUE);
        
        $a->addTransition("0", $b);
        $b->addTransition("0", $a);
        
        $this->assertSame(2, $fa->count());
        $fa->minimize();
        $this->assertSame(2, $fa->count());
    }
}

?>