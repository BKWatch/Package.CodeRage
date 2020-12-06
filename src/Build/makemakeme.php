<?php

/**
 * Builds the script makeme.php
 *
 * File:        CodeRage/Build/makemakeme.php
 * Date:        Mon Jan 21 11:31:55 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 * @package     CodeRage_Build
 */

namespace Makeme;

use function CodeRage\Text\wrap;

/**
 * @ignore
 */
const DIRECTORIES = 'Action Config Packages Resource Svn Target Tool';
const MATCH_INCLUDES = '#[ \t]*require_once\s*\(?\s*(?:[\'"])(CodeRage/[^\'"]+)[\'"]\)' .
        '?\s*;[^\n]*\n#';
const MATCH_NAMESPACE =
    '/namespace ([_a-z][_a-z0-9]*(\\\[_a-z][_a-z0-9]*)*);/i';
const MATCH_CLASSDEF = '/(?:class|interface)\s+([_a-z][_a-z0-9]*)/i';
const MATCH_BASEDEF = '/(?:extends|implements)\s+' .
        '((?:[\\\_a-z][\\\_a-z0-9]*)(?:\s*,\s*[\\\_a-z][\\\_a-z0-9]*)*)/i';
const BUILD_DIRECTORY = 'CodeRage/Build';
const RESOURCE_DIRECTORY = 'CodeRage/Build/Resource';
const RESOURCE_DEFINITION = 'CodeRage/Build/Resource.php';

try {
    checkCurrentDirectory();
    writeFile(buildFile());
} catch (Throwable $e) {
    exit_($e->getMessage());
}

function checkCurrentDirectory()
{
    // Check whether we are in the directory CodeRage/Build/Bin, and
    // change to the directory CodeRage
    $okay = false;
    $cwd = getcwd();
    if (file_exists('makemakeme.php')) {
        $directories = preg_split('/\w+/', trim(DIRECTORIES));
        foreach ($directories as $d)
            if (!@is_dir("$cwd/$d")) {
                $okay = true;
                break;
            }
    }
    if (!$okay)
        exit_(
           'makemakeme.php must be called from the directory ' .
           BUILD_DIRECTORY
        );
    chdir('..');
    if (basename(getcwd()) != 'CodeRage')
        exit_(
           'makemakeme.php must be called from the directory ' .
           BUILD_DIRECTORY
        );

    // Change to the parent of the CodeRage directory
    chdir('..');

    // Add parent of the CodeRage directory to front of include path
    ini_set(
        'include_path',
        getcwd() . PATH_SEPARATOR . ini_get('include_path')
    );
}

function writeFile($content)
{
    require_once("CodeRage/File/generate.php");
    \CodeRage\File\generate(
        getcwd() . '/' . BUILD_DIRECTORY . '/makeme.php', $content
    );
}

function buildFile()
{
    $content = configStub() . collectResources() . collectSources();
    return tabify($content);
}

function configStub()
{
    return
        "namespace Makeme;\n" .
        "\n" .
        "class Config {\n" .
        "    public function hasProperty(\$n) { return false; }\n" .
        "    public function getProperty(\$n, \$d = null) { return \$d; }\n" .
        "    public static function current() { return new Config; }\n" .
        "}\n";
}

function collectResources()
{
    // Create list of resources
    $dir = getcwd() . '/' . RESOURCE_DIRECTORY;
    $files = [];
    $hnd = @opendir($dir);
    if ($hnd === false)
        exit_("Failed reading directory '$dir'");
    while (($file = @readdir($hnd)) !== false)
        if (is_file("$dir/$file"))
            $files[] = $file;
    @closedir($hnd);

    // Define class Makeme\\Build\\Resource_
    require_once("CodeRage/File/getContents.php");
    require_once("CodeRage/Text/wrap.php");
    $list = "'" . join("', '", $files) . "'";
    $code =
        "\n" .
        "namespace Makeme\\Build;\n" .
        "\n" .
        "class Resource_ {\n" .
        "    static function listFiles(\\Makeme\\Build\\Run \$run)\n" .
        "    {\n" .
        "        return [\n" .
        wrap($list, 68, ["\t\t\t\t   "]) .
        "               ];\n" .
        "    }\n" .

        "    static function load(\\Makeme\\Build\\Run \$run, \$file)\n" .
        "    {\n" .
        "        \$content = null;\n" .
        "        switch(\$file) {\n";
    foreach ($files as $f) {
        $content = \CodeRage\File\getContents("$dir/$f");
        $prefixes = ["\t\t\t\t"];
        $code .=
            "        case '$f':\n" .
            "            \$content = <<< __ENDRESOURCE__\n" .
            wrap(base64_encode($content), 68, $prefixes) .
            "__ENDRESOURCE__;\n" .
            "            break;\n";
    }
    $code .=
        "        default:\n" .
        "            throw new \Exception(\"No such resource: \$file\");\n" .
        "        }\n" .
        "        return base64_decode(\$content);\n" .
        "    }\n";
    $code .=
        "    static function loadFile(\\Makeme\\Build\\Run \$run, \$file)\n" .
        "    {\n" .
        "        \$temp = \\Makeme\\File\\temp();\n" .
        "        \$contents = self::load(\$run, \$file);\n" .
        "        file_put_contents(\$temp, \$contents);\n" .
        "        return \$temp;\n" .
        "    }\n" .
        "}\n";
    return $code;
}

function collectSources()
{
    require_once("CodeRage/Build/Constants.php");
    require_once("CodeRage/File/find.php");
    require_once("CodeRage/Util/preorderSort.php");

    // Define list of directories to search for included sources.
    $search = explode(PATH_SEPARATOR, ini_get('include_path'));

    // Define collection of source files to exclude
    $exclude =
        [
            getcwd() . '/' . RESOURCE_DEFINITION => 1
        ];

    // Define collections of processed and unprocessed source files
    $processed = [];
    $unprocessed = [driver()];

    // Recursively process source files, adding them to as keys to $processed
    while (sizeof($unprocessed)) {

        // Check existence
        $file = array_pop($unprocessed);
        //echo "Processing '$file'\n";
        if (!is_file($file))
            exit_("Invalid source file '$file'");
        $real = realpath($file);
        if (isset($processed[$real]))
            continue; // Avoid infinite loop
        $processed[$real] = 1;

        // Read file
        $text = @file_get_contents($file);
        if ($text === false)
            exit_("Failed reading '$file'");

        // Process includes
        $match = null;
        if (preg_match_all(MATCH_INCLUDES, $text, $match) === false)
            exit_("Failed matching '" . MATCH_INCLUDES . "'");
        foreach ($match[1] as $path) {
            $f = \CodeRage\File\find($path, $search);
            if ($f === null)
                exit_(
                    "Non existent source file '$path' referenced in '$file'"
                );
            //echo "Adding '$f' (path = '$path')\n";
            if (!isset($exclude[$f]))
                $unprocessed[] = $f;
        }
    }

    // Sort collection of source files
    $sorter = new SourceSorter(array_keys($processed));
    $sorter->sort();
    $processed = $sorter->sources();

    // Concatenate source files, removing includes
    $content = '';
    foreach ($processed as $f)
        $content .= processSource($f);

    return stripComments($content);
}

/**
 * Returns the path to 'CodeRage/Build/driver.php', the script that makes the
 * build system start.
 *
 * @return string
 */
function driver()
{
    return realpath(getcwd() . '/' . BUILD_DIRECTORY . '/driver.php');
}

/**
 * Sorts a list of source files in such a manner that files definiting base
 * classes or interfaces come before files definting classes or interfaces
 * extending or implementing those classes or interfaces.
 * @package CodeRage_Build
 */
class SourceSorter {
    private $sources;
    private $classDefs = [];
    private $baseDefs = [];

    /**
     * Constructs a Makeme\SourceSorter.
     *
     * @param array $sources
     */
    function __construct($sources)
    {
        $this->sources = $sources;
        foreach ($sources as $f) {
            $text = file_get_contents($f);
            $text = stripComments($text);
            $namespace = preg_match(MATCH_NAMESPACE, $text, $match) ?
                $match[1] :
                null;
            $this->classDefs[$f] = [];
            if (preg_match_all(MATCH_CLASSDEF, $text, $match)) {
                if ($namespace !== null)
                    $match[1] =
                        array_map(
                            function($class) use($namespace)
                            { return "\\$namespace\\$class"; },
                            $match[1]
                        );
                $this->classDefs[$f] = $match[1];
            }
            $this->baseDefs[$f] = [];
            if (preg_match_all(MATCH_BASEDEF, $text, $match)) {
                foreach ($match[1] as $list) {
                    foreach (preg_split('/\s*,\s*/', trim($list)) as $def) {
                        if ($namespace !== null && $def[0] != '\\')
                            $def = "\\$namespace\\$def";
                        $this->baseDefs[$f][] = $def;
                    }
                }
            }
        }
    }

    /**
     * Returns the underlying collection of source files.
     */
    function sources()
    {
        return $this->sources;
    }

    /**
     * Sorts the underlying collection.
     */
    function sort()
    {
        $callback = [$this, 'compareSources'];
        \CodeRage\Util\strictPreorderSort($this->sources, $callback);
        for ($z = 0, $n = sizeof($this->sources); $z < $n - 1; ++$z) {
            $lhs = $this->sources[$z];
            $rhs = $this->sources[$z + 1];
            if ($this->compareSources($lhs, $rhs) == 1)
                exit_(
                    "A recurive dependency prevents the source files " .
                    "from being ordered"
                );
        }
    }

    /**
     * Comparison function passed to CodeRage\Util\strictPreorderSort.
     *
     * @param string $lhs
     * @param string $rhs
     * @return int
     */
    function compareSources($lhs, $rhs)
    {
        static $driver;
        if (!$driver)
            $driver = driver();

        if ($lhs == $rhs)
            return 0;
        if ($rhs == $driver)
            return -1;
        if ($lhs == $driver)
            return 1;

        $result = null;
        foreach ($this->baseDefs[$rhs] as $base) {
            if (in_array($base, $this->classDefs[$lhs])) {
                $result = -1;
                break;
            }
        }
        foreach ($this->baseDefs[$lhs] as $base) {
            if (in_array($base, $this->classDefs[$rhs])) {
                if ($result !== null)
                    exit_(
                        "A recurive dependency between '$lhs' and '$rhs' " .
                        "prevents the source files from being ordered"
                    );
                $result = 1;
                break;
            }
        }
        return $result;
    }
}

function processSource($source)
{
    $text = file_get_contents($source);
    $text = preg_replace('/^\s*<\?php\s*?\n?/', '', $text);
    $text = preg_replace('/\?>\s*?\n?$/', '', $text);
    $text = preg_replace(MATCH_INCLUDES, '', $text);
    $text = preg_replace('/\bnamespace CodeRage;/', 'namespace Makeme;', $text);
    $text = preg_replace('/\bCodeRage\\\/', 'Makeme\\', $text);
    return $text;
}

/**
 * Removes comments from the given PHP text.
 *
 * @param string $text
 * @return string
 */
function stripComments($text)
{
    $text = preg_replace('#\s*\n\s*/\*\*.*?\*/\s*\n#s', "\n", $text);
    $text = preg_replace('#/\*\*.*?\*/\s*\n#s', "\n", $text);
    $text = preg_replace('#^\s*//.*\n#m', '', $text);
    return $text;
}

/**
 * Replaces spaces with tabs in the given PHOP text.
 *
 * @param string $text
 * @return string
 */
function tabify($text)
{
    for ($z = 20; $z != 0; --$z) {
        $search = str_repeat('    ', $z);
        $replace = str_repeat("\t", $z);
        $text = preg_replace("/^$search/m", $replace, $text);
    }
    return $text;
}

/**
 * Prints to given error message and exits with status 1.
 *
 * @param string $error
 */
function exit_($error)
{
    echo "ERROR: $error\n";
    exit(1);
}

?>
