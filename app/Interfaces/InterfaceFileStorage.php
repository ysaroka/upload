<?php
/**
 * Created: 2016-06-28
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace App\Interfaces;


interface InterfaceFileStorage
{
    /**
     * Set path to user files storage
     * @param string $path
     */
    public function setStoragePath($path);

    /**
     * Get path to user files storage
     * @return \SplFileInfo
     */
    public function getStoragePath();

    /**
     * Add file to storage
     * @param string $tmpFilePath
     * @param bool|true $removeTmpFile
     * @return bool|\SplFileInfo Path to new file or false
     */
    public function addFile($tmpFilePath, $removeTmpFile = true);

    /**
     * Get file from storage
     * @param string $fileName
     * @return bool|\SplFileInfo
     */
    public function getFile($fileName);

    /**
     * Check validation errors after any manipulation
     * @return bool
     */
    public function hasErrors();

    /**
     * Get last validation errors after file adding
     * @return array
     */
    public function getErrors();

    /**
     * Reset validation errors
     */
    public function resetErrors();

}