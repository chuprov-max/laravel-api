<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    const STATUS_CREATED = 1;
    const STATUS_QUEUED = 2;
    const STATUS_SYNTHESIZE_STARTED = 3;
    const STATUS_SUCCESS_SYNTHESIZED = 4;
    const STATUS_FAILED_SYNTHESIZED = 5;

    const STORAGE_DIRECTORY = 'app/speech/';
    const PUBLIC_DIRECTORY = 'app/public/speech/';

    /**
     * Get sound file's path on filesystem (after Yandex Speech Kit API processing)
     *
     * @param bool $checkExists
     * @return string|null
     */
    public function getSpeechFileStoragePath(bool $checkExists = false)
    {
        $filePath = storage_path(self::STORAGE_DIRECTORY."{$this->getSpeechFilename()}");
        if (!$checkExists) {
            return $filePath;
        }

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
    }

    /**
     * Get sound file public url (direct link to listen)
     *
     * @return string|null
     */
    public function getSpeechFilePublicUrl()
    {
        $filePath = storage_path(self::PUBLIC_DIRECTORY . "{$this->getSpeechFilename()}");

        if (file_exists($filePath)) {
            return asset("storage/speech/{$this->getSpeechFilename()}");
        }

        return null;
    }

    /**
     * Move file from storage directory (after processed via Speech Kit API) to public directory
     *
     * @return bool
     */
    public function moveFileFromStorageToPublic()
    {
        $storageFilePath = $this->getSpeechFileStoragePath(true);
        if (!$storageFilePath) {
            return false;
        }

        return \Illuminate\Support\Facades\File::move($storageFilePath, storage_path(self::PUBLIC_DIRECTORY . "{$this->getSpeechFilename()}"));
    }

    /**
     * Get sound's filename
     *
     * @return string
     */
    public function getSpeechFilename()
    {
        return "{$this->id}.ogg";
    }

    /**
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $status)
    {
        $this->status = $status;

        return $this->save();
    }
}
