<?php declare(strict_types=1);

namespace mii\storage;

use mii\core\Component;
use mii\core\Exception;
use mii\web\UploadedFile;

class S3 extends Component implements StorageInterface
{
    protected string $region = 'eu-central-1';
    protected string $bucket = '';

    protected string $key = '';
    protected string $secret = '';

    // custom endpoint
    protected ?string $endpoint = null;

    protected \levmorozov\s3\S3 $s3;


    public function init(array $config = []): void
    {
        parent::init($config);
        $this->s3 = new \levmorozov\s3\S3($this->key, $this->secret, $this->endpoint, $this->region);
    }

    /**
     * @throws \ErrorException
     */
    public function exist(string $path): bool
    {

        $response = $this->s3->getObjectInfo([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if ($response['error']) {

            if ($response['error']['code'] !== 404) {
                $this->error($response['error']);
                throw new \ErrorException();
            }
            return false;
        }

        return true;
    }

    // Warning: This method loads the entire downloaded contents into memory!
    public function get(string $path)
    {
        $response = $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if ($response['error']) {
            if ($response['error']['code'] === 'NoSuchKey') {
                $this->error($response['error']);
            }
            return false;
        }

        return (string)$response['body'];
    }

    public function streamTo(string $path, $to)
    {
        $response = $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path),
            'SaveAs' => $to
        ]);

        if ($response['error']) {
            if ($response['error']['code'] === 'NoSuchKey') {
                $this->error($response['error']);
            }
            return false;
        }
        return $response;
    }

    /**
     * @param string $path
     * @param string|\Stringable|resource $content
     * @return bool
     */
    public function put(string $path, $content): bool
    {
        if ($content instanceof UploadedFile) {
            return $this->putFile($path, $content->tmp_name);
        }

        try {
            $response = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->clean($path),
                'Body' => $content
            ]);
        } catch (\Throwable $t) {
            \Mii::error($t);
            return false;
        }

        if ($response['error']) {
            return $this->error($response['error']);
        }
        return true;
    }


    public function putFile(string $path, string $from): bool
    {
        try {
            $response = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->clean($path),
                'SourceFile' => $from
            ]);
        } catch (\Throwable $t) {
            \Mii::error($t);
            return false;
        }
        if ($response['error']) {
            return $this->error($response['error']);
        }
        return true;
    }

    public function delete(string $path): bool
    {
        $response = $this->s3->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if ($response['error']) {
            return $this->error($response['error']);
        }
        return true;
    }

    public function size(string $path)
    {
        $response = $this->s3->getObjectInfo([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if ($response['error']) {
            return false;
        }

        $length = $response['headers']['content-length'] ?? null;

        return $length !== null ? (int)$length : false;
    }

    public function modified(string $path)
    {
        $response = $this->s3->getObjectInfo([
            'Bucket' => $this->bucket,
            'Key' => $this->clean($path)
        ]);

        if ($response['error']) {
            return false;
        }

        $date = $response['headers']['last-modified'] ?? null;

        return $date !== null ? strtotime($date) : false;
    }

    /**
     * @throws Exception
     */
    public function copy(string $from, string $to): bool
    {
        throw new Exception("Not implemented yet");
    }

    /**
     * @throws Exception
     */
    public function move(string $from, string $to): bool
    {
        throw new Exception("Not implemented yet");
    }

    public function url(string $path): string
    {
        return $this->s3->getObjectInfo($path)['@metadata']['effectiveUri'];
    }

    /**
     * @throws Exception
     */
    public function files(string $path)
    {
        throw new Exception("Not implemented yet");
    }

    public function mkdir(string $path, $mode = 0777)
    {
        // TODO: ?
        // https://stackoverflow.com/questions/38965266/how-to-create-a-folder-within-s3-bucket-using-php
    }

    protected function clean(string $path): string
    {
        return \ltrim($path, '/');
    }

    protected function error($error): bool
    {
        \Mii::error('S3 Error. ' . $error['code'] . ': ' . $error['message']);
        return false;
    }
}
