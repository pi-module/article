<?php
/**
 * Article module list controller
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
use Module\Article\Service;
use Module\Article\Model\Article;
use Zend\Db\Sql\Expression;
use Pi\Paginator\Paginator;
use Pi;

/**
 * Public action controller for listing articles
 */
class ListController extends ActionController
{
    /**
     * Listing all articles for users to review. 
     */
    public function allAction()
    {
        $page   = Service::getParam($this, 'p', 1);
        
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

        $route  = '.' . Service::getRouteName();
        $resultset = $model->selectWith($select);
        $items     = array();
        foreach ($resultset as $row) {
            $items[$row->id] = $row->toArray();
            $publishTime     = date('Ymd', $row->time_publish);
            $items[$row->id]['url'] = $this->url($route, array('id' => $row->id, 'time' => $publishTime));
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
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $route,
                'params'    => array(
                    'module'        => $this->getModule(),
                    'controller'    => $this->getEvent()->getRouteMatch()->getParam('controller'),
                    'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
                    'list'          => 'all',
                ),
            ));

        $this->view()->assign(array(
            'title'     => __('All Articles'),
            'articles'  => $items,
            'paginator' => $paginator,
        ));
    }
}
