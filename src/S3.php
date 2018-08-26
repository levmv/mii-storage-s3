<?php

namespace levmorozov\s3storage;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use mii\storage\FileSystemInterface;
use mii\storage\Storage;

class S3 extends Storage implements FileSystemInterface {

    protected $region = '';
    protected $bucket = '';

    protected $key = '';
    protected $secret = '';

    protected $s3;

    public function init($config){
        parent::init($config);

        $credentials = new Credentials($this->key, $this->secret);

        $this->s3 =  new S3Client([
            'version' => 'latest',
            'region'  => 'us-west-2',
            'credentials' => $credentials
        ]);
    }


    public function exist(string $path) {

    }

    public function get(string $path) {

    }

    public function put(string $path, $content) {

    }
    public function delete(string $path) {

    }
    public function size(string $path) {

    }
    public function modified(string $path) {

    }
    public function copy(string $from, string $to) {

    }
    public function move(string $from, string $to) {

    }
    public function url(string $path) {

    }
    public function files(string $path) {

    }
    public function mkdir(string $path, $mode = 0777) {

    }

}