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
}
