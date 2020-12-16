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

namespace CodeRage\Web;

use DOMDocument;
use CodeRage\Build\ProjectConfig;
use CodeRage\Build\Engine;
use CodeRage\Config;
use CodeRage\File;
use CodeRage\Util\Os;

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
    private const METASCHEMA_PATH  = __DIR__ . '/Schema/dataSource.xsd.dsx';

    /**
     * Constructs an instance of CodeRage\Access\Module
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

    public function build(Engine $engine)
    {
        $doc = $this->generateDatasourceDefinition($engine);
        $path = Config::projectRoot() . '/' . self::SCHEMA_PATH;
        File::mkdir(dirname($path));
        File::generate($path, $doc->saveXml(), 'xml');
        $engine->recordGeneratedFile($path);
    }

    public function install(Engine $engine)
    {
        // Create database
        $config = $engine->projectConfig();
        $options = ['xmltodb', '--create', '--non-interactive'];
        $options[] = '--username';
        $options[] = '%DATABASE_ADMIN_USERNAME';
        $options[] = '--password';
        $options[] = '%DATABASE_ADMIN_PASSWORD';
        foreach ($config->propertyNames() as $name) {
            if ($name == 'project_root')
                continue;
            $value = $this->getProperty($config, $name);
            $options[] = '--config';
            $options[] = escapeshellarg("$name=$value");
        }
        $options[] = dirname(__DIR__) . '/../Db/default.dsx';
        $command = join(' ', $options);
        $engine->log()->logMessage("running $command");
        $status = null;
        setenv("DATABASE_ADMIN_USERNAME=$dbUsername");
        setenv("DATABASE_ADMIN_PASSWORD=$dbPassword");
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
                   'username' => $dbUsername,
                   'password' => $dbPassword
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
        $root = $doc->documentElement;
        foreach ($engine->moduleStore()->modules() as $module) {
            if ($module->tables() == null)
                continue;
            $tables = Xml::loadDocument($module->tables(), self::METASCHEMA_PATH);
            $tables = $doc->importNode($tables, true);
            foreach (Xml::childElements($tables->documentElement) as $elt)
                $root->appendChild($elt);
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
        $prop = $config->lookupProperty();
        if ($prop === null)
            throw new
                Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'message' => "No such property: $name"
                ]);
        return $prop->value();
    }
}
