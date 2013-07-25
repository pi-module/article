<?php
/**
 * Article module article controller
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Pi Engine http://www.xoopsengine.org
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Lijun Dong <lijun@eefocus.com>
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article\Controller\Front;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Pi\Paginator\Paginator;
use Module\Article\Model\Article;
use Module\Article\Model\Draft;
use Module\Article\Model\Asset;
use Module\Article\Upload;
use Module\Article\Form\DraftSearchForm;
use Module\Article\Form\SimpleSearchForm;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Module\Article\Service;
use Module\Article\Cache;
use Module\Article\Role;
use Module\Article\Model\Extended;

/**
 * Public class for article 
 */
class ArticleController extends ActionController
{
    /**
     * Article homepage, all page content are dressed up by user 
     */
    public function indexAction()
    {

    }
    
    /**
     * Article detail page.
     * 
     * @return ViewModel 
     */
    public function detailAction()
    {
        $id       = $this->params('id');
        $slug     = $this->params('slug', '');
        $page     = $this->params('p', 1);
        $remain   = $this->params('r', '');
        
        if ('' !== $remain) {
            $this->view()->assign('remain', $remain);
        }

        $details = Service::getEntity($id);
        $params  = array();
        
        if (!$id or ($details['time_publish'] > time())) {
            return $this->jumpTo404(__('Page not found'));
        }
        if (empty($details['active'])) {
            return $this->jumpToException(__('The article requested is not active'), 503);
        }
        if (strval($slug) != $details['slug']) {
            $routeParams = array(
                'action'    => 'detail',
                'id'        => $id,
                'slug'      => $details['slug'],
                'p'         => $page,
            );
            if ($remain) {
                $params['r'] = $remain;
            }
            return $this->redirect()->setStatusCode(301)->toRoute('', array_merge($routeParams, $params));
        }
        
        foreach ($details['content'] as &$value) {
            $value['url'] = $this->url('', array_merge(array(
                'action'     => 'detail',
                'id'         => $id,
                'slug'       => $slug,
                'p'          => $value['page'],
            ), $params));
            if (isset($value['title']) and preg_replace('/&nbsp;/', '', trim($value['title'])) !== '') {
                $showTitle = true;
            } else {
                $value['title'] = '';
            }
        }
        $details['view'] = $this->url('', array_merge(array(
            'action'      => 'detail',
            'id'          => $id,
            'slug'        => $slug,
            'r'           => 0,
        ), $params));
        $details['remain'] = $this->url('', array_merge(array(
            'action'      => 'detail',
            'id'          => $id,
            'slug'        => $slug,
            'r'           => $page,
        ), $params));

        $this->view()->assign(array(
            'details'     => $details,
            'page'        => $page,
            'showTitle'   => isset($showTitle) ? $showTitle : null,
            'config'      => Pi::service('module')->config('', $this->getModule()),
        ));
    }

    /**
     * Deleting published articles
     * 
     * @return ViewModel 
     */
    public function deleteAction()
    {
        $id     = Service::getParam($this, 'id', '');
        $ids    = array_filter(explode(',', $id));
        $from   = Service::getParam($this, 'from', '');

        if (empty($ids)) {
            return $this->jumpTo404(__('Invalid article ID'));
        }
        
        $module         = $this->getModule();
        $modelArticle   = $this->getModel('article');
        //$modelAsset     = $this->getModel('asset');

        $resultsetArticle = $modelArticle->select(array('id' => $ids));

        // Step operation
        foreach ($resultsetArticle as $article) {
            // Delete feature image
            if ($article->image) {
                unlink(Pi::path($article->image));
                unlink(Pi::path(Upload::getThumbFromOriginal($article->image)));
            }
        }

        // Batch operation
        // Deleting extended fields
        $this->getModel('extended')->delete(array('article' => $ids));
        
        // Deleting statistics
        $this->getModel('statistics')->delete(array('article' => $ids));
        
        // Deleting compiled article
        $this->getModel('compiled')->delete(array('article' => $ids));
        
        // Delete tag
        if ($this->config('enable_tag')) {
            Pi::service('tag')->delete($module, $ids);
        }
        // Delete related articles
        $this->getModel('related')->delete(array('article' => $ids));

        // Delete visits
        $this->getModel('visit')->delete(array('article' => $ids));

        // Delete assets
        /*$resultsetAsset = $modelAsset->select(array('article' => $ids));
        foreach ($resultsetAsset as $asset) {
            unlink(Pi::path($asset->path));

            if (Asset::FIELD_TYPE_IMAGE == $asset->type) {
                unlink(Pi::path(Upload::getThumbFromOriginal($asset->path)));
            }
        }
        $modelAsset->delete(array('article' => $ids));*/

        // Update status
        $modelArticle->update(
            array('status' => Article::FIELD_STATUS_DELETED),
            array('id' => $ids)
        );

        // Clear cache
        Pi::service('render')->flushCache($module);

        if ($from) {
            $from = urldecode($from);
            $this->redirect()->toUrl($from);
        } else {
            // Go to list page
            return $this->redirect()->toRoute('', array(
                'controller' => 'article',
                'action'     => 'published',
                'from'       => 'all',
            ));
        }
    }

    /**
     * Active or deactivate article 
     */
    public function activateAction()
    {      
        $id     = Service::getParam($this, 'id', '');
        $ids    = array_filter(explode(',', $id));
        $status = Service::getParam($this, 'status', 0);
        $from   = Service::getParam($this, 'from', '');

        if ($ids) {
            $module         = $this->getModule();
            $modelArticle   = $this->getModel('article');
            $modelArticle->setActiveStatus($ids, $status ? 1 : 0);

            // Clear cache
            Pi::service('render')->flushCache($module);
            Pi::service('render')->flushCache('channel');
        }

        if ($from) {
            $from = urldecode($from);
            $this->redirect()->toUrl($from);
        } else {
            // Go to list page
            $this->redirect()->toRoute('', array('action' => 'published', 'from' => 'all'));
        }
        $this->view()->setTemplate(false);
    }

    /**
     * Editing a published article, the article details will be copy to draft table,
     * and then redirecting to edit page.
     * 
     * @return ViewModel 
     */
    public function editAction()
    {
        $id     = Service::getParam($this, 'id', 0);
        $module = $this->getModule();

        if (!$id) {
            return $this->jumpTo404(__('Invalid article ID'));
        }
        
        // Check if draft exists
        $draftModel = $this->getModel('draft');
        $rowDraft   = $draftModel->find($id, 'article');

        if ($rowDraft) {
            Service::deleteDraft($rowDraft->id, $module);
        }

        // Create new draft if no draft exists
        $model = $this->getModel('article');
        $row   = $model->find($id);

        if (!$row->id or $row->status != Article::FIELD_STATUS_PUBLISHED) {
            return $this->jumpTo404(__('Can not create draft'));
        }
        
        $draft = array(
            'article'         => $row->id,
            'subject'         => $row->subject,
            'subtitle'        => $row->subtitle,
            'summary'         => $row->summary,
            'content'         => $row->content,
            'uid'             => $row->uid,
            'author'          => $row->author,
            'source'          => $row->source,
            'pages'           => $row->pages,
            'category'        => $row->category,
            'status'          => Draft::FIELD_STATUS_DRAFT,
            'time_save'       => time(),
            'time_submit'     => $row->time_submit,
            'time_publish'    => $row->time_publish,
            'time_update'     => $row->time_update,
            'image'           => $row->image,
        );
        
        // Getting extended fields
        $modelExtended = $this->getModel('extended');
        $rowExtended   = $modelExtended->find($row->id, 'article');
        $extendColumns = $modelExtended->getValidColumns();
        foreach ($extendColumns as $col) {
            $draft[$col] = $rowExtended->$col;
        }

        // Get related articles
        $relatedModel = $this->getModel('related');
        $related      = $relatedModel->getRelated($id);
        $draft['related'] = $related;

        // Get tag
        if ($this->config('enable_tag')) {
            $draft['tag'] = Pi::service('tag')->get($module, $id);
        }

        // Save as draft
        $draftRow = $draftModel->createRow($draft);
        $draftRow->save();

        $draftId = $draftRow->id;

        // Copy assets
        /*$resultsetAsset = $this->getModel('asset')->select(array(
            'article' => $id,
        ));
        $modelDraftAsset = $this->getModel('draft_asset');
        foreach ($resultsetAsset as $asset) {
            $data = array(
                'original_name' => $asset->original_name,
                'name'          => $asset->name,
                'extension'     => $asset->extension,
                'size'          => $asset->size,
                'mime_type'     => $asset->mime_type,
                'type'          => $asset->type,
                'path'          => $asset->path,
                'time_create'   => $asset->time_create,
                'user'          => $asset->user,
                'draft'         => $draftId,
                'published'     => 1,
            );
//                        $data['path'] = Upload::copyAttachmentToTmp($attachment->path, $module);
            $rowDraftAsset = $modelDraftAsset->createRow($data);
            $rowDraftAsset->save();
        }*/

        // Redirect to edit draft
        if ($draftId) {
            return $this->redirect()->toRoute('', array(
                'action'     => 'edit',
                'controller' => 'draft',
                'id'         => $draftId,
                'from'       => 'all',
            ));
        }
    }

    /**
     * Listing all articles for users to review. 
     */
    public function listAction()
    {
        $page   = Service::getParam($this, 'page', 1);
        
        $where  = array(
            'status'           => Article::FIELD_STATUS_PUBLISHED,
            'active'           => 1,
            'time_publish < ?' => time(),
        );
        
        //@todo Get limit from module config
        $limit  = (int) $this->config('page_limit_front');
        $limit  = $limit ?: 40;
        $offset = $limit * ($page - 1);

        $model  = $this->getModel('article');
        $select = $model->select()->where($where);
        $select->order('time_publish DESC')->offset($offset)->limit($limit);

        $resultset = $model->selectWith($select);
        $items     = array();
        foreach ($resultset as $row) {
            $items[$row->id] = $row->toArray();
            $items[$row->id]['url'] = $this->url('', array('action' => 'detail', 'id' => $row->id));
        }

        // Total count
        $select     = $model->select()->where($where)->columns(array('total' => new Expression('count(id)')));
        $articleCountResultset = $model->selectWith($select);
        $totalCount = intval($articleCountResultset->current()->total);

        // Paginator
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'pageParam' => 'page',
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
                'params'    => array(
                    'module'        => $this->getModule(),
                    'controller'    => $this->getEvent()->getRouteMatch()->getParam('controller'),
                    'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
                ),
            ));

        $this->view()->assign(array(
            'title'     => __('All Articles'),
            'articles'  => $items,
            'paginator' => $paginator,
        ));
    }

    /**
     * Processing published article list 
     */
    public function publishedAction()
    {
        $where  = $whereChannel = array();
        $page   = Service::getParam($this, 'page', 1);
        $limit  = Service::getParam($this, 'limit', 20);
        $from   = Service::getParam($this, 'from', 'my');
        $order  = 'time_publish DESC';

        $data   = $ids = array();

        $module         = $this->getModule();
        $modelArticle   = $this->getModel('article');
        $categoryModel  = $this->getModel('category');

        $category = Service::getParam($this, 'category', 0);
        if ($category > 1) {
            $categoryIds = $categoryModel->getDescendantIds($category);
            if ($categoryIds) {
                $where['category'] = $categoryIds;
            }
        }

        $filter = Service::getParam($this, 'filter', '');
        if ($filter == 'active') {
            $where['active'] = 1;
        } else if ($filter == 'deactive') {
            $where['active'] = 0;
        }

        // Build where
        $where['status'] = Article::FIELD_STATUS_PUBLISHED;

        // Retrieve data
        $data = Service::getArticlePage($where, $page, $limit, null, $order, $module);

        // Total count
        $select = $modelArticle->select()
            ->columns(array('total' => new Expression('count(id)')))
            ->where($where);
        $resulsetCount = $modelArticle->selectWith($select);
        $totalCount    = (int) $resulsetCount->current()->total;

        // Paginator
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
            'pageParam' => 'page',
            'router'    => $this->getEvent()->getRouter(),
            'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
            'params'    => array_filter(array(
                'module'        => $module,
                'controller'    => $this->getEvent()->getRouteMatch()->getParam('controller'),
                'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
                'category'      => $category,
                'filter'        => $filter,
            )),
        ));

        // Prepare search form
        $form = new SimpleSearchForm;

        $form->setData($this->params()->fromQuery());
        
        $flags = array(
            'draft'     => Draft::FIELD_STATUS_DRAFT,
            'pending'   => Draft::FIELD_STATUS_PENDING,
            'rejected'  => Draft::FIELD_STATUS_REJECTED,
            'published' => Article::FIELD_STATUS_PUBLISHED,
        );

        $this->view()->assign(array(
            'title'      => __('Published'),
            'data'       => $data,
            'form'       => $form,
            'paginator'  => $paginator,
            'summary'    => Service::getSummary('all'),
            'category'   => $category,
            'filter'     => $filter,
            'categories' => Cache::getCategoryList(),
            'action'     => 'published',
            'flags'      => $flags,
            'status'     => Article::FIELD_STATUS_PUBLISHED,
            'from'       => $from,
        ));
        
        if ('my' == $from) {
            $this->view()->setTemplate('draft-list');
        }
    }

    public function searchAction()
    {
        $keyword    = Service::getParam($this, 'keyword', '');
        $page       = Service::getParam($this, 'page', 1);
        $limit      = Service::getParam($this, 'limit', 20);

        $data       = $where = array();

        $module         = $this->getModule();
        $modelArticle   = $this->getModel('article');

        // Build where
        $where = array('status' => Article::FIELD_STATUS_PUBLISHED);
        if ($keyword) {
            $where['subject like ?'] = sprintf('%%%s%%', $keyword);
        }

        // Retrieve data
        $data = Service::getArticlePage($where, $page, $limit, null, null, $module);

        // Total count
        $totalCount = $modelArticle->getSearchRowsCount($where);

        // Paginator
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
            'pageParam' => 'page',
            'router'    => $this->getEvent()->getRouter(),
            'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
            'params'    => array_filter(array(
                'module'        => $module,
                'controller'    => $this->getEvent()->getRouteMatch()->getParam('controller'),
                'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
                'keyword'       => $keyword,
                'limit'         => $limit,
            )),
        ));

        $this->view()->assign(array(
            'title'     => __('Pending'),
            'data'      => $data,
            'keyword'   => $keyword,
            'page'      => $page,
            'limit'     => $limit,
            'paginator' => $paginator,
            'summary'   => $this->getSummary(),
        ));
    }
}
