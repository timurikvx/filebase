<?php

namespace Timurikvx\Filebase;

use Symfony\Component\Filesystem\Filesystem;

class Filebase
{

    private string $dir;

    private string $keyfile;

    private Filesystem $filesystem;

    public function __construct(string $dir)
    {
        $slash = php_uname('s') == 'Linux'? '/': '\\';
        $replace = php_uname('s') == 'Linux'? '\\': '/';
        $this->filesystem = new Filesystem();
        $this->dir = str_ends_with($dir, '/')? substr($dir, 0, strlen($dir) - 1): $dir;
        $this->dir = str_replace($slash, $replace, $this->dir);
        $this->keyfile = '\\keys_data\\keys.bin';
        $this->filesystem->mkdir($this->dir);
    }

//    public function setTable(string $table): Table
//    {
//        $filename = $this->dir.'/'.$table.'.bin';
//        return new Table($filename);
//    }

    public function getByKey(string $key, $default = null): mixed
    {
        $storage = new KeyStorage($this->dir, $this->keyfile);
        return $storage->get($key, $default);
    }

    public function setByKey(string $key, mixed $value): void
    {
        $storage = new KeyStorage($this->dir, $this->keyfile);
        $storage->set($key, $value);
    }

}