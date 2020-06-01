<?php

namespace App\Http\Controllers;

use App\Article;
use App\Events\Article\Created;
use App\Events\Article\Queued;
use App\Http\Requests\ArticleRequest;
use App\Http\Requests\StoreArticle;
use App\Http\Resources\Article as ArticleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $articles = Article::paginate(10);

        return ArticleResource::collection($articles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreArticle $request
     * @return ArticleResource
     */
    public function store(StoreArticle $request)
    {
        $article = new Article();

        $article->title = $request->input('title');
        $article->body = $request->input('body');

        if ($article->save()) {
            event(new Created($article));
            return new ArticleResource($article);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return ArticleResource
     */
    public function show($id)
    {
        $article = Article::find($id);

        return new ArticleResource($article);
    }

    /**
     * Update the specified article in storage.
     *
     * @param StoreArticle $request
     * @param $id
     * @return ArticleResource
     */
    public function update(StoreArticle $request, $id)
    {
        $article = Article::find($id);

        abort_unless($article, 404, "Article not found");

        $article->title = $request->input('title');
        $article->body = $request->input('body');

        if ($article->save()) {
            event(new Queued($article));
            return new ArticleResource($article);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $article = Article::find($id);

        $soundFileName = $article->getYandexStorageFilePath();

        if ($article->delete()) {
            $yandexStorage = Storage::disk('yandex');
            if ($yandexStorage->exists($soundFileName)) {
                $yandexStorage->delete($soundFileName);
            }
            return response()->json(null);
        }
    }
}
