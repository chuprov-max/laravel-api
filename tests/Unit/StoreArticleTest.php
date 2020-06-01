<?php

namespace Tests\Unit;

use App\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreArticleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating article
     */
    public function testCreateArticle()
    {
        $articleCreated = factory(Article::class)->create();

        $articleFounded = Article::first();

        $this->assertNotNull($articleFounded);

        $this->assertTrue($articleFounded->id === $articleCreated->id);
    }
}
