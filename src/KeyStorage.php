<?php

namespace Timurikvx\Filebase;

use Symfony\Component\Filesystem\Filesystem;

class KeyStorage
{
    private string $filename;

    private Filesystem $filesystem;

    public function __construct(string $dir, string $file)
    {
        $this->filesystem = new Filesystem();
        $this->filename = $dir.$file;
        if(!file_exists($this->filename)){
            file_put_contents($this->filename, '');
        }
    }

    public function get(string $key, $default = null): mixed
    {
        $data = $this->getData();
        if(!key_exists($key, $data)){
            return $default;
        }
        return $data[$key];
    }

    public function set(string $key, mixed $value): void
    {
        $data = $this->getData();
        $data[$key] = $value;
        file_put_contents($this->filename, json_encode($data));
    }

    private function getData(): array
    {
        if(!$this->filesystem->exists($this->filename)){
            file_put_contents($this->filename, '');
        }
        $content = file_get_contents($this->filename);
        if(empty($content)){
            $data = [];
        }else{
            $data = json_decode($content, true);
        }
        return $data;
    }
}