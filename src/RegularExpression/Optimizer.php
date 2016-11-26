<?php
namespace FormalTheory\RegularExpression;

use FormalTheory\RegularExpression\Token\Regex;
use FormalTheory\RegularExpression\Token\Union;
use FormalTheory\RegularExpression\Token\Repeat;
use FormalTheory\RegularExpression\Token\Special;
use FormalTheory\RegularExpression\Token\Constant;
use FormalTheory\RegularExpression\Token\Set;

class Optimizer
{

    private $_strategies_by_qualified_class_name = NULL;

    private $_mutation_by_qualified_class_name = NULL;

    static function getClassNames($prefix)
    {
        $removePrefix = function ($prefix, $string) {
            $prefix_length = strlen($prefix);
            if (substr($string, 0, $prefix_length) !== $prefix) {
                throw new \RuntimeException("\$prefix doesn't match: $prefix - $string");
            }
            return substr($string, $prefix_length);
        };
        $folder_path = realpath(__DIR__ . "/Optimizer/{$prefix}");
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder_path));
        $iterator = new \RegexIterator($iterator, '/^(.+)\.php$/i', \RecursiveRegexIterator::GET_MATCH);
        $classes = array();
        foreach ($iterator as $file) {
            $classes[] = "FormalTheory\\RegularExpression\\Optimizer\\{$prefix}\\" . str_replace("/", "\\", $removePrefix($folder_path . "/", $file[1]));
        }
        return $classes;
    }

    function __construct(array $strategy_class_names = NULL, array $mutation_class_names = NULL)
    {
        if (is_null($strategy_class_names)) {
            $strategy_class_names = self::getClassNames("Strategy");
            sort($strategy_class_names);
        }
        $this->_strategies_by_qualified_class_name = array();
        foreach ($strategy_class_names as $strategy_class_name) {
            $strategy = new $strategy_class_name();
            foreach ($strategy->qualifiedClassNames() as $qualified_class_name) {
                $this->_strategies_by_qualified_class_name[$qualified_class_name][] = $strategy;
            }
        }
        
        if (is_null($mutation_class_names)) {
            $mutation_class_names = self::getClassNames("Mutation");
            sort($mutation_class_names);
        }
        $this->_mutation_by_qualified_class_name = array();
        foreach ($mutation_class_names as $mutation_class_name) {
            $mutation = new $mutation_class_name();
            foreach ($mutation->qualifiedClassNames() as $qualified_class_name) {
                $this->_mutation_by_qualified_class_name[$qualified_class_name][] = $mutation;
            }
        }
    }

    function safe(Token $token)
    {
        do {
            $has_changed = FALSE;
            $token_class = get_class($token);
            
            switch ($token_class) {
                case Regex::class:
                case Union::class:
                    $token = new $token_class(array_map(array(
                        $this,
                        "safe"
                    ), $token->getTokens()), FALSE);
                    break;
                case Repeat::class:
                    $token = new Repeat($this->safe($token->getToken()), $token->getMinNumber(), $token->getMaxNumber());
                    break;
                case Special::class:
                case Constant::class:
                case Set::class:
                    break;
                default:
                    throw new \RuntimeException("bad class: $token_class");
            }
            
            if (array_key_exists($token_class, $this->_strategies_by_qualified_class_name)) {
                foreach ($this->_strategies_by_qualified_class_name[$token_class] as $strategy) {
                    if ($strategy->qualifier($token)) {
                        $new_token = $strategy->run($token);
                        if ($new_token === FALSE)
                            continue;
                        if (! $new_token instanceof Token) {
                            throw new \RuntimeException(get_class($strategy) . " returned a non class: " . var_export($new_token, TRUE));
                        }
                        $has_changed = TRUE;
                        $token = $new_token;
                        break;
                    }
                }
            }
        } while ($has_changed);
        return $token;
    }

    function getEffectiveAlphabet(Token $token)
    {
        $token_class = get_class($token);
        switch ($token_class) {
            case Regex::class:
            case Union::class:
                return array_unique(call_user_func_array("array_merge", array_map(array(
                    $this,
                    "getEffectiveAlphabet"
                ), $token->getTokens())));
            case Repeat::class:
                return $this->getEffectiveAlphabet($token->getToken());
            case Special::class:
                return array();
            case Constant::class:
                return array(
                    $token->getString()
                );
            case Set::class:
                return $token->charArray();
            default:
                throw new \RuntimeException("bad class: $token_class");
        }
    }

    function mutate(Token $token)
    {
        $lazy_array = new FormalTheory_Utility_LazyArray();
        $this->_mutate($token, $lazy_array, function ($token) {
            return $token;
        });
        return $lazy_array;
    }

    private function _mutate(Token $token, FormalTheory_Utility_LazyArray $lazy_array, Closure $build_full_regex)
    {
        $token_class = get_class($token);
        if (array_key_exists($token_class, $this->_mutation_by_qualified_class_name)) {
            foreach ($this->_mutation_by_qualified_class_name[$token_class] as $mutation) {
                $count = $mutation->countOptions($token);
                for ($i = 0; $i < $count; $i ++) {
                    $lazy_array->appendClosure(function () use($build_full_regex, $mutation, $token, $i) {
                        return $build_full_regex($mutation->run($token, $i));
                    });
                }
            }
        }
        switch ($token_class) {
            case Regex::class:
            case Union::class:
                $sub_tokens = $token->getTokens();
                foreach ($sub_tokens as $i => $sub_token) {
                    $this->_mutate($sub_token, $lazy_array, function ($mutated_token) use($token_class, $sub_tokens, $i, $build_full_regex) {
                        $sub_tokens[$i] = $mutated_token;
                        return $build_full_regex(new $token_class($sub_tokens, FALSE));
                    });
                }
                break;
            case Repeat::class:
                $this->_mutate($token->getToken(), $lazy_array, function ($mutated_token) use($token, $build_full_regex) {
                    return $build_full_regex(new Repeat($mutated_token, $token->getMinNumber(), $token->getMaxNumber()));
                });
                break;
            case Special::class:
            case Constant::class:
            case Set::class:
                break;
            default:
                throw new \RuntimeException("bad class: $token_class");
        }
    }

    function mutateCount(Token $token)
    {
        $count = 0;
        $token_class = get_class($token);
        switch ($token_class) {
            case Regex::class:
            case Union::class:
                $count += array_sum(array_map(array(
                    $this,
                    "mutateCount"
                ), $token->getTokens()));
                break;
            case Repeat::class:
                $count += $this->mutateCount($token->getToken());
                break;
            case Special::class:
            case Constant::class:
            case Set::class:
                break;
            default:
                throw new \RuntimeException("bad class: $token_class");
        }
        if (array_key_exists($token_class, $this->_mutation_by_qualified_class_name)) {
            foreach ($this->_mutation_by_qualified_class_name[$token_class] as $mutation) {
                $count += $mutation->countOptions($token);
            }
        }
        return $count;
    }
}

?>