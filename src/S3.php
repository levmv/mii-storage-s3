<?php

namespace levmorozov\s3storage;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use mii\storage\FileSystemInterface;
use mii\storage\Storage;

class S3 extends Storage implements FileSystemInterface
{
    protected $region = 'eu-central-1';
    protected $bucket = '';

    protected $key = '';
    protected $secret = '';

    /**
     * @var S3Client
     */
    protected $s3;

    public function init($config)
    {
        parent::init($config);

        $credentials = new Credentials($this->key, $this->secret);

        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => $credentials
        ]);
    }

    public function exist(string $path)
    {
        return $this->s3->doesObjectExist($this->bucket, $this->clean($path));
    }

    public function get(string $path)
    {
        return $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);
    }

    public function put(string $path, $content)
    {
        return $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path),
            'Body' => $content
        ]);
    }

    public function delete(string $path)
    {
        $this->s3->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);
    }

    public function size(string $path)
    {
        return $this->get($path)['ContentLength'];
    }

    public function modified(string $path)
    {
        return strtotime($this->get($path)['@metadata']['headers']['last-modified']);
    }

    public function copy(string $from, string $to)
    {
        $this->s3->copyObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($to),
            'CopySource' => $this->bucket . '/' . $this->clean($from)
        ]);
    }

    public function move(string $from, string $to)
    {
        $this->copy($from, $to);
        $this->delete($from);
    }

    public function url(string $path)
    {
        return $this->get($path)['@metadata']['effectiveUri'];
    }

    public function files(string $path)
    {
        $result = $this->s3->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => $this->clean($path)
        ]);

        return array_map(function ($object) {return $object['Key'];}, $result['Contents']);
    }

    public function mkdir(string $path, $mode = 0777)
    {
        // https://stackoverflow.com/questions/38965266/how-to-create-a-folder-within-s3-bucket-using-php
    }

    protected function clean(string $path)
    {
        if (strpos($path, '/') === 0)
            return substr($path, 1);
        return $path;
    }
}