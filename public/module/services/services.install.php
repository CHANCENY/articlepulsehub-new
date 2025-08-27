<?php

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Simp\Core\modules\structures\taxonomy\Term;
use Simp\Public\Module\services\src\Controller\ArticlesController;
use Simp\Public\Module\services\src\Twig\TwigHelperFunctionAction;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Hooks file
 */

function services_cron_subscribers_install(): array
{
    return [
        'services_blogs_migration.migration' => \Simp\Public\Module\services\src\Cron\MigrationCron::class,
    ];
}

function services_cron_jobs_install(): array
{
    return [
        'services_blogs_migration' => [
            'title' => 'Services Blogs Migration',
            'description' => 'Migrating old blogs to new server database',
            'timing' => 'every|minute',
            'subscribers' => 'services_blogs_migration.migration'
        ]
    ];
}

function services_twig_function_install(): array
{
    /**
     * Register important twig functions here
     *
     */
    return [
        new TwigFunction('feature_articles',function (){
            return TwigHelperFunctionAction::getFeaturedArticles();
        }),
        new TwigFunction('term', function (int $tid) {
            return Term::load($tid);
        }),
        new TwigFunction('recent_articles', function () {
            return TwigHelperFunctionAction::getRecentArticles();
        }),
        new TwigFunction('categories', function () {
            return TwigHelperFunctionAction::getCategories();
        }),
        new TwigFunction('tags', function () {
            return TwigHelperFunctionAction::getTags();
        }),
        new TwigFunction('popular_articles', function () {
            return TwigHelperFunctionAction::getHomeArticles();
        }),
        new TwigFunction('blog_terms', function (array $tags, int $limit = 1) {
            $terms = Term::factory()->getTermsByTid($tags);
            return array_slice($terms, 0, $limit);
        }),
        new TwigFunction('get_comments', function (array $sids){
            return TwigHelperFunctionAction::getComments($sids);
        }),
        new TwigFunction('get_call_to_action', function (){
            return TwigHelperFunctionAction::getCallToAction();
        })
    ];
}

function services_twig_extension_install(): array
{
    return [
        new \Twig\Extra\String\StringExtension()
    ];
}

function services_route_install(): array
{
    return array(
        'services.articles' => array(
            'title' => 'Our Recent Blog Entries',
            'path' => '/articles',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'articlesListing'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.articles.pagination' => array(
            'title' => 'Our Recent Blog Entries',
            'path' => '/articles/page/[page:int]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'articlesListing'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.articles.view' => array(
            'title' => 'Article View',
            'path' => '/articles/[nid:int]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'articleView'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.articles.category' => array(
            'title' => 'Article Category',
            'path' => '/articles/category/[name:string]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'articleCategory'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.articles.category.pagination' => array(
            'title' => 'Article Category',
            'path' => '/articles/category/[name:string]/page/[page:int]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'articleCategory'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.articles.tag' => array(
            'title' => 'Article Tag',
            'path' => '/articles/tag/[name:string]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'articleTag'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.articles.tag.pagination' => array(
            'title' => 'Article Tag',
            'path' => '/articles/tag/[name:string]/page/[page:int]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'articleTag'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            )
        ),
        'services.contact.us' => array(
            'title' => 'Contact Us',
            'path' => '/en/contact-us',
            'method' => array('GET','POST'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'contactUs'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.about.us' => array(
            'title' => 'About Us',
            'path' => '/en/about-us',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'aboutUs'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.policy' => array(
            'title' => 'Privacy Policy',
            'path' => '/en/privacy-policy',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'privacyPolicy'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            ),
            'options' => array(
                'classes' => ['fa fa-list']
            )
        ),
        'services.search' => array(
            'title' => 'Search',
            'path' => '/en/search',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'search'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            )
        ),
        'services.search.pagination' => array(
            'title' => 'Search',
            'path' => '/en/search/page/[page:int]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'search'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            )
        ),
        'services.account.view' => array(
            'title' => 'Account View',
            'path' => '/en/account/[uid:int]',
            'method' => array('GET'),
            'controller' => array(
                'class' => ArticlesController::class,
                'method' => 'accountView'
            ),
            'access' => array(
                'anonymous',
                'authenticated',
                'content_creator',
                'manager',
                'administrator'
            )
        )
    );
}

function services_twig_filter_install(): array
{
    return array(

        new TwigFilter('strip_empty_tags', function (string $html) {
            do {
                $tmp = $html;
                $html = preg_replace('#<([^ >]+)[^>]*>([[:space:]]|&nbsp;)*</\\1>#i', '', $html);
            } while ($html !== $tmp);
            return $html;
        }),
        new TwigFilter('count', function (array $array) {
            $array = array_filter($array);
            return count($array);
        }),
        new TwigFilter('phone', function (string $number, string $region = 'MW') {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneProto = $phoneUtil->parse($number, $region);
                return $phoneUtil->format($phoneProto, PhoneNumberFormat::INTERNATIONAL);
            } catch (\libphonenumber\NumberParseException $e) {
                return preg_replace('/[^0-9]/', '', $number);
            }
        })
   );
}