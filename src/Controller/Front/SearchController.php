<?php
/**
 * Article module search controller
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
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article\Controller\Front;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Pi\Paginator\Paginator;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Module\Article\Model\Article;
use Module\Article\Service;
use Module\Article\Entity;

/**
 * Public class for searching articles 
 */
class SearchController extends ActionController
{
    /**
     * Searching articles by title. 
     */
    public function simpleAction()
    {
        $order  = 'time_publish DESC';
        $page   = Service::getParam($this, 'p', 1);
        $module = $this->getModule();

        $config = Pi::service('module')->config('', $module);
        $limit  = intval($config['page_limit_front']) ?: 40;
        $offset = $limit * ($page - 1);

        // Build where
        $where   = array();
        $keyword = Service::getParam($this, 'keyword', '');
        if ($keyword) {
            $where['subject like ?'] = sprintf('%%%s%%', $keyword);
        }
        
        // Retrieve data
        $articleResultset = Entity::getAvailableArticlePage($where, $page, $limit, null, $order, $module);

        // Total count
        $where = array_merge($where, array(
            'time_publish <= ?' => time(),
            'status'            => Article::FIELD_STATUS_PUBLISHED,
            'active'            => 1,
        ));
        $modelArticle   = $this->getModel('article');
        $totalCount     = $modelArticle->getSearchRowsCount($where);

        // Paginator
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
            'router'    => $this->getEvent()->getRouter(),
            'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
            'params'    => array_filter(array(
                'module'        => $module,
                'controller'    => $this->getEvent()->getRouteMatch()->getParam('controller'),
                'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
                'keyword'       => $keyword,
            )),
        ));

        $this->view()->assign(array(
            'title'        => __('Search result about '),
            'articles'     => $articleResultset,
            'keyword'      => $keyword,
            'p'            => $page,
            'paginator'    => $paginator,
        ));
    }
}
