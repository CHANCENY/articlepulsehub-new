<?php

namespace Simp\Public\Module\services\src\Controller;


use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\components\extensions\ModuleHandler;
use Simp\Core\components\site\SiteManager;
use Simp\Core\extends\form_builder\src\Plugin\Submission;
use Simp\Core\lib\routes\Route;
use Simp\Core\lib\themes\View;
use Simp\Core\modules\mail\MailQueueManager;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\structures\content_types\entity\Node;
use Simp\Core\modules\structures\taxonomy\Term;
use Simp\Core\modules\user\entity\User;
use Simp\Mail\Mail\Envelope;
use Simp\Mail\Mail\MailManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ArticlesController
{
    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function articlesListing(...$args): Response
    {
        extract($args);

        $page = $request->get('page', 0);
        $offset = 0;

        if ($page > 0) {
            $offset = ($page - 1) * 6;
        }

        $node_storage = Node::nodeStorage('content_articles');
        $node_storage->addWhere("status = :status", ['status' => 1]);

        $node_storage->orderBy('updated', 'DESC');
        $node_storage->limit(6);
        $node_storage->offset($offset);
        $node_storage->execute();

        $pagination = Node::nodeStorage('content_articles');
        $pagination->addWhere("status = :status", ['status' => 1]);
        $pagination->orderBy('updated', 'DESC');
        $pagination->limit(6);
        $pagination_data = $pagination->paginate($page);

        return new Response(View::view('articlepulsehub.view.views.views_view_articles_listing.results.rows',[
            'articles' => $node_storage,
            'pagination' => $pagination_data,
            'page_title' => "Recent Blog Articles on Finance, Lifestyle, Technology & More | ".SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
        ]));
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function articleView(...$args): Response
    {
        extract($args);

        $nid = $request->get('nid');

        /**@var Route $route **/
        $route = $options['options']['route'] ?? "";

        $nid = !empty($route->getOptions()['node']) ? $route->getOptions()['node'] : $nid ?? 0;

        $node = Node::load($nid);

        ModuleHandler::factory()->attachLibrary('form_builder', 'form.builder.library.js');

        return new Response(View::view('articlepulsehub.view.views.views_view_articles_listing.result',[
            'article' => $node,
            'is_found' => !empty($node),
            'page_title' => $node->getTitle() ." |" . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
        ]),!empty($node) ? 200 : 404);
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function articleCategory(...$args): Response
    {
        extract($args);

        $name = $request->get('name');

        if (empty($name)) {
            return new RedirectResponse($request->headers->get('referer'));
        }

        $term = Term::factory()->get($name);
        $term = reset($term);

        $page = $request->get('page', 0);

        $offset = 0;

        if ($page > 0) {
            $offset = ($page - 1) * 6;
        }

        $node_storage = Node::nodeStorage('content_articles');
        $node_storage->addWhere("status = :status", ['status' => 1]);
        $node_storage->addJoin('node__content_articles_field_category','content_c','node_data.nid = content_c.nid');
        $node_storage->addWhere("content_c.content_articles_field_category__value = :tid", ['tid' => $term['id']]);
        $node_storage->orderBy('updated', 'DESC');
        $node_storage->limit(6);
        $node_storage->offset($offset);
        $node_storage->execute();

        $pagination = Node::nodeStorage('content_articles');
        $pagination->addWhere("status = :status", ['status' => 1]);
        $pagination->addJoin('node__content_articles_field_category','content_c','node_data.nid = content_c.nid');
        $pagination->addWhere("content_c.content_articles_field_category__value = :tid", ['tid' => $term['id']]);
        $pagination->orderBy('updated', 'DESC');
        $pagination->limit(6);
        $pagination_data = $pagination->paginate($page);

        return new Response(View::view('articlepulsehub.view.views.articles.category',[
            'articles' => $node_storage,
            'pagination' => $pagination_data,
            'term' => $term,
            'page_title' => $term['label'] ." | " . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
        ]));
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function articleTag(...$args): Response
    {
        extract($args);

        $name = $request->get('name');

        if (empty($name)) {
            return new RedirectResponse($request->headers->get('referer'));
        }

        $term = Term::factory()->get($name);
        $term = reset($term);

        $page = $request->get('page', 0);

        $offset = 0;

        if ($page > 0) {
            $offset = ($page - 1) * 6;
        }

        $node_storage = Node::nodeStorage('content_articles');
        $node_storage->addWhere("status = :status", ['status' => 1]);
        $node_storage->addJoin('node__content_articles_field_tags','content_c','node_data.nid = content_c.nid');
        $node_storage->addWhere("content_c.content_articles_field_tags__value = :tid", ['tid' => $term['id']]);
        $node_storage->orderBy('updated', 'DESC');
        $node_storage->limit(6);
        $node_storage->offset($offset);
        $node_storage->execute();

        $pagination = Node::nodeStorage('content_articles');
        $pagination->addWhere("status = :status", ['status' => 1]);
        $pagination->addJoin('node__content_articles_field_tags','content_c','node_data.nid = content_c.nid');
        $pagination->addWhere("content_c.content_articles_field_tags__value = :tid", ['tid' => $term['id']]);
        $pagination->orderBy('updated', 'DESC');
        $pagination->limit(6);
        $pagination_data = $pagination->paginate($page);

        return new Response(View::view('articlepulsehub.view.views.articles.tag',[
            'articles' => $node_storage,
            'pagination' => $pagination_data,
            'term' => $term,
            'page_title' => $term['label'] ." | " . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
        ]));
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function contactUs(...$args): Response
    {
        extract($args);

        if ($request->isMethod('POST')) {

            if (!empty($request->request->get('your_full_name')) &&
                !empty($request->request->get('form_field_email_2')) &&
                !empty($request->request->get('request_subject')) &&
                !empty($request->request->get('request_message')))
            {
                $data_message = $request->request->all();

                $submission = Submission::factory(form_name: 'contact_us_form');
                $submission = $submission->create($data_message);

                $form_settings = $submission->getSettings();

                if ($form_settings->getNotify()) {

                    $envelope = Envelope::create(
                        SiteManager::factory()->get('site_name', 'Articlepulsehub'). " - ".$submission->get('request_subject')[0]['value'],
                        $submission->get('request_message')[0]['value'],
                    );

                    $envelope->addToAddresses([
                        $form_settings->getNotify()
                    ]);

                    $queue = MailQueueManager::factory();
                    $queue->add($envelope);
                    $queue->send();

                    Messager::toast()->addMessage($form_settings->getConfirmation());

                }
                else {
                    Messager::toast()->addMessage("Thank you for contacting us. We will get back to you soon.");
                }

            }

            return new RedirectResponse(Route::url('services.contact.us'));
        }
        return new Response(View::view('articlepulsehub.view.views.contact_us.contact_us',[
            'page_title' => "Contact Us | " . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
        ]));
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function aboutUs(...$args): Response
    {
        extract($args);
        return new Response(View::view('articlepulsehub.view.views.about_us.about_us',[
            'page_title' => "About Us | " . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
        ]));
    }

    public function privacyPolicy(...$args): Response
    {
        extract($args);
        return new Response(View::view('articlepulsehub.view.views.privacy_policy.privacy_policy',[
            'page_title' => "Privacy Policy | " . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
        ]));
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function search(...$args): Response
    {
        extract($args);
        $query = $request->get('q');
        $page = $request->get('page', 0);
        $offset = 0;
        if ($page > 0) {
            $offset = ($page - 1) * 6;
        }

        $node_storage = Node::nodeStorage('content_articles');
        $node_storage->addWhere("status = :status", ['status' => 1]);
        $node_storage->addWhere("title LIKE :query", ['query' => "%$query%"]);
        $node_storage->orderBy('updated', 'DESC');
        $node_storage->limit(6);
        $node_storage->offset($offset);
        $node_storage->execute();

        $pagination = Node::nodeStorage('content_articles');
        $pagination->addWhere("status = :status", ['status' => 1]);
        $pagination->addWhere("title LIKE :query", ['query' => "%$query%"]);
        $pagination->orderBy('updated', 'DESC');
        $pagination->limit(6);
        $pagination_data = $pagination->paginate($page);

        return new Response(View::view('articlepulsehub.view.views.search.search',[
            'articles' => $node_storage,
            'pagination' => $pagination_data,
            'page_title' => "Search Results for \"$query\" | " . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
            'query' => $query,
        ]));
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function accountView(...$args): Response
    {
        extract($args);
        $uid = $request->get('uid');

        if (empty($uid)) {
            return new RedirectResponse(Route::url('/'));
        }

        $user = User::load($uid);
        $profile = $user->getProfile();
        $full_name = "{$profile->getFirstName()} {$profile->getLastName()}";

        return new Response(View::view('articlepulsehub.view.views.account.account',[
            'user' => $user,
            'profile' => $profile,
            'page_title' => "Author ".$full_name." | " . SiteManager::factory()->get('site_name', 'ArticlePulseHub'),
            'full_name' => $full_name,
        ]));
    }
}