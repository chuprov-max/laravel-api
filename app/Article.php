<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Article extends Model
{
    const STATUS_CREATED = 1;
    const STATUS_QUEUED = 2;
    const STATUS_SYNTHESIZE_STARTED = 3;
    const STATUS_SUCCESS_SYNTHESIZED = 4;
    const STATUS_FAILED_SYNTHESIZED = 5;

    /**
     * Get sound file public url (direct link to listen)
     *
     * @return string|null
     */
    public function getSpeechFilePublicUrl()
    {
        $yandexStorage = Storage::disk('yandex');
        $yandexStorageFilePath = $this->getYandexStorageFilePath();

        return $yandexStorage->exists($yandexStorageFilePath) ? $yandexStorage->url($yandexStorageFilePath) : null;
    }

    /**
     * Get path to file on Yandex Cloud Storage (inside selected bucket)
     *
     * @return string
     */
    public function getYandexStorageFilePath()
    {
        return "synthesize/{$this->getSpeechFilename()}";
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
