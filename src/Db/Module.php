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
use CodeRage\Build\ProjectConfig;
use CodeRage\Build\Engine;
use CodeRage\Config;
use CodeRage\Db;
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
        // Generate schema
        $doc = $this->generateDatasourceDefinition($engine);
        $path = Config::projectRoot() . '/' . self::SCHEMA_PATH;
        File::mkdir(dirname($path));
        file_put_contents($path, $doc->saveXml());
        $engine->recordGeneratedFile($path);

        // Generate runtime configuration
        $ds = Parser::parseDataSourceXml($path);
        $name = $ds->name();
        $params = $ds->params();
        $options = [];
        foreach (
            [ 'dbms', 'host', 'port', 'username', 'password',
              'database', 'options']
            as $opt)
        {
            $options[$opt] = $params->$opt();
        }
        $path = Config::projectRoot() . "/.coderage/db/$name.json";
        File::mkdir(dirname($path));
        file_put_contents($path, json_encode($options));
        $engine->recordGeneratedFile($path);
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
        $doc = Xml::loadDocument(self::TEMPLATE_PATH, self::METASCHEMA_PATH);
        $db = Xml::firstChildElement($doc->documentElement, 'database');
        foreach ($engine->moduleStore()->modules() as $module) {
            foreach ($module->tables() as $def) {
                $inner = Xml::loadDocument($def, self::METASCHEMA_PATH);
                $tables = $doc->importNode($inner->documentElement, true);
                foreach (Xml::childElements($tables, 'table') as $table)
                    $db->appendChild($table);
            }
        }
        return $doc;
    }

    /**
     * Returns the value of the named configuration variable
     *
     * @param CodeRage\Build\ProjectConfig $config
     * @param string $name
     * @return string
     * @throws CodeRage\Build\Error
     */
    private function getProperty(ProjectConfig $config, string $name)
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
