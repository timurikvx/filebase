<?php

namespace Timurikvx\Filebase;

use Symfony\Component\Filesystem\Filesystem;

class Filebase
{

    private string $dir;

    private string $key = '';

    private string $keyfile;

    private Filesystem $filesystem;

    public function __construct(string $dir, string $key = '')
    {
        $this->key = $key;
        $slash = php_uname('s') == 'Linux'? '/': '\\';
        $replace = php_uname('s') == 'Linux'? '\\': '/';
        $this->filesystem = new Filesystem();
        $this->dir = str_ends_with($dir, '/')? substr($dir, 0, strlen($dir) - 1): $dir;
        $this->dir = str_replace($slash, $replace, $this->dir);
        $this->keyfile = '\\keys\\keys.bin';
        $this->filesystem->mkdir($this->dir);
    }

    public function createTable(string $table, array $indexes = [], string $crypto = ''): Table
    {
        $dir = $this->dir.'/'.$table.'/';
        return Table::create($dir, $indexes, $crypto);
    }

    public function table(string $table): Table|null
    {
        $filename = $this->dir.'/'.$table.'/';
        if(!file_exists($filename.'data.bin')){
            return null;
        }
        return new Table($filename);
    }

    public function getByKey(string $key, $default = null): mixed
    {
        $storage = new KeyStorage($this->dir, $this->keyfile, $this->key);
        return $storage->get($key, $default);
    }

    public function setByKey(string $key, mixed $value): void
    {
        $storage = new KeyStorage($this->dir, $this->keyfile, $this->key);
        $storage->set($key, $value);
    }

}