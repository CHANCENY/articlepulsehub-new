<?php

namespace Simp\Public\Module\services\src\Twig;

use Simp\Core\extends\form_builder\src\Plugin\Submission;
use Simp\Core\modules\services\Service;
use Simp\Core\modules\structures\content_types\entity\Node;
use Simp\Core\modules\structures\content_types\entity\NodeStorageEntity;
use Simp\Core\modules\structures\taxonomy\Term;

class TwigHelperFunctionAction
{
    public static function getFeaturedArticles(): NodeStorageEntity
    {
        $node_storage = Node::nodeStorage('content_articles');
        $node_storage->addWhere('node_data.status = :status',['status' => 1]);
        $node_storage->addJoin('node__content_articles_field_featured','content_f','node_data.nid = content_f.nid');
        $node_storage->addWhere('content_f.content_articles_field_featured__value = :featured',['featured' => "Yes"]);
        $node_storage->orderBy('node_data.updated','DESC');
        $node_storage->limit(7);
        return $node_storage->execute();
    }

    public static function getRecentArticles(): NodeStorageEntity
    {
        $node_storage = Node::nodeStorage('content_articles');
        $node_storage->addWhere('node_data.status = :status',['status' => 1]);
        $node_storage->orderBy('node_data.updated','DESC');
        $node_storage->limit(3);
        return $node_storage->execute();
    }

    public static function getCategories(): array
    {
        return Term::factory()->getTermByVid('categories');
    }

    public static function getTags(): array
    {
        $tags = Term::factory()->getTermByVid('tags');

        // shake the array and get first 9
        shuffle($tags);
        return array_slice($tags, 0, 9);
    }

    public static function getHomeArticles(): NodeStorageEntity
    {
        $node_storage = Node::nodeStorage('content_articles');
        try {
            $node_storage->addWhere('node_data.status = :status',['status' => 1]);
            $node_storage->orderBy('node_data.updated','DESC');
            $node_storage->limit(3);
            $node_storage->execute();
            return $node_storage->orderByFieldCountPhp('content_articles_field_comments');
        }catch (\Throwable $e) {
            return $node_storage;
        }
    }

    public static function getComments(array $comments): array
    {
        $comments = array_filter($comments);
        return Submission::loadMultiple($comments);
    }

    public static function getCallToAction(): NodeStorageEntity
    {
        $node_storage = Node::nodeStorage('content_banner_ads');
        $node_storage->addWhere('node_data.status = :status',['status' => 1]);
        $node_storage->limit(1);
        $node_storage->pickRandom();
        return $node_storage->execute();
    }
}