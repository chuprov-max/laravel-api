<?php

namespace App\Listeners;

use App\Article;
use App\Events\Article\Created;
use App\Events\Article\FailSynthesized;
use App\Events\Article\Queued;
use App\Events\Article\SuccessSynthesized;
use App\Events\Article\SynthesizeStarted;
use App\Jobs\YandexSpeechSynthesize;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class ArticleEventSubscriber is listener to handle Article's events
 * @package App\Listeners
 */
class ArticleEventSubscriber
{
    public function handleArticleCreated(Created $event)
    {
        $event->article->changeStatus(Article::STATUS_CREATED);
        logs()->info("ArticleEventSubscriber: Article #{$event->article->id} created");
        event(new Queued($event->article));
    }

    public function handleArticleQueued(Queued $event)
    {
        $event->article->changeStatus(Article::STATUS_QUEUED);

        // add job to queue to synthesize text
        YandexSpeechSynthesize::dispatch($event->article)->onQueue('synthesize');
        logs()->info("ArticleEventSubscriber: Article #{$event->article->id} queued");
    }

    public function handleSynthesizeStarted(SynthesizeStarted $event)
    {
        $event->article->changeStatus(Article::STATUS_SYNTHESIZE_STARTED);
        logs()->info("ArticleEventSubscriber: Article #{$event->article->id} started synthesize");
    }

    public function handleSuccessSynthesized(SuccessSynthesized $event)
    {
        $article = $event->article;
        $article->changeStatus(Article::STATUS_SUCCESS_SYNTHESIZED);
        logs()->info("ArticleEventSubscriber: Article #{$event->article->id} successfully synthesized");
    }

    public function handleFailSynthesized(FailSynthesized $event)
    {
        $event->article->changeStatus(Article::STATUS_FAILED_SYNTHESIZED);
        logs()->info("ArticleEventSubscriber: Article #{$event->article->id} didn't synthesize");
    }

    public function subscribe($events)
    {
        $events->listen(
            Created::class,
            'App\Listeners\ArticleEventSubscriber@handleArticleCreated'
        );
        $events->listen(
            Queued::class,
            'App\Listeners\ArticleEventSubscriber@handleArticleQueued'
        );
        $events->listen(
            SynthesizeStarted::class,
            'App\Listeners\ArticleEventSubscriber@handleSynthesizeStarted'
        );
        $events->listen(
            SuccessSynthesized::class,
            'App\Listeners\ArticleEventSubscriber@handleSuccessSynthesized'
        );
        $events->listen(
            FailSynthesized::class,
            'App\Listeners\ArticleEventSubscriber@handleFailSynthesized'
        );
    }
}
