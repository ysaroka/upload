<?php
/**
 * Created: 2016-06-26
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace App\Components;

use App\Interfaces\InterfaceFileStorage;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use SplFileInfo;
use SplFileObject;

class UserFileStorage implements InterfaceFileStorage
{
    /**
     * Path to user files storage
     * @var \SplFileInfo
     */
    private $storagePath;

    /**
     * Validator object
     * @var \Illuminate\Contracts\Validation\Factory
     */
    private $validator;

    /**
     * File validation rules
     * @var array
     */
    private $validationRules = [];

    /**
     * Storage manipulation errors
     * @var array
     */
    private $errors = [];

    /**
     * UserFileStorage constructor.
     * @param string $storagePath
     * @param \Illuminate\Contracts\Validation\Factory $validator
     * @throws \Exception
     */
    public function __construct($storagePath, ValidationFactory $validator)
    {
        $this->setStoragePath($storagePath);
        $this->setValidator($validator);
        $this->initValidator();
    }

    /**
     * Add file to storage
     * @param string $tmpFilePath
     * @param bool|true $removeTmpFile
     * @return bool|\SplFileInfo Path to new file or false
     */
    public function addFile($tmpFilePath, $removeTmpFile = true)
    {
        $splTmpFile = new SplFileInfo($tmpFilePath);
        $splNewFile = new SplFileInfo($this->getStoragePath() . DIRECTORY_SEPARATOR . $splTmpFile->getFilename());

        if ($this->validateFile($splTmpFile)) {
            // If file does not exist in storage (same name and sha1) - add it,
            // else no need add, only return it path ($splNewFile)
            if (!$splNewFile->isFile() ||
                strcmp(sha1_file($splNewFile->getRealPath()), sha1_file($splTmpFile->getRealPath())) !== 0
            ) {
                // TODO:2016-06-27:Yauhen Saroka: Rebuild it in the future
                /*
                                // In storage is a file with the same name but with different sha1() hash
                                if (file_exists($storageFilePath)) {
                                    $splNewFile = $this->getStoragePath() .
                                                   DIRECTORY_SEPARATOR .
                                                   $splTmpFile->getBasename('.' . $splTmpFile->getExtension()) .
                                                   ' (' . sha1(uniqid($storageFilePath, true)) . ').' .
                                                   $splTmpFile->getExtension();
                                }
                */

                if (!rename($splTmpFile->getRealPath(), $splNewFile)) {
                    $this->addError('file', 'Can not move a file ' . $splTmpFile->getRealPath() . ' to ' . $splNewFile->getRealPath());
                }
            }
        }

        if ($removeTmpFile && $splTmpFile->isFile()) {
            @unlink($splTmpFile->getRealPath());
        }

        if (!$this->hasErrors()) {
            return $splNewFile;
        } else {
            return false;
        }
    }

    /**
     * Get file from storage
     * @param string $fileName
     * @return bool|SplFileInfo
     * @throws \Exception
     */
    public function getFile($fileName)
    {
        throw new \Exception('The method ' . __METHOD__ . ' is not implemented.');
    }

    /**
     * Get last validation errors after file adding
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set errors after any storage manipulation
     * @param $errors array
     */
    public function setErrors($errors)
    {
        if (is_array($errors)) {
            $this->errors = $errors;
        }
    }

    /**
     * Add error after any storage manipulation
     * @param string $name
     * @param string $message
     */
    public function addError($name, $message)
    {
        $this->errors[] = [$name => $message];
    }

    /**
     * Check validation errors after any manipulation
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * Reset validation errors
     */
    public function resetErrors()
    {
        $this->errors = [];
    }

    /**
     * Set path to user files storage
     * @param $path
     * @throws \Exception
     */
    public function setStoragePath($path)
    {
        $this->storagePath = new SplFileInfo($path);

        if (!$this->storagePath->isDir()) {
            throw new \Exception(__METHOD__ . ' | ' . 'storage path is not directory or does not exist.');
        }
    }

    /**
     * Get path to user files storage
     * @return SplFileInfo
     */
    public function getStoragePath()
    {
        return $this->storagePath;
    }

    /**
     * Validate added file
     * @param \SplFileInfo $splFile
     * @return bool
     */
    public function validateFile($splFile)
    {
        if ($splFile->isFile()) {
            $validator = $this->getValidator()->make([
                'file' => $splFile,
            ], [
                'file' => 'extension_allowed|max_size_allowed|stopwords_allowed',
            ], [
                'extension_allowed' => 'File extension is not allowed.',
                'max_size_allowed' => 'The file is very large.',
                'stopwords_allowed' => 'The file contains forbidden words.',
            ]);

            if ($validator->fails()) {
                $this->setErrors($validator->errors()->getMessages());
            } else {
                return true;
            }
        } else {
            $this->addError('file', 'File ' . $splFile->getRealPath() . ' does not exists.');
        }

        return false;
    }

    /**
     * Initialize add file validator
     */
    public function initValidator()
    {
        $validator = $this->getValidator();

        $configRules = $this->getValidationRules();
        $parseRules = [];

        if (is_array($configRules)) {
            foreach ($configRules as $rule) {
                if (isset($rule['extension'])) {
                    $ext = $rule['extension'];
                    unset($rule['extension']);
                    $parseRules[$ext][] = $rule;
                }
            }
        }

        $validator->extend('extension_allowed', function ($attribute, $splFileObject, $parameters, $validator) use ($parseRules) {
            /* @var \SplFileObject $splFileObject */
            return isset($parseRules[$splFileObject->getExtension()]);
        });

        $validator->extend('max_size_allowed', function ($attribute, $splFileObject, $parameters, $validator) use ($parseRules) {
            /* @var \SplFileObject $splFileObject */
            if (isset($parseRules[$splFileObject->getExtension()])) {
                foreach ($parseRules[$splFileObject->getExtension()] as $criteria) {
                    if (isset($criteria['max_size']) &&
                        $splFileObject->getSize() > $criteria['max_size']
                    ) {
                        return false;
                    }
                }
            }

            return true;
        });

        $validator->extend('stopwords_allowed', function ($attribute, $splFileObject, $parameters, $validator) use ($parseRules) {
            /* @var \SplFileObject $splFileObject */
            if (isset($parseRules[$splFileObject->getExtension()])) {
                foreach ($parseRules[$splFileObject->getExtension()] as $criteria) {
                    if (isset($criteria['stopwords'])
                    ) {
                        $phrases = explode(',', $criteria['stopwords']);

                        foreach ($phrases as $phrase) {
                            $regexPatternPhrase = preg_replace('#\s+#', '[\n\s]+', trim($phrase));
                            if (preg_match('#' . $regexPatternPhrase . '#', file_get_contents($splFileObject->getRealPath()))) {
                                return false;
                            }

                            //while (!$splFileObject->eof()) {
                            //    if (strripos($splFileObject->fgets(), $trimPhrase) !== false) {
                            //        return false;
                            //    }
                            //}
                        }
                    }
                }
            }

            return true;
        });
    }

    public function setValidationRules($rules)
    {
        $this->validationRules = $rules;
        $this->initValidator();
    }

    public function getValidationRules()
    {
        return $this->validationRules;
    }

    /**
     * Get validator object
     * @return \Illuminate\Contracts\Validation\Factory
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Set vaidator object
     * @param \Illuminate\Contracts\Validation\Factory $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }
}