<?php

namespace Simp\Public\Module\services\src\Cron\MigrateHandle;

class Resources
{
    public array $categories = [];
    public array $blogs = [];
    public array $tags = [];
    public array $conctactUs = [];
    public array $fileManaged = [];
    public array $data = [];


    public function __construct()
    {
        $file = __DIR__ . "/old/u599963710_blogger.json";
        $data = json_decode(file_get_contents($file), true);

        foreach ($data as $key => $value) {

            if (!empty($value['type']) && $value['type'] === 'table') {


               if (!empty($value['name']) && $value['name'] === 'conctact_us') {
                    $this->conctactUs = $value['data'] ?? [];
               }

               elseif (!empty($value['name']) && $value['name'] === 'file_managed') {
                    $this->fileManaged = $value['data'] ?? [];
               }

               elseif (!empty($value['name']) && $value['name'] === 'blogs') {
                    $this->blogs = $value['data'] ?? [];
                    $tags = [];

                     foreach ($this->blogs as $blog) {
                        $list = explode(",", $blog['blog_tags']);
                        $tags = array_merge($tags, $list);
                     }

                     $this->tags = array_unique($tags);

               }

               elseif (!empty($value['name']) && $value['name'] === 'bog_categories') {
                    $this->categories = $value['data'] ?? [];
               }

            }

        }
    }
}