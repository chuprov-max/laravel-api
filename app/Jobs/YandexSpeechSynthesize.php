<?php

namespace App\Jobs;

use App\Article;
use App\Events\Article\FailSynthesized;
use App\Events\Article\SuccessSynthesized;
use App\Events\Article\SynthesizeStarted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Panda\Yandex\SpeechKitSDK\Cloud;
use Panda\Yandex\SpeechKitSDK\Emotion;
use Panda\Yandex\SpeechKitSDK\Exception\ClientException;
use Panda\Yandex\SpeechKitSDK\Format;
use Panda\Yandex\SpeechKitSDK\Lang;
use Panda\Yandex\SpeechKitSDK\Rate;
use Panda\Yandex\SpeechKitSDK\Ru;
use Panda\Yandex\SpeechKitSDK\Speech;
use Panda\Yandex\SpeechKitSDK\Speed;

/**
 * Class YandexSpeechSynthesize is Job Queue to synthesize text
 * @package App\Jobs
 */
class YandexSpeechSynthesize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Article
     */
    private $article;

    /**
     * YandexSpeechSynthesize constructor.
     *
     * @param Article $article
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logs()->info("Selected article \"{$this->article->title}\" to synthesize text");
        event(new SynthesizeStarted($this->article));

        try {
            $cloud = $this->createCloud();
            logs()->info("Yandex Cloud init");

            $speech = $this->createSynthesizeTextTask();
            logs()->info("Synthesize Text Task prepared");

            $this->synthesizeTextToSoundFile($cloud, $speech);

        } catch (ClientException | \Exception $e) {
            event(new FailSynthesized($this->article));
            logs()->error($e->getMessage());
            return ;
        }
    }

    /**
     * Create Yandex Cloud service
     *
     * @return Cloud
     * @throws \Exception
     */
    private function createCloud()
    {
        if (!env('YANDEX_OAUTH_TOKEN') || !env('YANDEX_CLOUD_FOLDER_ID')) {
            throw new \Exception('You need to set next .env variables: YANDEX_OAUTH_TOKEN, YANDEX_CLOUD_FOLDER_ID');
        }

        return new Cloud(env('YANDEX_OAUTH_TOKEN'), env('YANDEX_CLOUD_FOLDER_ID'));
    }

    /**
     * Create task to Synthesize text to speech (Text-To-Speech, TTS)
     * @todo add speech settings to .env
     *
     * @return Speech
     */
    private function createSynthesizeTextTask()
    {
        $speech = new Speech($this->article->body);
        $speech->setLang(Lang::RU)
            ->setVoice(Ru::OKSANA)
            ->setEmotion(Emotion::GOOD)
            ->setSpeed(Speed::NORMAL)
            ->setFormat(Format::OGGOPUS)
            ->setRate(Rate::HIGH);
        return $speech;
    }

    /**
     * Synthesize article's text to sound file
     *
     * @param Cloud $cloud
     * @param Speech $speech
     */
    private function synthesizeTextToSoundFile(Cloud $cloud, Speech $speech)
    {
        $filePath = $this->article->getSpeechFileStoragePath();
        if ($filePath && file_put_contents($filePath, $cloud->request($speech))) {
            event(new SuccessSynthesized($this->article));
            logs()->info("File \"{$filePath}\" synthesized successfully");
        } else {
            event(new FailSynthesized($this->article));
            logs()->error("Error: File \"{$filePath}\" doesn't synthesized");
        }
    }
}
