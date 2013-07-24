<?php
/**
 * Article module my controller
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) http://www.eefocus.com
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
use Module\Article\Model\Draft;
use Module\Article\Model\Article;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Module\Article\Service;
use Module\Article\Role;

class MyController extends ActionController
{
    public function showDraftPage($status)
    {
        $where  = array();
        $page   = Service::getParam($this, 'page', 1);
        $limit  = Service::getParam($this, 'limit', 20);

//        $keyword = Service::getParam($this, 'keyword', '');
//        if ($keyword) {
//            $where['subject like ?'] = sprintf('%%%s%%', $keyword);
//        }

        $where['status']        = $status;
        $where['user']          = Pi::registry('user')->id;
        $where['article < ?']   = 1;

        $module         = $this->getModule();
        $modelDraft     = $this->getModel('draft');

        $resultsetDraft = Service::getDraftPage($where, $page, $limit, null, null, $module);

        // Total count
        $totalCount = $modelDraft->getSearchRowsCount($where);

        // Paginator
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'pageParam' => 'page',
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
                'params'    => array(
                    'module'        => $module,
                    'controller'    => 'my',
                    'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
//                    'keyword'       => $keyword,
                    'limit'         => $limit,
                ),
            ));

        $this->view()->assign(array(
            'data'      => $resultsetDraft,
            'paginator' => $paginator,
            'status'    => $status,
            'page'      => $page,
            'limit'     => $limit,
            'action'    => $this->getEvent()->getRouteMatch()->getParam('action'),
        ));
    }

    public function showArticlePage()
    {
        $where  = array();
        $page   = Service::getParam($this, 'page', 1);
        $limit  = Service::getParam($this, 'limit', 20);

//        $keyword = Service::getParam($this, 'keyword', '');
//        if ($keyword) {
//            $where['subject like ?'] = sprintf('%%%s%%', $keyword);
//        }

        $where['status'] = Article::FIELD_STATUS_PUBLISHED;
        $where['user']   = Pi::registry('user')->id;

        $module         = $this->getModule();
        $modelArticle   = $this->getModel('article');

        $resultsetArticle   = Service::getArticlePage($where, $page, $limit, null, null, $module);

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
                'params'    => array(
                    'module'        => $module,
                    'controller'    => 'my',
                    'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
//                    'keyword'       => $keyword,
                    'limit'         => $limit,
                ),
            ));

        $this->view()->assign(array(
            'data'      => $resultsetArticle,
            'paginator' => $paginator,
            'page'      => $page,
            'limit'     => $limit,
            'action'    => $this->getEvent()->getRouteMatch()->getParam('action'),
        ));
    }

    public function getSummary()
    {
        $result = array(
            'published' => 0,
            'draft'     => 0,
            'pending'   => 0,
            'rejected'  => 0,
        );

        $modelDraft = $this->getModel('draft');
        $select     = $modelDraft->select()
            ->columns(array('status', 'total' => new Expression('count(status)')))
            ->where(array(
                'user'          => Pi::registry('user')->id,
                'article < ?'   => 1,
            ))
            ->group(array('status'));
        $resultset  = $modelDraft->selectWith($select);
        foreach ($resultset as $row) {
            if (Draft::FIELD_STATUS_DRAFT == $row->status) {
                $result['draft'] = $row->total;
            } else if (Draft::FIELD_STATUS_PENDING == $row->status) {
                $result['pending'] = $row->total;
            } else if (Draft::FIELD_STATUS_REJECTED == $row->status) {
                $result['rejected'] = $row->total;
            }
        }

        $modelArticle   = $this->getModel('article');
        $select         = $modelArticle->select()
            ->columns(array('total' => new Expression('count(id)')))
            ->where(array(
                'user'   => Pi::registry('user')->id,
                'status' => Article::FIELD_STATUS_PUBLISHED,
            ));
        $resultset = $modelArticle->selectWith($select);
        if ($resultset->count()) {
            $result['published'] = $resultset->current()->total;
        }

        return $result;
    }
    
    public function indexAction()
    {
        $this->redirect()->toRoute('', array(
            'action' => 'draft',
        ));
        $this->view()->setTemplate(false);
    }

    public function pendingAction()
    {
        /**#@+
        * Added by Zongshu Lin
        */
        $rules = Role::getAllowedResources($this, PermController::PERM_WRITER, 'pending');
        /**#@-*/
        $this->showDraftPage(Draft::FIELD_STATUS_PENDING);

        $this->view()->assign(array(
            'title'   => __('Pending'),
            'summary' => $this->getSummary(),
            /**#@+
             * Added by Zongshu Lin
             */
            'rules'   => $rules,
            /**#@-*/
        ));
    }

    public function rejectedAction()
    {
        /**#@+
        * Added by Zongshu Lin
        */
        $rules = Role::getAllowedResources($this, PermController::PERM_WRITER, 'rejected');
        /**#@-*/
        $this->showDraftPage(Draft::FIELD_STATUS_REJECTED);

        $this->view()->assign(array(
            'title'   => __('Rejected'),
            'summary' => $this->getSummary(),
            /**#@+
             * Added by Zongshu Lin
             */
            'rules'   => $rules,
            /**#@-*/
        ));
    }

    public function draftAction()
    {
        /**#@+
        * Added by Zongshu Lin
        */
        $rules = Role::getAllowedResources($this, PermController::PERM_WRITER);
        if (empty($rules)) {
            return $this->jumpToDenied('__denied__');
        }
        /**#@-*/
        $this->showDraftPage(Draft::FIELD_STATUS_DRAFT);

        $this->view()->assign(array(
            'title'   => __('Draft'),
            'summary' => $this->getSummary(),
            /**#@+
             * Added by Zongshu Lin
             */
            'rules'   => $rules,
            /**#@-*/
        ));
    }

    public function publishedAction()
    {
        /**#@+
        * Added by Zongshu Lin
        */
        $rules = Role::getAllowedResources($this, PermController::PERM_WRITER);
        /**#@-*/
        $this->showArticlePage();

        $this->view()->assign(array(
            'title'   => __('Published'),
            'summary' => $this->getSummary(),
            /**#@+
             * Added by Zongshu Lin
             */
            'rules'  => $rules,
            /**#@-*/
        ));
    }
}
