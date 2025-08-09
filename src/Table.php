<?php

namespace Timurikvx\Filebase;

use Symfony\Component\Filesystem\Filesystem;
use Timurikvx\Filebase\Crypto\Encrypt;

class Table
{

    private string $filename;

    private Filesystem $filesystem;

    private string $key;

    public function __construct(string $filename, string $key = '')
    {
        $this->filename = $filename;
        $this->key = $key;
        if(!file_exists($filename)){
            file_put_contents($filename, '');
        }
    }

    public function select()
    {

    }

    public function insert(array $data): void
    {
        //INSERT INTO table_name (column1, column2, column3, ...) VALUES (value1, value2, value3, ...);
        $string = json_encode($data);
        $encrypted = Encrypt::encryptString($string, $this->key);
    }

    public function delete()
    {

    }

    public function update()
    {

    }

}