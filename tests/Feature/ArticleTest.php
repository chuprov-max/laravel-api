<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Test POST /article endpoint.
     */
    public function testArticlePostEndpoint()
    {
        $response = $this->postJson('/api/article', [
            'title' => 'Минеральные дезодоранты (на алюмокалиевых квасцах)',
            'body' => 'Плюсы: отлично блокируют запах пота, обладают обеззараживающим, антибактериальным и заживляющим действием, поэтому их можно использовать ежедневно, нанося в том числе на кожу после депиляции.
Минусы: требуют привыкания в течение пары недель после перехода от привычного дезодоранта, особенно если у вас обильное потоотделение.
Особенности использования: чаще всего производятся в формате кристалла, который перед применением необходимо смочить водой. Кристалла хватает как минимум на год использования! Встречаются и более привычные форматы - спреи и ролики с содержанием квасцов.'
        ]);

        $responseData = $response->getData();

        $response->assertStatus(201);
        $this->assertTrue(isset($responseData->data) && isset($responseData->data->id));
        $this->assertIsInt($responseData->data->id);

        // Check GET article/{id} endpoint for just created article
        $responseArticle = $this->getJson('/api/article/' . $responseData->data->id);
        $responseArticle->assertStatus(200);
    }

    /**
     * Test GET /article endpoint.
     *
     * @return void
     */
    public function testArticleIndexEndpoint()
    {
        $response = $this->getJson('/api/article');
        $responseData = $response->getData();

        $response->assertStatus(200);
        $this->assertTrue(isset($responseData->data) && is_array($responseData->data));
    }
}
