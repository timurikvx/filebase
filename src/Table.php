<?php

namespace Timurikvx\Filebase;

use Symfony\Component\Filesystem\Filesystem;
use Timurikvx\Filebase\Table\Index;
use Timurikvx\Filebase\Table\IndexRow;

class Table
{

    protected string $dir;

    protected string $filename;

    protected string $fileindex;

    protected array $indexes;

    protected Filesystem $filesystem;

    protected Index $index;

    protected string $key;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
        $this->filename = $this->dir.'data.bin';
        $this->fileindex = $this->dir.'indexes.bin';
        $this->filesystem = new Filesystem();
        $this->indexes = $this->readIndexes();
        $this->index = new Index($this);
    }

    public static function create(string $dir, array $indexes = [], string $key = ''): Table
    {
        $table = new self($dir);
        $table->indexes = $indexes;
        $table->fileindex = $table->dir.'indexes.bin';
        $table->key = $key;
        $table->createTable();
        return $table;
    }

    protected function createTable(): void
    {
        if(!file_exists($this->filename)){
            $this->filesystem->dumpFile($this->filename, '');
        }
        $this->createIndex();
    }

    public function id(): Table
    {
        $this->indexes[] = 'id';
        $this->createIndex();
        return $this;
    }

    public function indexes(): array
    {
        return $this->indexes;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function insert(array $data, bool $id = true): int
    {
        $line = $this->add($data);
        $this->updateIndex($data, $line);
        return $line;
    }

    protected function add(array &$data, bool $id = true): int
    {
        $file = new \SplFileObject($this->dir.'data.bin', 'r');
        $file->seek(PHP_INT_MAX);
        $line = $file->key();
        $file = null;
        if($id){
            $data['id']= $this->generateUuid();
        }
        $string = json_encode($data);
        file_put_contents($this->dir.'data.bin', $string.PHP_EOL, FILE_APPEND);
        return $line;
    }

    public function update(array $data): bool
    {
        if(!key_exists('id', $data)){
            return false;
        }
        $keys = $this->query()->filter();
        if(empty($keys)){
            return false;
        }
        return $this->updateLineByNumber($this->dir.'data.bin', $keys[0], json_encode($data));
    }

    protected function updateLineByNumber(string $filename, int $lineNumber, string $newContent): bool
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        if ($lineNumber > count($lines) - 1) {
            return false;
        }
        $lines[$lineNumber] = $newContent;
        file_put_contents($filename, implode(PHP_EOL, $lines) . PHP_EOL);
        return true;
    }

    protected function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function reindex(): void
    {
        foreach ($this->indexes as $index){
            $this->index->reindex($index);
        }
    }

    protected function createIndex(): void
    {
        foreach ($this->indexes as $index){
            $this->index->create($index);
        }
        $this->createIndexesFile();
    }

    protected function readIndexes(): array
    {
        if(!file_exists($this->filename)){
            return [];
        }
        $string = file_get_contents($this->fileindex);
        return json_decode($string, true);
    }

    protected function createIndexesFile(): void
    {
        file_put_contents($this->fileindex, json_encode($this->indexes));
    }

    protected function updateIndex(array $data, int $line): void
    {
        $indexes = $this->indexes;
        foreach ($indexes as $index){
            $this->index->update($index, $line, $data);
        }
    }

    public function query(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

}