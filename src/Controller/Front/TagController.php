<?php
/**
 * Article module tag controller
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Engine http://www.xoopsengine.org
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
use Module\Article\Upload;
use Module\Article\Service;
use Module\Article\Entity;
use Zend\View\Helper\Placeholder\Container\AbstractContainer;

/**
 * Public class 
 */
class TagController extends ActionController
{
    /**
     * Processing article list related with tag
     * 
     * @return ViewModel 
     */
    public function listAction()
    {
        $tag    = Service::getParam($this, 'tag', '');
        $page   = Service::getParam($this, 'p', 1);
        $page   = $page > 0 ? $page : 1;
        $where  = $articleIds = $articles = array();

        if (empty($tag)) {
            return $this->jumpTo404(__('Cannot find this page'));
        }

        $module = $this->getModule();
        $config = Pi::service('module')->config('', $module);
        $limit  = $config['page_limit_front'] ?: 40;
        $offset = ($page - 1) * $limit;

        // Total count
        $totalCount = (int) Pi::service('tag')->getCount($module, $tag);

        // Get article ids
        $articleIds = Pi::service('tag')->getList($module, $tag, null, $limit, $offset);

        if ($articleIds) {
            $where['id']    = $articleIds;
            $articles       = array_flip($articleIds);
            $columns        = array('id', 'subject', 'time_publish', 'category');

            $resultsetArticle   = Entity::getAvailableArticlePage($where, 1, $limit, $columns, '', $module);

            foreach ($resultsetArticle as $key => $val) {
                $articles[$key] = $val;
            }

            $articles = array_filter($articles, function($var) {
                return is_array($var);
            });
        }

        $route = '.' . Service::getRouteName();
        // Pagination
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $route,
                'params'    => array(
                    'tag'           => $tag,
                ),
            ));

        $this->view()->assign(array(
            'title'     => __('Article List with Tag'),
            'articles'  => $articles,
            'paginator' => $paginator,
            'p'         => $page,
            'tag'       => $tag,
            'config'    => $config,
        ));

        $this->view()->viewModel()->getRoot()->setVariables(array(
            'breadCrumbs' => true,
            'Tag'         => $tag,
        ));
    }
}
