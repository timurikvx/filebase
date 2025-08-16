<?php

namespace Timurikvx\Filebase;

use Symfony\Component\Filesystem\Filesystem;
use Timurikvx\Filebase\Crypto\Crypto;

class KeyStorage
{
    private string $filename;

    private string $key;

    private Filesystem $filesystem;

    private Crypto $crypto;

    public function __construct(string $dir, string $file, string $key = '')
    {
        $this->key = $key;
        $this->crypto = new Crypto();
        $this->filesystem = new Filesystem();
        $this->filename = $dir.$file;
        if(!file_exists($this->filename)){
            $this->filesystem->dumpFile($this->filename, '');
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

    public function set(string $key, mixed $value = ''): void
    {
        $data = $this->getData();
        $data[$key] = $value;
        $this->setData($data);
    }

    private function getData(): array
    {
        if(!$this->filesystem->exists($this->filename)){
            file_put_contents($this->filename, '');
        }
        $content = file_get_contents($this->filename);
        if(!empty($this->key)){
            $content = $this->crypto->decrypt($content, $this->key);
        }
        if(empty($content)){
            $data = [];
        }else{
            $data = json_decode($content, true);
        }
        return $data;
    }

    private function setData(array $data): void
    {
        $value = json_encode($data);
        if(!empty($this->key)){
            $value = $this->crypto->encrypt($value, $this->key);
        }
        file_put_contents($this->filename, $value);
    }
}