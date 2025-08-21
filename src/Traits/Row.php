<?php

namespace Timurikvx\Filebase\Traits;
trait Row
{
    protected function getRow(string $string): array|null
    {
        $line = json_decode($string, true);
        if (is_null($line)){
            return null;
        }
        $this->getDate($line);
        return $line;
    }

    private function getDate(array &$line): void
    {
        foreach ($line as $key => $field){
            if(is_array($field) && array_key_exists('date', $field)){
                $line[$key] = new \DateTime($line[$key]['date']);
            }
        }
    }
}