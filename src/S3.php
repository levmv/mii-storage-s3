<?php

namespace levmorozov\s3storage;

use mii\core\Exception;
use mii\storage\FileSystemInterface;
use mii\storage\Storage;

class S3 extends Storage implements FileSystemInterface
{
    protected $region = 'eu-central-1';
    protected $bucket = '';

    protected $key = '';
    protected $secret = '';

    // custom endpoint
    protected $endpoint;

    /**
     * @var \levmorozov\s3\S3
     */
    protected $s3;

    public function init($config) {
        parent::init($config);
        $this->s3 = new \levmorozov\s3\S3($this->key, $this->secret, $this->endpoint, $this->region);
    }

    public function exist(string $path) {

        $response = $this->s3->getObjectInfo([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if($response['error'])
            return false;

        return true;
    }

    // Warning: This method loads the entire downloadable contents into memory!
    public function get(string $path) {

        $response = $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if($response['error']) {
            if($response['error']['code'] === 'NoSuchKey')
                $this->error($response['error']);
            return false;
        }

        return (string)$response['body'];
    }

    /**
     * @param string $path
     * @param $content Content of the file. May be a resource returned from an fopen call
     * @return int|bool
     */
    public function put(string $path, $content) {

        $response = $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path),
            'Body' => $content
        ]);
        if($response['error']) {
            return $this->error($response['error']);
        }
        return 1;
    }

    public function delete(string $path) {

        $response = $this->s3->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if($response['error']) {
            return $this->error($response['error']);
        }
        return true;
    }

    public function size(string $path) {

        $response = $this->s3->getObjectInfo([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if($response['error'])
            return false;

        $length = $response['headers']['content-length'] ?? null;

        return $length !== null ? (int) $length : false;
    }

    public function modified(string $path) {

        $response = $this->s3->getObjectInfo([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if($response['error'])
            return false;

        $date = $response['headers']['last-modified'] ?? null;

        return $date !== null ? strtotime($date) : false;
    }

    public function copy(string $from, string $to) {
        throw new Exception("Not implemented yet");
    }

    public function move(string $from, string $to) {
        $this->copy($from, $to);
        $this->delete($from);
    }

    public function url(string $path) {
        return $this->get_object($path)['@metadata']['effectiveUri'];
    }

    public function files(string $path) {
        throw new Exception("Not implemented yet");
    }

    public function mkdir(string $path, $mode = 0777) {
        // TODO: ?
        // https://stackoverflow.com/questions/38965266/how-to-create-a-folder-within-s3-bucket-using-php
    }

    protected function clean(string $path) {
        if (strpos($path, '/') === 0)
            return substr($path, 1);
        return $path;
    }

    protected function error($error) {
        \Mii::error('S3 Error. ' . $error['code'] . ': ' . $error['message']);
        return false;
    }
}