<?php
/**
 * Article module topic api
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
use Module\Article\Service;

/**
 * Public APIs for article module itself 
 */
class Topic
{
    protected static $module = 'article';
    
    /**
     * Getting topic id by passed topic name.
     * 
     * @param string  $name    Topic unique name
     * @param string  $module
     * @return int 
     */
    public static function getTopicId($name, $module = null)
    {
        $module = $module ?: Pi::service('module')->current();
        $topic  = Pi::model('topic', $module)->find($name, 'name');
        
        return $topic->id;
    }

    /**
     * Getting articles belonging to a certain topic by passed condition.
     * 
     * @param array   $where
     * @param int     $page
     * @param int     $limit
     * @param array   $columns
     * @param string  $order
     * @param string  $module
     * @return array 
     */
    public static function getTopicArticles($where, $page, $limit, $columns = null, $order = null, $module = null)
    {
        $module     = $module ?: Pi::service('module')->current();
        $topicId    = is_numeric($where['topic']) ? $where['topic'] : self::getTopicId($where['topic'], $module);
        if (empty($where['topic'])) {
            return array();
        }
        unset($where['topic']);
        
        $modelTopic = Pi::model('article_topic', $module);
        $rowTopic   = $modelTopic->select(array('topic' => $topicId));
        $articleIds = array();
        foreach ($rowTopic as $row) {
            $articleIds[] = $row['article'];
        }
        $where['id'] = $articleIds;
        
        return Service::getAvailableArticlePage($where, $page, $limit, $columns, $order, $module);
    }
    
    /**
     * Getting topic details by passed condition.
     * 
     * @param array   $where
     * @param int     $page
     * @param int     $limit
     * @param array   $columns
     * @param string  $order
     * @param string  $module
     * @return array 
     */
    public static function getTopics($where, $page, $limit, $columns = null, $order = null, $module = null)
    {
        $offset     = ($limit && $page) ? $limit * ($page - 1) : null;
        $where      = empty($where) ? array() : (array) $where;
        $columns    = empty($columns) ? array('*') : $columns;
        $module     = $module ?: Pi::service('module')->current();
        
        $modelTopic = Pi::model('topic', $module);
        $select     = $modelTopic->select()->where($where)->columns($columns);
        if (!empty($page)) {
            $select->offset($offset);
        }
        if (!empty($limit)) {
            $select->limit($limit);
        }
        if (!empty($order)) {
            $select->order($order);
        }
        $rowset = $modelTopic->selectWith($select);
        $topics = array();
        $route  = $module . '-' . Service::getRouteName();
        foreach ($rowset as $row) {
            $id   = $row->id;
            $topics[$id] = $row->toArray();
            $topics[$id]['url'] = Pi::engine()->application()
                                              ->getRouter()
                                              ->assemble(array(
                                                  'topic' => $row->name,
                                              ), array('name' => $route));
        }
        
        return $topics;
    }
}
