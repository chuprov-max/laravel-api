<?php

namespace App\Console\Commands;

use App\Article;
use Illuminate\Console\Command;
use Panda\Yandex\SpeechKitSDK\Cloud;
use Panda\Yandex\SpeechKitSDK\Emotion;
use Panda\Yandex\SpeechKitSDK\Exception\ClientException;
use Panda\Yandex\SpeechKitSDK\Format;
use Panda\Yandex\SpeechKitSDK\Lang;
use Panda\Yandex\SpeechKitSDK\Rate;
use Panda\Yandex\SpeechKitSDK\Ru;
use Panda\Yandex\SpeechKitSDK\Speech;
use Panda\Yandex\SpeechKitSDK\Speed;

class YandexSpeechSynthesize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yandex:synthesize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synthesize text to speech';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $articleModel = Article::where('status', Article::STATUS_QUEUED)->first();

        if (!$articleModel) {
            $this->error("Article to Synthesize not found");
            return ;
        }
        $this->info("Selected article \"{$articleModel->title}\" to synthesize text");

        try {
            if (!env('YANDEX_OAUTH_TOKEN') || !env('YANDEX_CLOUD_FOLDER_ID')) {
                throw new \Exception('You need to set next .env variables: YANDEX_OAUTH_TOKEN, YANDEX_CLOUD_FOLDER_ID');
            }
            // Create Yandex Cloud service
            $cloud = new Cloud(env('YANDEX_OAUTH_TOKEN'), env('YANDEX_CLOUD_FOLDER_ID'));
        } catch (ClientException | \Exception $e) {
            $this->error($e->getMessage());
            return ;
        }
        $this->info("Yandex Cloud init");

        try {
            // Create task to Synthesize text to speech (Text-To-Speech, TTS)
            $speech = new Speech($articleModel->body);
            $speech->setLang(Lang::RU)
                ->setVoice(Ru::OKSANA)
                ->setEmotion(Emotion::GOOD)
                ->setSpeed(Speed::NORMAL)
                ->setFormat(Format::OGGOPUS)
                ->setRate(Rate::HIGH);

        } catch (ClientException $e) {
            $this->error($e->getMessage());
            return ;
        }

        $this->info("Yandex Speech Task prepared");

        try {
            $filePath = $articleModel->getSpeechFileStoragePath();
            if ($filePath && file_put_contents($filePath, $cloud->request($speech))) {
                $articleModel->changeStatus(Article::STATUS_SUCCESS_SYNTHESIZE_SPEECH);
                $this->info("File \"{$filePath}\" created successfully");
            } else {
                $this->error("Error: File \"{$filePath}\" doesn't created");
            }
        } catch (ClientException $e) {
            $this->error($e->getMessage());
        }
    }
}
