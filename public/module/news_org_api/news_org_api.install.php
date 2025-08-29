<?php


function news_org_api_cron_subscribers_install(): array
{
    return [
        'news_org_api.create_news' => \Simp\Public\Module\news_org_api\src\CronNewsOrg::class,
    ];
}

function news_org_api_cron_jobs_install(): array
{
    return [
        'news_org_api_create_news' => [
            'title' => 'News Live Org',
            'description' => 'Create the articles from the live org',
            'timing' => 'every|day',
            'subscribers' => 'news_org_api.create_news'
        ]
    ];
}