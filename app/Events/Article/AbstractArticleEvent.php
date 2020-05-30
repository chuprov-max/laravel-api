<?php


namespace App\Events\Article;


use App\Article;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AbstractArticleEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Article
     */
    public $article;

    /**
     * AbstractArticleEvent constructor.
     * @param Article $article
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
