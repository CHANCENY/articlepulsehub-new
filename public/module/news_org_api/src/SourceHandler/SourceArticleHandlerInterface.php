<?php

namespace Simp\Public\Module\news_org_api\src\SourceHandler;

interface SourceArticleHandlerInterface
{
    /**
     * @param string $title
     * @param string $content
     * @param string $url
     * @param string $image
     * @param string $published_at
     */
    public function __construct(
        string $title,
        string $content,
        string $url,
        string $image,
        string $published_at
    );

    /**
     * Saves the current state or context and returns a status message.
     *
     * @return string A string indicating the result of the save operation.
     */
    public function save(): string;
}