<?php

/**
 * Defines the class CodeRage\WebService\Regex
 *
 * File:        CodeRage/WebService/Regex.php
 * Date:        Thu Jun 28 17:15:35 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;


/**
 * Represents a simple regular expression
 */
final class Regex {

    /**
     * @var string
     */
    const MATCH_QUANTIFIER = '/^(\?|\*|\+)$/';

    /**
     * @var string
     */
    const MATCH_ANCHOR = '/^[\$^]$/';

    /**
     * @var string
     */
    const MATCH_META = '/[][(){}.?*+\\\\]/';

    /**
     * @var string
     */
    const GRAMMAR_PATH = 'Regex/Grammar.pp';

    /**
     * Constructs a CodeRage\WebService\Regex
     *
     * @param strng $expr The regular expression to parse
     */
    public function __construct($expr)
    {
        if ($expr === null)
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing regular expression'
                ]);
        Args::check($expr, 'string', 'regular expression');
        try {
            $this->ast = $this->compiler()->parse($expr);
        } catch (\Throwable $e) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid regular expression: $expr",
                    'inner' => $e
                ]);

        }
        $this->expr = $expr;
        $this->root = $this->processNode($this->ast);
    }

    /**
     * Returns the original expression
     */
    public function expr()
    {
        return $this->expr;
    }

    /**
     * Returns the expression tree formatted as a string
     */
    public function prettyPrint()
    {
        return json_encode($this->root, JSON_PRETTY_PRINT);
    }

    /**
     * Translates this regular expression into MySQL syntax
     */
    public function toMySql()
    {
        return $this->toMySqlImpl($this->root);
    }

    /**
     * Traverses the Hoa\Compiler\Llk AST, constructing a simplified node
     *
     * @param array $node
     * @return array
     */
    private function processNode($node)
    {
        $type = $this->nodeType($node);
        if ($type == '#expression') {
            $kid = $node->getChildren()[0];
            if ($this->nodeType($kid) == '#alternation') {
                return $this->processNode($kid);
            } else {
                return
                    [
                        'type' => 'expr',
                        'value' => null,
                        'children' => [$this->processNode($kid)]
                    ];
            }
        } elseif ($type == '#alternation') {
            return
                [
                    'type' => 'expr',
                    'value' => null,
                    'children' =>
                        Array_::map(function($n)
                        {
                            return $this->processNode($n);
                        }, $node->getChildren())
                ];
        } elseif ($type == '#concatenation') {
            return
                [
                    'type' => 'sequence',
                    'value' => null,
                    'children' =>
                        Array_::map(function($n)
                        {
                            return $this->processNode($n);
                        }, $node->getChildren())
                ];
        } elseif ($type == '#quantification') {
            list($first, $last) = $node->getChildren();
            $quant = $last->getValue()['value'];
            if (!preg_match(self::MATCH_QUANTIFIER, $quant))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Unsupported regular expression quantifier: $quant"
                    ]);
            return
                [
                    'type' => 'repeat',
                    'value' => $quant,
                    'children' => [$this->processNode($first)]
                ];
        } elseif ($type == 'token') {
            $v = $node->getValue();
            $token = $v['token'];
            $value = $v['value'];
            if ($token == 'literal') {
                if ($value == '.' ) {
                    return
                        [
                            'type' => 'any',
                            'value' => null,
                            'children' => null
                        ];
                } else {
                    $value = stripslashes($value);
                    if (Text::toAscii($value) != $value)
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'details' =>
                                    'Unsupported regular expression literal: ' .
                                    $value
                            ]);
                    return
                        [
                            'type' => 'literal',
                            'value' => $value,
                            'children' => null
                        ];
                }
            } elseif ($token == 'anchor') {
                if ($value != '^' && $value != '$')
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Unsupported regular expression anchor: $value"
                        ]);
                return
                    [
                        'type' => 'anchor',
                        'value' => $value,
                        'children' => null
                    ];
            } else {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Unsupported regular expression token: $token"
                    ]);
            }
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Unsupported regular expression construct: ' .
                        substr($type, 1)
                ]);
        }
    }

    /**
     * Returns the type of a YAPE::Regex node
     */
    private function nodeType($node)
    {
        return $node->getId();
    }

    /**
     * Returns a newly constructed node of type "literal"
     */
    private function literal($value)
    {
        if (!Text::toAscii($value) == $value)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Non-ASCII characters in regular expression: $value"
                ]);
        return (object)
            [
                'type' => 'literal',
                'value' => $value,
                'children' => null
            ];
        return $result;
    }

    /**
     * Returns a newly constructed node of type "sequence" whose children are
     * constructred by parsing the given list of YAPE::Regex nodes
     */
    private function sequence($nodes)
    {
        $result = (object)
            [
                'type' => 'sequence',
                'value' => null,
                'children' => $nodes
            ];
        return $result;
    }

    private function toMySqlImpl($node)
    {
        $type = $node['type'];
        if ($type == 'expr') {
            $inner =
                Array_::map(function($n) { return $this->toMySqlImpl($n); }, $node['children'], '|');
            return "($inner)";
        } elseif ($type == 'sequence') {
            return
                Array_::map(function($n) { return $this->toMySqlImpl($n); }, $node['children'], '');
        } elseif ($type == 'repeat') {
            $child = $node['children'][0];
            $inner = $this->stripParens($this->toMySqlImpl($child));
            if ( $child['type'] != 'any' &&
                 ($child['type'] != 'literal' || strlen($child['value']) != 1) )
            {
                $inner = "($inner)";
            }
            return $inner . $node['value'];
        } elseif ($type == 'literal') {
            $text = $node['value'];
            $result = null;
            for ($i = 0, $n = strlen($text); $i < $n; ++$i) {
                $c = $text[$i];
                $result .= preg_match(self::MATCH_META, $c) ?
                    '\\' . $c :
                    $c;
            }
            return $result;
        } elseif ($type == 'any') {
            return '.';
        } elseif ($type == 'anchor') {
            return $node['value'];
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "MySQL does not support node type '$type'"
                ]);
        }
    }

    /**
     * Strips zero or more pairs of outer parentheses
     *
     * @param string
     * @return string
     */
    private function stripParens($value)
    {
        while ( $value[0] == '(' &&
                ($len = strlen($value)) > 0 &&
                $value[$len - 1] == ')' )
        {
            $value = substr($value, 1, $len - 2);
        }
        return $value;
    }

    /**
     * Returns the regular expression parser
     *
     * @return Hoa\Compiler\Llk\Parser
     */
    private function compiler()
    {
        if (self::$compiler === null) {
            $path = __DIR__ . '/' . self::GRAMMAR_PATH;
            $grammar  = new \Hoa\File\Read($path);
            self::$compiler = \Hoa\Compiler\Llk\Llk::load($grammar);
        }
        return self::$compiler;
    }

    /**
     * @var Hoa\Compiler\Llk\Parser
     */
    private static $compiler;

    /**
     * @var string
     */
    private $expr;

    /**
     * @var stdclass
     */
    private $root;
}
