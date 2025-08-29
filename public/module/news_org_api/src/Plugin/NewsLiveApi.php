<?php

namespace Simp\Public\Module\news_org_api\src\Plugin;

use Exception;
use jcobhams\NewsApi\NewsApi;
use jcobhams\NewsApi\NewsApiException;
use Simp\Public\Module\news_org_api\src\SourceHandler\BBCNews;

class NewsLiveApi
{
    protected array $articles;
    protected array $headlines;
    private NewsApi $newsLive;

    /**
     * Constructor for the NewsLiveApi class
     * @throws Exception
     */
    public function __construct() {

        if (!defined('LIVE_NEWS_ORG_API')) {
            throw new Exception('LIVE_NEWS_ORG_API is not defined');
        }
        $this->newsLive = new NewsApi(LIVE_NEWS_ORG_API);
    }

    /**
     * @throws NewsApiException
     */
    protected function getHeadlines() {
        $this->headlines = $this->newsLive->getTopHeadlines();
        return $this->headlines;
    }

    protected function getArticles(string $source = 'bbc.com')
    {
        $articles = $this->newsLive->getEverything(domains: $source,
        from: (new \DateTime('now'))->format('Y-m-d'),
        to: (new \DateTime('now'))->modify('-1 day')->format('Y-m-d'),
        sort_by: 'popularity');
        return $articles;
    }

    public static function cronHandler(): string
    {
        $new = new NewsLiveApi();

        if (!defined('NEWS_LIVE_SOURCES')) {
            throw new Exception('NEWS_LIVE_SOURCES is not defined');
        }

        $articles = $new->getArticles(NEWS_LIVE_SOURCES);

        $articles = json_decode(json_encode($articles), true)['articles'] ?? [];

        $handlers = [
            'BBCNews' => BBCNews::class
        ];

        $articles_objects = [];
        foreach ($articles as $article) {
            $source = str_replace(' ', '',$article['source']['name'] ?? '');
            $handler = $handlers[$source] ?? null;

            if ($handler) {

                $data = [
                    'title' => $article['title'],
                    'content' => $article['content'] ?? $article['description'],
                    'url' => $article['url'],
                    'image' => $article['urlToImage'],
                    'published_at' => $article['publishedAt'],
                ];

                $articles_objects[] = (new $handler(...$data))->save();
            }
        }

        return implode('\n', $articles_objects);

    }
}