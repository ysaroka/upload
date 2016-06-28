<?php
/**
 * Created: 2016-06-25
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace App\Components;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SplFileObject;

class Uploader
{
    const PROGRESS_BYTE_INTERVAL = 524288;

    private $allowedSchemes = ['ftp', 'sftp'];

    public function __construct()
    {
        //
    }

    /**
     * @param string $file
     * @param string $url
     * @param string $amqp
     */
    public function detachUploadProcess($file, $url, $amqp)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $command = 'php -f ' . base_path() . '/uploader';
            exec('start /b ' . $command . " $file $url $amqp");
        } else {
            // POSIX
            $command = 'php -f ' . base_path() . '/uploader';
            exec($command . " $file $url $amqp >> /dev/null &");
        }
    }

    /**
     * @param string $file
     * @param string $url
     * @param string $amqp
     * @throws Exception
     */
    public function uploadProcess($file, $url, $amqp)
    {
        $urlParameters = $this->getUrlParameters($url);
        $amqpParameters = $this->getAmqpParameters($amqp);
        $uniqueUploadId = sha1(uniqid($url, true));

        $amqpWrapper = $this->getAmqpWrapper($amqpParameters['host'], $amqpParameters['port'], $amqpParameters['user'], $amqpParameters['pass']);

        $splFile = new SplFileObject($file);

        $serverFileName = sha1(uniqid(rand(), true)) . '.' . $splFile->getExtension();
        $serverUrl = "{$url}/{$serverFileName}";

        $fp = fopen($splFile->getRealPath(), 'r');

        $curl = curl_init();
        if (!$curl) {
            throw new \Exception(__METHOD__ . ' | ' . 'Error: could not initialize cURL.');
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $serverUrl,
            CURLOPT_UPLOAD => true,
            CURLOPT_PROTOCOLS => constant('CURLPROTO_' . strtoupper($urlParameters['scheme'])),
            CURLOPT_INFILE => $fp,
            CURLOPT_INFILESIZE => $splFile->getSize(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function ($resource, $download_size = 0, $downloaded = 0, $upload_size = 0, $uploaded = 0) use ($amqpWrapper, $urlParameters, $amqpParameters, $serverFileName, $splFile, $uniqueUploadId) {
                static $up = 0;

                if ($uploaded > ($up + self::PROGRESS_BYTE_INTERVAL)) {
                    $up = $uploaded + self::PROGRESS_BYTE_INTERVAL;

                    $amqpWrapper->sendMessage($amqpParameters['queue'], json_encode([
                        'upload_id' => $uniqueUploadId,
                        'status' => 'progress',
                        'server' => $urlParameters,
                        'upload_name' => $serverFileName,
                        'original_filename' => $splFile->getBasename(),
                        'percent' => round(($uploaded / $upload_size) * 100),
                    ]));
                }
            },
        ]);

        if (curl_exec($curl)) {
            // Success upload
            $amqpWrapper->sendMessage($amqpParameters['queue'], json_encode([
                'upload_id' => $uniqueUploadId,
                'status' => 'success',
                'status_message' => 'Successfully loaded.',
                'server' => $urlParameters,
                'upload_name' => $serverFileName,
                'original_filename' => $splFile->getBasename(),
            ]));
        } else {
            // Error upload
            $amqpWrapper->sendMessage($amqpParameters['queue'], json_encode([
                'upload_id' => $uniqueUploadId,
                'status' => 'error',
                'status_message' => curl_error($curl),
                'server' => $urlParameters,
                'upload_name' => $serverFileName,
                'original_filename' => $splFile->getBasename(),
            ]));
            throw new \Exception(__METHOD__ . ' | ' . curl_error($curl));
        }
    }

    private function getUrlParameters($url)
    {
        $urlParameters = parse_url($url);

        if (!isset($urlParameters['scheme']) || !in_array($urlParameters['scheme'], $this->allowedSchemes)) {
            throw new \Exception('Invalid scheme: ' . $url);
        }

        if (!isset($urlParameters['host'])) {
            throw new \Exception('Invalid host: ' . $url);
        }

        if (!isset($urlParameters['path'])) {
            $urlParameters['path'] = '/';
        }

        return $urlParameters;
    }

    private function getAmqpParameters($amqp)
    {
        $amqpParameters = parse_url($amqp);

        if (!isset($amqpParameters['scheme']) || $amqpParameters['scheme'] !== 'amqp') {
            throw new \Exception('Invalid amqp scheme: ' . $amqp);
        }

        if (!isset($amqpParameters['path'])) {
            throw new \Exception('Invalid queue in amqp scheme: ' . $amqp);
        } else {
            $amqpParameters['queue'] = trim($amqpParameters['path'], '\/');
        }

        if (!isset($amqpParameters['host'])) {
            $amqpParameters['host'] = 'localhost';
        }

        if (!isset($amqpParameters['port'])) {
            $amqpParameters['port'] = 5672;
        }

        if (!isset($amqpParameters['user'])) {
            $amqpParameters['user'] = 'guest';
            $amqpParameters['pass'] = 'guest';
        } elseif (!isset($amqpParameters['pass'])) {
            $amqpParameters['pass'] = '';
        }

        return $amqpParameters;
    }

    private function getAmqpWrapper($host, $port, $user, $password)
    {
        try {
            $amqpStreamConnection = new AMQPStreamConnection($host, $port, $user, $password);
            $amqpMessage = new AMQPMessage();
            $amqpWrapper = new AmqpWrapper($amqpStreamConnection, $amqpMessage);
        } catch (\Exception $e) {
            throw new \Exception(__METHOD__ . ' | ' . 'Error connecting to AMQP server: ' . $e->getMessage());
        }

        return $amqpWrapper;
    }
}