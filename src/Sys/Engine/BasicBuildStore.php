<?php

/**
 * Defines the class CodeRage\Sys\Engine\BasicBuildStore
 *
 * File:        CodeRage/Sys/Engine/BasicBuildStore.php
 * Date:        Thu Nov 19 00:43:23 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use Traversable;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Args;
use CodeRage\Util\Random;

/**
 * An implementation of CodeRage\Sys\BuildStoreInterface
 */
class BasicBuildStore extends IteratorAggregate implements BuildStoreInterface
{
    /**
     * @var int
     */
    private const PATH_LENGTH = 50;

    /**
     * Constructs an instance of CodeRage\Sys\Engine\BasicBuildStore with the
     * given root directory
     *
     * @param string $root The root directory
     */
    public function __construct(string $root)
    {
        $this->root = $root;
        if (file_exists($this->root)) {
            $this->readonly = !is_writable($this->root);
        } else {
            $dir = dirname($this->root);
            $this->readonly = !file_exists($dir) || !is_writable($dir);
        }
    }

    public function createFileEntry(string $key, string $content): string
    {
        $func =
            function ($p) use($content)
            {
                file_put_contents($p, $content);
            };
        return $this->createEntry($key, $func);
    }

    public function createDirectoryEntry(string $key): string
    {
        return $this->createEntry($key, function ($p) { File::mkdir($p); });
    }

    public function getEntry(string $key): ?string
    {
        return $this->loadIndex()[$key] ?? null;
    }

    public function deleteEntry(string $key): void
    {
        $this->checkWriteable();
        $entries = $this->loadIndex();
        $path = $entries[$key] ?? null;
        if ($path !== null) {
            File::rm($path);
            delete($entries[$key]);
            $this->saveIndex($entries);
        }
    }

    public function clear(): void
    {
        $this->checkWriteable();
        $entries = $this->loadIndex();
        foreach ($entries as $key => $path) {
            File::rm($path);
            delete($entries[$key]);
            $this->saveIndex($entries);
        }
    }

    public function copy(BuildStoreInterface $target): void
    {
        $target->clear();
        foreach ($this as $key => $path) {
            File::checkReadable($path, 0b0100);
            if (is_file($path)) {
                $target->createFileEntry($key, file_get_contents($path));
            } else {
                File::copy($path, $target->createDirectoryEntry($key));
            }
        }
    }

    public function getIterator(): Traversable
    {
        return new \Arrayiterator($this->loadIndex());
    }

    private function createEntry(string $key, callable $func): string
    {
        $this->checkWriteable();
        $entries = $this->loadIndex();
        $path = $entries[$key] ?? null;
        if ($path === null) {
            $path = self::randomPath();
            $func($path);
            $entries[$key] = $path;
            $this->saveIndex($entries);
        } else {
            throw new Error([
                'status' => 'OBJECT_EXISTS',
                'details' => "Entry '$key' already exists"
            ]);
        }
        return $path;
    }

    private function loadIndex(): array
    {
        $path = $this->indexPath();
        if (is_file($path)) {
            File::checkFile($path, 0b0100);
            $content = file_get_contents($path);
            $entries = json_decode($content, true);
            Args::check($entries, 'map[string]', 'index entries');
            return $entries;
        } else {
            return [];
        }
    }

    private function saveIndex(array $entries): void
    {
        $this->checkWriteable();
        file_put_contents(
            $this->indexPath(),
            json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function indexPath(): string
    {
        return File::join($this->root,  'index.json');
    }

    private function checkWriteable()
    {
        if ($this->readonly) {
            throw new Error([
                'status' => 'UNSUPPORTED_OPERATION',
                'details' => 'Build store is read-only'
            ]);
        }
    }

    private static function randomPath():string
    {
        return Random::string(self::PATH_LENGTH, Random::ALNUM);
    }

    /**
     * @var string
     */
    private $root;

    /**
     * @var boolean
     */
    private $readonly;
}
