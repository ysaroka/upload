<?php

namespace App\Http\Controllers;

use App\Components\AmqpWrapper;
use App\Interfaces\InterfaceFileStorage;
use App\Server;
use App\UploadEntity;
use Illuminate\Contracts\Foundation\Application;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

class UploadController extends Controller
{
    /**
     * Limit upload entities on upload page
     */
    const UPLOAD_ENTITIES_LIMIT = 100;

    /**
     * Laravel application object
     * @var \Illuminate\Foundation\Application
     */
    private $app;

    /**
     * Laravel request object
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * Laravel view object
     * @var \Illuminate\View\Factory
     */
    private $view;

    /**
     * Laravel config object
     * @var \Illuminate\Config\Repository
     */
    private $config;

    public function __construct(Application $app, ResponseFactory $response)
    {
        $this->app = $app;
        $this->response = $response;
        $this->request = $this->app->request;
        $this->view = $this->app->view;
        $this->config = $this->app->config;
    }

    public function anyUpload(InterfaceFileStorage $userFileStorage, AmqpWrapper $amqpWrapper, Server $server, UploadEntity $uploadEntity)
    {
        $responseVariables = [
            'uploadStatus' => false,
            'storageErrors' => [],
            'uploadEntities' => [],
        ];

        if ($this->request->isMethod('post') &&
            $this->request->hasFile('file') &&
            $this->request->file('file')->isValid()
        ) {
            $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tmp-user-files-to-storage' . DIRECTORY_SEPARATOR;
            $tmpFilePath = $tmpDir . $this->request->file('file')->getClientOriginalName();
            $this->request->file('file')->move(
                $tmpDir,
                $this->request->file('file')->getClientOriginalName()
            );

            $newStorageFile = $userFileStorage->addFile($tmpFilePath);

            if ($newStorageFile && !$userFileStorage->hasErrors()) {
                /* @var \SplFileInfo $newStorageFile */

                // AMQP send $newfile, to servers
                foreach ($server->all() as $server) {
                    if (count($server->configs) > 0) {
                        foreach ($server->configs as $config) {
                            // Send server and file info to upload queue task
                            $amqpWrapper->sendMessage($this->config->get('amqp.queues.uploader.upload'), json_encode([
                                'file' => $newStorageFile->getRealPath(),
                                'url' => $server->scheme . '://' . $config->auth . '@' . $server->host . '/' . trim($config->path, '\/'),
                            ]));
                        }
                    } else {
                        // The server has no configuration
                        $amqpWrapper->sendMessage($this->config->get('amqp.queues.uploader.upload'), json_encode([
                            'file' => $newStorageFile->getRealPath(),
                            'url' => $server->scheme . '://' . $server->host,
                        ]));
                    }
                }
                $responseVariables['uploadStatus'] = true;
            } else {
                $responseVariables['storageErrors'] = $userFileStorage->getErrors();
            }

            if ($this->request->ajax()) {
                return $this->response->json($responseVariables);
            }
        }

        $responseVariables['uploadEntities'] = $uploadEntity->limit(self::UPLOAD_ENTITIES_LIMIT)->orderBy('created_at', 'DESC')->get();

        return $this->view->make('upload.index', $responseVariables);
    }
}
