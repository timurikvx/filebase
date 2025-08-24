<?php

namespace Timurikvx\Filebase;

use DateTime;
use Timurikvx\Filebase\Table\Index;
use Timurikvx\Filebase\Table\IndexRow;
use Timurikvx\Filebase\Traits\Row;

class QueryBuilder
{
    use Row;

    private Table $table;

    private array $fields = [];

    private array $sort = [];

    private array $where = [];

    private array $whereNull = [];

    private int $limit = 0;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function select(array|string $fields): QueryBuilder
    {
        if(is_array($fields)){
            $this->fields = $fields;
        }
        if(is_string($fields)){
            $this->fields = array_map(fn($item)=>trim($item), explode(', ', $fields));
        }
        return $this;
    }

    public function sortBy(string|array $sort): QueryBuilder
    {
        if(is_array($sort)){
            foreach($sort as $sortItem){
                $this->addStringSort($sortItem);
            }
        }
        if(is_string($sort)){
            $sorts = explode(',', $sort);
            foreach($sorts as $sortItem){
                $this->addStringSort(trim($sortItem));
            }
        }
        return $this;
    }

    private function addStringSort(string $sort): void
    {
        $values = explode(' ', $sort);
        $asc = SORT_ASC;
        if(count($values) > 1){
            $asc = ('asc' == strtolower($values[1]))? SORT_ASC : SORT_DESC;
        }
        $this->sort[] = [trim($values[0]), $asc];
    }

    public function limit(int $limit): QueryBuilder
    {
        $this->limit = $limit;
        return $this;
    }

    //////////////////////////////// WHERE /////////////////////////////////////////

    public function where(string $field, string $equal, mixed $value): QueryBuilder
    {
        if(($equal == '<>' || $equal == '!=') && $value == null){
            $this->whereNull[] = [$field];
        }else{
            $this->where[] = [$field, $equal, $value];
        }
        return $this;
    }

    public function whereBetween(string $field, float|int $start, float|int $end): QueryBuilder
    {
        $this->where[] = [$field, 'between', [$start, $end]];
        return $this;
    }

    public function whereNotNull(string $field): QueryBuilder
    {
        $this->where[] = [$field, '!=', null];
        return $this;
    }

    public function whereNull(string $field): QueryBuilder
    {
        if(empty($field)){
            return $this;
        }
        $this->whereNull[] = [$field];
        return $this;
    }

    /////////////////////////////// GET ////////////////////////////////

    public function get(): array
    {
        $keys = $this->filter();
        $sorted = $this->sort($keys);
        return $this->getRows($sorted);
    }

    public function first(): array|null
    {
        $rows = $this->get();
        return (count($rows) === 0) ? null : $rows[0];
    }

    public function find(string $id): ?array
    {
        $this->where = [['id', '=', $id]];
        return $this->first();
    }

    ///////////////////////////// DELETE /////////////////////////

    public function delete(): void
    {
        $filename = $this->table->getDir().'data.bin';
        $keys = $this->filter();
        $deleted = $this->deleteLines($filename, $keys);
        if($deleted){
            $this->table->reindex();
        }
    }

    public function deleteByID(string $id): void
    {
        $this->where = [['id', '=', $id]];
        $this->delete();
    }

    //////////////////////////////////////////////////////////////

    private function getRows(array $keys): array
    {
        $lines = [];
        $filename = $this->table->getDir().'data.bin';
        $file = new \SplFileObject($filename, 'r');
        $limit = 1;
        foreach ($keys as $key){
            $file->seek($key);
            $row = $this->getRow($file->current());
            if(is_null($row)){
                continue;
            }
            $lines[] = $this->getColumns($row);
            if($this->limit > 0 && $limit >= $this->limit ){
                break;
            }
            $limit++;
        }
        return $lines;
    }

    private function getColumns(array $row): array
    {
        $columns = [];
        if(count($this->fields)  == 0){
            return $row;
        }
        foreach($this->fields as $field){
            $columns[$field] = key_exists($field, $row) ? $row[$field] : null;
        }
        return $columns;
    }

    public function filter(): array
    {
        $arrays = [];
        $indexes = $this->table->indexes();
        if(count($this->where) == 0 && count($this->whereNull) == 0){
            return $this->all();
        }
        foreach ($indexes as $index){
            $wheres = [];
            foreach ($this->where as $where){
                if($where[0] == $index){
                    $wheres[] = $where;
                }
            }
            $arrayNullValues = $this->searchNull($index);
            if(!empty($arrayNullValues)){
                $arrays[] = $arrayNullValues;
            }
            $array = $this->search($index, $wheres);
            if(!empty($array)){
                $arrays[] = $array;
            }else{
                $arrays = [];
            }
        }
        return $this->getKeys($arrays);
    }

    private function all(): array
    {
        $filename = $this->table->getDir().'data.bin';
        $file = new \SplFileObject($filename, 'r');
        $file->seek(PHP_INT_MAX);
        $line = $file->key();
        return range(0, $line - 1);
    }

    private function sort(array $keys): array
    {
        $indexes = $this->table->indexes();
        $indexFile = new Index($this->table);
        $sorted = [];
        $fields = [];
        if(count($this->sort) == 0){
            return $keys;
        }
        foreach ($this->sort as $sort){
            $index = $sort[0];
            if(!in_array($index, $indexes)){
                continue;
            }
            $fields[$index] = $sort[1];
            $file = $indexFile->read($index);
            foreach ($keys as $key){
                if (!key_exists($key, $file)){
                    $sorted[$key] = [$sort[0]=>null];
                    continue;
                }
                if(key_exists($key, $sorted)){
                    $sorted[$key][$sort[0]] = $file[$key];
                }else{
                    $sorted[$key] = [$sort[0]=>$file[$key]];
                }
            }
        }
        $this->sortByMultipleFields($sorted, $fields);
        return array_keys($sorted);
    }

    private function getKeys(array $arrays): array
    {
        if(count($arrays) == 0){
            return [];
        }
        $commonKeys = array_reduce(
            $arrays,
            function($carry, $array) {
                return $carry === null ? array_keys($array) : array_intersect($carry, array_keys($array));
            }
        );
        return array_intersect_key($arrays[0], array_flip($commonKeys));
    }

    private function search(string $index, $wheres = []): array
    {
        $file = new \SplFileObject($this->table->getDir().$index.'_index.bin', 'r');
        $filtered = [];
        if(count($wheres) == 0){
            return $filtered;
        }
        foreach ($file as $row){
            if(empty($row)){
                continue;
            }
            $indexRow = new IndexRow($row);
            foreach ($wheres as $where){
                $result = $this->condition($indexRow->value(), $where);
                if(!$result){
                    continue;
                }
                $filtered[] = $indexRow->key();
            }
        }
        return $filtered;
    }

    private function searchNull(string $index): array
    {
        $filtered = [];
        if(count($this->whereNull) == 0){
            return [];
        }
        foreach ($this->whereNull as $where){
            if($where[0] != $index){
                continue;
            }
            $file = new \SplFileObject($this->table->getDir().$index.'_index.bin', 'r');
            foreach ($file as $row){
                if(empty($row)){
                    continue;
                }
                $indexRow = new IndexRow($row);
                $filtered[] = $indexRow->key();
            }
        }
        $all = $this->all();
        return array_merge(
            array_diff($filtered, $all),
            array_diff($all, $filtered)
        );
    }

    private function sortByMultipleFields(array &$array, array $sortCriteria): void
    {
        uasort($array, function($a, $b) use ($sortCriteria) {
            foreach ($sortCriteria as $field => $direction) {
                $valueA = $a[$field] ?? null;
                $valueB = $b[$field] ?? null;
                if ($valueA != $valueB) {
                    return $direction === SORT_DESC ? $valueB <=> $valueA : $valueA <=> $valueB;
                }
            }
            return 0;
        });
    }

    private function condition(mixed $value, array $where): bool
    {
        $comparable = $where[2];
        $equal = $where[1];
        if($comparable instanceof DateTime){
            $comparable = $comparable->getTimestamp();
        }
        if($value instanceof DateTime){
            $value = $value->getTimestamp();
        }
        if($equal == '='){
            return $value == $comparable;
        }
        if($equal == '>'){
            return floatval($value) > $comparable;
        }
        if($equal == '<'){
            return floatval($value) < $comparable;
        }
        if($equal == '>='){
            return floatval($value) >= $comparable;
        }
        if($equal == '<='){
            return floatval($value) <= $comparable;
        }
        if($equal == '!='){
            return $value != $comparable;
        }
        if($equal == '<>'){
            return $value != $comparable;
        }
        if($equal == 'in'){
            return in_array($value, $comparable);
        }
        if($equal == 'not in'){
            return !in_array($value, $comparable);
        }
        if($equal == 'between'){
            $eq1 = $value >= $comparable[0];
            $eq2 = $value <= $comparable[1];
            return $eq1 && $eq2;
        }
        return false;
    }

    private function deleteLines($filename, array $lines): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'temp_');

        $input = fopen($filename, 'r');
        $output = fopen($tempFile, 'w');

        $currentLine = 0;
        $deleted = false;

        while (($line = fgets($input)) !== false) {
            if (!in_array($currentLine, $lines)) {
                fwrite($output, $line);
            } else {
                dump($line);
                $deleted = true;
            }
            $currentLine++;
        }

        fclose($input);
        fclose($output);
        if (!$deleted) {
            unlink($tempFile);
            return false;
        }
        rename($tempFile, $filename);
        return $deleted;
    }

}