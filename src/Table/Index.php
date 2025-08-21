<?php

namespace Timurikvx\Filebase\Table;

use Symfony\Component\Filesystem\Filesystem;
use Timurikvx\Filebase\Table;
use Timurikvx\Filebase\Traits\Row;

class Index
{

    use Row;

    private Table $table;

    private Filesystem $filesystem;

    public function __construct(Table $table)
    {
        $this->table = $table;
        $this->filesystem = new Filesystem();
    }

    public function create(string $index): void
    {
        if(file_exists($this->table->getDir().$index.'_index.bin')){
            return;
        }
        $this->filesystem->dumpFile($this->table->getDir().$index.'_index.bin', '');
    }

    public function update(string $index, int $line, array $data): void
    {
        $lines = $this->read($index);
        $value = $this->value($index, $data);
        if(is_null($value)){
            return;
        }
        $lines[$line] = $value;
        $this->write($index, $lines);
    }

    public function reindex(string $index): void
    {
        $this->create($index);
        $file = new \SplFileObject($this->table->getDir().'data.bin', 'r');
        $lines = [];
        foreach ($file as $line => $row){
            $data = $this->getRow($row);
            if(is_null($data)){
                continue;
            }
            $value = $this->value($index, $data);
            if(is_null($value)){
                continue;
            }
            $lines[$line] = $value;
        }
        $this->write($index, $lines);
    }

    public function read(string $index): array
    {
        $array = file($this->table->getDir().$index.'_index.bin', FILE_IGNORE_NEW_LINES);
        $lines = [];
        foreach ($array as $string){
            if(empty($string)){
                continue;
            }
            $row = new IndexRow($string);
            $lines[$row->key()] = $row->value();
        }
        return $lines;
    }

    public function value(string $index, array $data)
    {
        if(!key_exists($index, $data)){
            return null;
        }
        return $data[$index];
    }

    public function write(string $index, array $lines): void
    {
        $text = '';
        asort($lines);
        foreach ($lines as $key => $value){
            $type = str_pad($this->getType($value), 14);
            if($value instanceof \DateTime){
                $value = $value->getTimestamp();
            }
            $text .= str_pad($key, 12).$type.'  '.$value.PHP_EOL;
        }
        file_put_contents($this->table->getDir().$index.'_index.bin', $text);
    }

    private function getType(mixed $value): string
    {
        if(is_float($value)){
            return 'float';
        }
        if(is_int($value)){
            return 'int';
        }
        if(is_bool($value)){
            return 'bool';
        }
        if($value instanceof \DateTime){
            return 'datetime';
        }
        return 'string';
    }

}