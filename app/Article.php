<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    const STATUS_CREATED = 1;
    const STATUS_QUEUED = 2;
    const STATUS_SEND_REQUEST_TO_YANDEX = 3;
    const STATUS_SUCCESS_SYNTHESIZE_SPEECH = 4;
    const STATUS_FAILED_SYNTHESIZE_SPEECH = 5;

    /**
     * @param bool $checkExists
     * @return string|null
     */
    public function getSpeechFileStoragePath(bool $checkExists = false)
    {
        $filePath = storage_path("app/speech/{$this->id}.ogg");
        if (!$checkExists) {
            return $filePath;
        }

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
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
