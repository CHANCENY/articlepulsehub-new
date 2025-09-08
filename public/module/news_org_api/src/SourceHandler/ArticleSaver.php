<?php

namespace Simp\Public\Module\news_org_api\src\SourceHandler;

use Simp\Core\modules\structures\content_types\entity\Node;
use Simp\Core\modules\structures\taxonomy\Term;
use Simp\Public\Module\news_org_api\src\Plugin\Helper;

class ArticleSaver implements SourceArticleHandlerInterface
{
    use Helper;
    private string $title;
    private string $content;
    private string $url;
    private string $image;
    private string $published_at;
    private string $category;
    private string $html_content;

    public function __construct(string $title, string $content, string $url, string $image, string $published_at, string $category)
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
<article>
    <p>
        {$this->content}
    </p>
    <a href="{$this->url}" target="_blank" rel="nofollow noopener noreferrer">
        Read More
    </a>
</article>
HTML;

        $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('image_');

        $this->image = str_contains($this->image, '?') ? substr($this->image, 0, strpos($this->image, '?')) : $this->image;

        if (file_put_contents($dest, file_get_contents($this->image))) {
            $filename = pathinfo($this->image, PATHINFO_FILENAME);
            $this->processCover($dest, $filename);
        }

        $this->category = $this->getCategory($category);;
    }

    private function getCategory(string $category): int
    {
        // lowercase
        $clean_name = strtolower($category);

        // replace spaces and special characters with "_"
        $clean_name = preg_replace('/[^a-z0-9]+/', '_', $clean_name);

        // collapse multiple underscores into a single "_"
        $clean_name = preg_replace('/_+/', '_', $clean_name);

        // trim underscores from start and end (optional, but cleaner)
        $clean_name = trim($clean_name, '_');

        $term = Term::factory()->get($clean_name);

        if (empty($term[0])) {
            $term = Term::factory()->create(
                'categories',
                $category
            );
            if ($term) {
                $term = Term::factory()->get($clean_name);
                return reset($term)['id'];
            }
            return 1;
        }
        return reset($term)['id'];

    }

    /**
     * @throws \Exception
     */
    public function save(): string
    {
        $node_storage = Node::nodeStorage('content_articles');
        $node_storage->addWhere('node_data.title = :title',['title' => $this->title]);
        $node_storage->limit(1);
        $node_storage->execute();
        if ($node_storage->count() > 0) {
            return "Node already exists";
        }

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

    private function getArticleContent(string $url, string $default = ''): false|string
    {
        // fetch with curl
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MyBot/1.0)' // good practice
        ]);
        $html = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // check if 200 OK
        if ($status !== 200 || !$html) {
            return false;
        }

        // parse DOM
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $articles = $dom->getElementsByTagName('article');
        if ($articles->length === 0) {
            return $default;
        }

        $innerHTML = '';

        foreach ($articles as $article) {
            // 1. Add rel="nofollow" etc to <a>
            foreach ($article->getElementsByTagName('a') as $a) {
                $a->setAttribute('rel', 'nofollow noopener noreferrer');
            }

            // 2. Prevent images from being indexed
            foreach ($article->getElementsByTagName('img') as $img) {
                $img->setAttribute('alt', '');
                $img->setAttribute('aria-hidden', 'true');
                $img->setAttribute('data-noindex', 'true');
            }

            // 3. Extract processed innerHTML
            foreach ($article->childNodes as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }
        }

        return !empty($innerHTML) ? $innerHTML : $default;
    }
}