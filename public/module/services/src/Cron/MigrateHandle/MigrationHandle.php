<?php

namespace Simp\Public\Module\services\src\Cron\MigrateHandle;

use Google\Service\Blogger\Blog;
use Simp\Core\modules\files\helpers\FileFunction;
use Simp\Core\modules\structures\content_types\entity\Node;
use Simp\Core\modules\structures\taxonomy\Term;
use Symfony\Component\Yaml\Yaml;

class MigrationHandle
{

    private static function runSettings(string $key, int $on = 0)
    {
        $file = __DIR__. "/run/run.yml";
        $data = Yaml::parseFile($file);
        $value = $data[$key] ?? 0;
        if ($on > 0) {
            $data[$key] = $on;
            file_put_contents($file, Yaml::dump($data));
        }
        return $value;
    }
    public static function migrateCategories(Resources $resources): string
    {
        $current_index = self::runSettings('category_on');
        $categories = $resources->categories;

        // Get a slice of categories
        $slice = array_slice($categories, $current_index, 10);
        if (empty($slice)) {
            return "No categories to migrate";
        }

        $results = 0;
        foreach ($slice as $item) {
            if (Term::factory()->create('categories', trim(html_entity_decode($item['name'])))) {
                $results += 1;
            }
        }

        self::runSettings('category_on', $current_index + 10);
        return "Migrated {$results} categories";
    }

    public static function migrateTags(Resources $resources): string
    {
        $current_index = self::runSettings('tag_on');
        $tags = $resources->tags;

        // Get a slice of tags
        $slice = array_slice($tags, $current_index, 10);
        if (empty($slice)) {
            return "No tags to migrate";
        }

        $results = 0;
        foreach ($slice as $item) {
            if (Term::factory()->create('tags', trim(html_entity_decode($item)))) {
                $results += 1;
            }
        }

        self::runSettings('tag_on', $current_index + 10);

        return "Migrated {$results} tags";
    }

    /**
     * @throws \Exception
     */
    public static function migrateBlogs(Resources $resources): string
    {
        $current_index = self::runSettings('blog_on');
        $blogs = $resources->blogs;

        $slice = array_slice($blogs, $current_index, 10);
        if (empty($slice)) {
            return "No blogs to migrate";
        }

        $results = 0;
        foreach ($slice as $item) {

            $images = $item['blog_images'] ?? "";
            $images = explode(",", $images);

            // find true data of images
            $images_data = array_filter($resources->fileManaged, function ($image) use ($images) {
                return in_array($image['fid'], $images);
            });

            $cover_image = true;
            // find true images path
            $COVER_IMAGE = 0;

            $images_fid = array_map(function ($image) use (&$cover_image, &$COVER_IMAGE){
                $filename = pathinfo($image['path'] ?? "", PATHINFO_FILENAME) . "." . pathinfo($image['path'] ?? "", PATHINFO_EXTENSION);
                $imageUploader = ImageUploader::factory($filename, $cover_image);
                if ($imageUploader->is_cover === true) {
                    $cover_image = false;
                }
                if ($imageUploader->is_cover) {
                    $COVER_IMAGE = $imageUploader->file_fid;
                    return 0;
                }
                return $imageUploader->file_fid;

            }, $images_data);

            $images_fid = array_values(array_filter($images_fid));
            $item['blog_images'] = $images_fid;

            // upload default cover image
            if (empty($COVER_IMAGE)) {
                $imageUploader = ImageUploader::factory("default_blog_thumb_png.png", true);
                if ($imageUploader->is_cover) {
                    $COVER_IMAGE = $imageUploader->file_fid;
                }
            }

            // add category
            $category = array_filter($resources->categories, function ($category) use ($item) {
                return $category['cid'] == $item['blog_category'];
            });

            if (!empty($category)) {

                $name = reset($category)['name'];
                $term = Term::search($name);

                if (!empty($term)) {
                    $item['blog_category'] = array_column($term, 'id');
                }

            }

            // add tags
            $list_tags = explode(",", $item['blog_tags']);
           $tags = [];

            foreach ($list_tags as $key => $tag) {
                $term = Term::search($tag);
                $items = array_column($term, 'id');
                $tags = array_merge($tags, $items);
            }
            $item['blog_tags'] = array_values(array_filter($tags));


            $blog = [];
            if (!empty($images_fid)) {
                $blog['content_articles_field_other_images'] = $images_fid;
            }
            if (!empty($COVER_IMAGE)) {
                $blog['content_articles_field_cover_image'] = $COVER_IMAGE;
            }
            if (!empty($item['blog_category'])) {
                $blog['content_articles_field_category'] = $item['blog_category'];
            }
            if (!empty($item['blog_tags'])) {
                $blog['content_articles_field_tags'] = $item['blog_tags'];
            }

            $d = [
                ...$blog,
                'content_articles_field_body' => $item['blog_body'],
                'status' => 1,
                'bundle' => 'content_articles',
                'uid' => 1,
                'title' => $item['blog_title']
            ];

            // create blog
            $node = Node::create($d);

            if ($node instanceof Node) {
                $results += 1;
            }

        }

        self::runSettings('blog_on', $current_index + 10);
        return "Migrated {$results} blogs";
    }
}