<?php
/**
 * Article module statistics api
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

namespace Module\Article;

use Pi;
use Zend\Mvc\MvcEvent;
use Zend\Db\Sql\Expression;
use Module\Article\Service;
use Module\Article\Model\Article;

/**
 * Public APIs for article module itself 
 */
class Statistics
{
    protected static $module = 'article';
    
    /**
     * Event listener to run before page cache, so some operation will also work
     * if the page is cached.
     * If this code is added in action, it will be ignored if page is cached.
     * 
     * @param MvcEvent $e 
     */
    public function runBeforePageCache(MvcEvent $e)
    {
        $name = $e->getRouteMatch()->getParam('id');
        if (empty($name)) {
            $name = $e->getRouteMatch()->getParam('slug');
        }
        $module = $e->getRouteMatch()->getParam('module');
        
        self::addVisit($name, $module);
    }

    /**
     * Adding visit count and visit record.
     * 
     * @param int|string $name    Article ID or slug
     * @param string     $module  Module name
     */
    public static function addVisit($name, $module = null)
    {
        $module = $module ?: Pi::service('module')->current();
        
        if (!is_numeric($name)) {
            $model = Pi::model('article', $module);
            $name  = $model->slugToId($name);
        }
        
        Pi::model('statistics', $module)->increaseVisits($name);
        Pi::model('visit', $module)->addRow($name);
    }
    
    /**
     * Getting articles which are mostly visit.
     * 
     * @param int     $limit   Article limitation
     * @param string  $module
     * @return array 
     */
    public static function getTopVisits($limit, $module = null)
    {
        $module = $module ?: Pi::service('module')->current();
        $model  = Pi::model('statistics', $module);
        $select = $model->select()
                        ->limit($limit)
                        ->order('visits DESC');
        $rowset = $model->selectWith($select);
        
        $result = array();
        foreach ($rowset as $row) {
            unset($row->id);
            $result[$row->article] = $row->toArray();
        }
        
        return $result;
    }
    
    /**
     * Getting article total count in period.
     * 
     * @param int     $dateFrom  
     * @param int     $dateTo
     * @param string  $module
     * @return int 
     */
    public static function getTotalInPeriod($dateFrom, $dateTo, $module = null)
    {
        $result = 0;
        $where  = array();
        $module = $module ?: Pi::service('module')->current();

        if (!empty($dateFrom)) {
            $where['time_submit >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['time_submit <= ?'] = $dateTo;
        }
        $where['status'] = Article::FIELD_STATUS_PUBLISHED;
        $where['active'] = 1;

        $modelArticle   = Pi::model('article', $module);
        $select         = $modelArticle->select()
            ->columns(array('total' => new Expression('count(id)')))
            ->where($where);
        $resultset = $modelArticle->selectWith($select);

        $result = $resultset->current()->total;

        return $result;
    }

    /**
     * Getting article total count in period.
     * 
     * @param int     $days
     * @param string  $module
     * @return int 
     */
    public static function getTotalRecently($days = null, $module = null)
    {
        $dateFrom = !is_null($days) ? strtotime(sprintf('-%d day', $days)) : 0;
        $dateTo   = time();

        return self::getTotalInPeriod($dateFrom, $dateTo, $module);
    }

    /**
     * Getting total article counts group by category.
     * 
     * @param int     $dateFrom
     * @param int     $dateTo
     * @param string  $module
     * @return int 
     */
    public static function getTotalInPeriodByCategory($dateFrom, $dateTo, $module)
    {
        $result = $where = array();
        $module = $module ?: Pi::service('module')->current();

        if (!empty($dateFrom)) {
            $where['time_submit >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['time_submit <= ?'] = $dateTo;
        }

        $result = Cache::getCategoryList();

        foreach ($result as &$val) {
            $val['total'] = 0;
        }

        $modelArticle = Pi::model('article', $module);
        $select = $modelArticle->select()
            ->columns(array('category', 'total' => new Expression('count(category)')))
            ->where($where)
            ->group('category');
        $groupResultset = $modelArticle->selectWith($select)->toArray();

        foreach ($groupResultset as $row) {
            $result[$row['category']]['total'] = $row['total'];
        }

        return $result;
    }

    /**
     * Getting total article counts group by category.
     * 
     * @param int     $days
     * @param string  $module
     * @return int 
     */
    public static function getTotalRecentlyByCategory($days = null, $module = null)
    {
        $dateFrom = !is_null($days) ? strtotime(sprintf('-%d day', $days)) : 0;
        $dateTo   = time();

        return self::getTotalInPeriodByCategory($dateFrom, $dateTo, $module);
    }

    /**
     * Getting submitter count in period.
     * 
     * @param int     $dateFrom
     * @param int     $dateTo
     * @param int     $limit
     * @param string  $module
     * @return int 
     */
    public static function getSubmittersInPeriod($dateFrom, $dateTo, $limit = null, $module = null)
    {
        $result = $userIds = $users = $where = array();
        $module = $module ?: self::$module;

        if (!empty($dateFrom)) {
            $where['time_submit >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['time_submit <= ?'] = $dateTo;
        }
        $where['status'] = Article::FIELD_STATUS_PUBLISHED;
        $where['active'] = 1;

        $modelArticle = Pi::model('article', $module);
        $modelUser    = Pi::model('user');

        $select = $modelArticle->select()
            ->columns(array('uid', 'total' => new Expression('count(uid)')))
            ->where($where)
            ->group('uid')
            ->order('total DESC');

        if ($limit) {
            $select->limit($limit);
        }

        $result = $modelArticle->selectWith($select)->toArray();

        foreach ($result as $row) {
            if (!empty($row['uid'])) {
                $userIds[] = $row['uid'];
            }
        }
        $userIds = array_unique($userIds);

        if (!empty($userIds)) {
            $resultsetUser = $modelUser->find($userIds);
            foreach ($resultsetUser as $row) {
                $users[$row->id] = array(
                    'name' => $row->identity,
                );
            }
            unset($resultsetUser);
        }

        foreach ($result as &$row) {
            if (!empty($users[$row['uid']])) {
                $row['identity'] = $users[$row['uid']]['name'];
            }
        }

        return $result;
    }

    /**
     * Getting submitter count in period.
     * 
     * @param int     $days
     * @param int     $limit
     * @param string  $module
     * @return int 
     */
    public static function getSubmittersRecently($days = null, $limit = null, $module = null)
    {
        $dateFrom = !is_null($days) ? strtotime(sprintf('-%d day', $days)) : 0;
        $dateTo   = time();

        return self::getSubmittersInPeriod($dateFrom, $dateTo, $limit, $module);
    }
}
