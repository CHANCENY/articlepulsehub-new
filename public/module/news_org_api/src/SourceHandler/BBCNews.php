<?php

namespace Simp\Public\Module\news_org_api\src\SourceHandler;

use Simp\Core\modules\structures\content_types\entity\Node;
use Simp\Core\modules\structures\taxonomy\Term;
use Simp\Public\Module\news_org_api\src\Plugin\Helper;

class BBCNews implements SourceArticleHandlerInterface
{
    use Helper;
    protected string $html_content;
    protected int $file_fid;

    protected int $category;

    protected string $title;
        protected string $content;
        protected string $url;
        protected string $image;
        protected string $published_at;

    public function __construct(
        string $title,
        string $content,
        string $url,
        string $image,
        string $published_at
    )
    {
        $this->title = $title;
        $this->content = $content;
        $this->url = $url;
        $this->image = $image;
        $this->published_at = $published_at;
        $this->category = 1;
        // remove the content from first [ to the end of the string
        $this->content = substr($this->content, 0,strpos($this->content, '['));
        $this->html_content = <<<HTML
<section>
    <p>
        {$this->content}
    </p>
    <a href="{$this->url}" target="_blank" rel="nofollow noopener noreferrer">
        Read More
    </a>
</section>
HTML;

        $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('image_');

        $this->image = str_contains($this->image, '?') ? substr($this->image, 0, strpos($this->image, '?')) : $this->image;

        if (file_put_contents($dest, file_get_contents($this->image))) {
            $filename = pathinfo($this->image, PATHINFO_FILENAME);
            $this->processCover($dest, $filename);
        }

        $term = Term::factory()->get('bbc_news');
        if (empty($term)) {
            $term = Term::factory()->create(
                'categories',
                'BBC News',
            );
            if ($term) {
                $term = Term::factory()->get('bbc_news');
            }
        }
        $this->category = reset($term)['id'];

    }

    /**
     * @throws \Exception
     */
    public function save(): string
    {
        $node = Node::create([
            'title' => $this->title,
            'uid' => 1,
            'status' => 1,
            'bundle' => 'content_articles',
            'content_articles_field_cover_image' => $this->file_fid,
            'content_articles_field_category' => $this->category,
            'content_articles_field_body'=> $this->html_content,
        ]);

        if ($node instanceof Node) {
            return "Created: ". $node->getTitle();
        }
        return '';
    }
}