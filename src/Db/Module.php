<?php

/**
 * Defines the class CodeRage\Db\Module
 *
 * File:        CodeRage/Db/Module.php
 * Date:        Wed Dec 16 19:52:11 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db;

use DOMDocument;
use DOMElement;
use CodeRage\Build\BuildConfig;
use CodeRage\Build\Engine;
use CodeRage\Config;
use CodeRage\Db;
use CodeRage\Db\Params;
use CodeRage\Db\Schema\Generator;
use CodeRage\Db\Schema\Parser;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Os;
use CodeRage\Xml;

/**
 * Database module
 */
final class Module extends \CodeRage\Build\BasicModule {
    use \CodeRage\Xml\ElementCreator;

    /**
     * @var string
     */
    private const SCHEMA_PATH  = '.coderage/db/schema.dsx';

    /**
     * @var string
     */
    private const TEMPLATE_PATH  = __DIR__ . '/template.dsx';

    /**
     * @var string
     */
    private const METASCHEMA_PATH  = __DIR__ . '/Schema/dataSource.xsd';

    /**
     * Constructs an instance of CodeRage\Db\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'Database',
            'description' => 'Database module'
        ]);
    }

    public function build(Engine $engine): void
    {
        $doc = $this->generateDatasourceDefinition($engine);
        $doc->formatOutput = true;
        $path = Config::projectRoot() . '/' . self::SCHEMA_PATH;
        File::mkdir(dirname($path));
        file_put_contents($path, $doc->saveXml());
        $engine->recordGeneratedFile($path);
    }

    protected function namespaceUril(): ?string
    {
        return \CodeRage\Build::NAMESPACE_URI;
    }

    public function install(Engine $engine): void
    {
        // Check if database exists
        $config = $engine->projectConfig();
        $database = $this->getProperty($config, 'db.database');
        if (in_array($database, Operations::listDatabases()))
            return;

        // Create database
        $options = ['xmltodb', '--create', '--non-interactive'];
        $options[] = '--username';
        $options[] = '%DATABASE_ADMIN_USERNAME';
        $options[] = '--password';
        $options[] = '%DATABASE_ADMIN_PASSWORD';
        foreach ($config->propertyNames() as $name) {
            $value = $this->getProperty($config, $name);
            $options[] = '--config';
            $options[] = escapeshellarg("$name=$value");
        }
        $options[] = Config::projectRoot() . '/' . self::SCHEMA_PATH;
        $command = join(' ', $options);
        $engine->log()->logMessage("running $command");
        $status = null;
        Os::run($command, $status);
        if ($status != 0)
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' => 'Failed creating database'
                ]);

        // Grant permission
        $host = $this->getProperty($config, 'db.host');
        $database = $this->getProperty($config, 'db.database');
        $username = $this->getProperty($config, 'db.username');
        $password = $this->getProperty($config, 'db.password');
        $db =
           new Db([
                   'dbms' => 'mysql',
                   'host' => $host,
                   'database' => $database,
                   'username' => getenv('DATABASE_ADMIN_USERNAME'),
                   'password' => getenv('DATABASE_ADMIN_PASSWORD')
               ]);
        $sql =
            "GRANT ALL PRIVILEGES ON $database.*
             TO %s@%s IDENTIFIED BY '$password';";
        $db->query($sql, $username, '%');
    }

    /**
     * Generates and stores datasource definition
     *
     * @return DOMDocument
     */
    private function generateDatasourceDefinition(Engine $engine): DOMDocument
    {
        $doc =
            Xml::loadDocument(self::TEMPLATE_PATH, null, [
                'preserveWhitespace' => false
            ]);
        $doc->formatOutput = true;
        $root = $doc->documentElement;
        $root->insertBefore(
            $this->generateParamsDefinition($engine, $doc),
            Xml::firstChildElement($root)
        );
        $db = Xml::firstChildElement($root, 'database');
        foreach ($engine->moduleStore()->modules() as $module) {
            foreach ($module->tables() as $def) {
                $inner =
                    Xml::loadDocument($def, self::METASCHEMA_PATH, [
                        'preserveWhitespace' => false
                    ]);
                $tables = $doc->importNode($inner->documentElement, true);
                foreach (Xml::childElements($tables, 'table') as $table)
                    $db->appendChild($table);
            }
        }
        return $doc;
    }

    /**
     * Generates the XML definition of the database connection parameters
     *
     * @return DOMElement
     */
    private function generateParamsDefinition(
        Engine $engine,
        DOMDocument $doc
    ): DOMElement {
        $params = Params::create($engine->projectConfig());
        $elt = $this->createElement($doc, 'connectionParams');
        $names = ['dbms', 'host', 'port', 'username', 'password', 'options'];
        foreach ($names as $name) {
            if (($value = $params->$name()) !== null) {
                if ($name !== 'options') {
                    $this->appendElement($elt, $name, "{config.db.$name}");
                } elseif (!empty($value)) {
                    $options = $this->appendElement($elt, 'options');
                    foreach ($value as $n => $v) {
                        $option = $this->appendElement($options, 'option', $v);
                        $option->setAttribute('name', $n);
                    }
                }
            }
        }
        return $elt;
    }

    /**
     * Returns the value of the named configuration variable
     *
     * @param CodeRage\Build\BuildConfig $config
     * @param string $name
     * @return string
     * @throws CodeRage\Build\Error
     */
    private function getProperty(BuildConfig $config, string $name)
    {
        $prop = $config->lookupProperty($name);
        if ($prop === null)
            throw new
                Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'message' => "No such property: $name"
                ]);
        return $prop->value();
    }
}
