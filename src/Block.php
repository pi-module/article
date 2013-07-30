<?php
/**
 * Article module block renderer
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
use Module\System\Form\LoginForm;
use Module\Article\Service;
use Module\Article\Model\Article;
use Module\Article\Topic;

/**
 * Public class for rendering block content 
 */
class Block
{
    /**
     * Listing all categories and its children
     * 
     * @param array   $options  Block parameters
     * @param string  $module   Module name
     * @return boolean 
     */
    public static function allCategories($options = array(), $module = null)
    {
        if (empty($module)) {
            return false;
        }
        
        $maxTopCount = ($options['top-category'] > 8 or $options['top-category'] <= 0) ? 6 : $options['top-category'];
        $maxSubCount = ($options['sub-category'] > 5 or $options['sub-category'] <= 0) ? 3 : $options['sub-category'];
        $route       = $module . '-' . Service::getRouteName();
        $defaultUrl  = Pi::engine()->application()
                                   ->getRouter()
                                   ->assemble(array(
                                       'category'  => 'all',
                                   ), array('name' => $route));
        
        $model       = Pi::model('category', $module);
        $root        = $model->find('root', 'name');
        $rowset      = $model->enumerate($root->id);
        $categories  = array_shift($rowset);
        
        $allItems    = array();
        $topCount    = 0;
        foreach ($categories['child'] as $category) {
            if ($topCount > $maxTopCount) {
                break;
            }
            $id = $category['id'];
            $allItems[$id] = array(
                'title'     => $category['title'],
                'url'       => Pi::engine()->application()
                                           ->getRouter()
                                           ->assemble(array(
                                               'category'  => $category['slug'] ?: $category['id'],
                                           ), array('name' => $route)),
            );
            $topCount++;
            
            // Fetching sub-category
            $subCount    = 0;
            foreach ($category['child'] as $child) {
                if ($subCount > $maxSubCount) {
                    break;
                }
                $allItems[$id]['child'][$child['id']] = array(
                    'title'    => $child['title'],
                    'url'      => Pi::engine()->application()
                                              ->getRouter()
                                              ->assemble(array(
                                                  'category'  => $child['slug'] ?: $child['id'],
                                              ), array('name' => $route)),
                );
                $subCount++;
            }
            for ($i = count($allItems[$id]['child']); $i < $maxSubCount; $i++) {
                $allItems[$id]['child'][] = array(
                    'title'    => empty($options['default-category']) ? __('None') : $options['default-category'],
                    'url'      => $defaultUrl,
                );
            }
        }
        for ($j = count($allItems); $j < $maxTopCount; $j++) {
            $child = array();
            for ($i = 0; $i < $maxSubCount; $i++) {
                $child[] = array(
                    'title'    => empty($options['default-category']) ? __('None') : $options['default-category'],
                    'url'      => $defaultUrl,
                );
            }
            $allItems[] = array(
                'title'    => empty($options['default-category']) ? __('None') : $options['default-category'],
                'url'      => $defaultUrl,
                'child'    => $child,
            );
            
        }
        
        return $allItems;
    }
    
    /**
     * Listing newest published articles.
     * 
     * @param array   $options
     * @param string  $module
     * @return boolean 
     */
    public static function newestPublishedArticles($options = array(), $module = null)
    {
        if (empty($module)) {
            return false;
        }
        
        $params   = Pi::engine()->application()->getRouteMatch()->getParams();
        
        $config   = Pi::service('module')->config('', $module);
        $image    = Pi::service('asset')->getModuleAsset($config['default_feature_image'], $module);
        
        $category = $options['category'] ? $options['category'] : (isset($params['category']) ? $params['category'] : 0);
        $topic    = $options['topic'] ? $options['topic'] : (isset($params['topic']) ? $params['topic'] : 0);
        $limit    = ($options['list-count'] <= 0) ? 10 : $options['list-count'];
        $page     = 1;
        $order    = 'time_publish DESC';
        $columns  = array('subject', 'summary', 'time_publish', 'image');
        $where    = array();
        if (!empty($category)) {
            $where['category'] = $category;
        }
        if (!empty($topic)) {
            $where['topic'] = $topic;
            $articles = Topic::getTopicArticles($where, $page, $limit, $columns, $order, $module);
        } else {
            $articles = Service::getAvailableArticlePage($where, $page, $limit, $columns, $order, $module);
        }
        
        if ($options['max_subject_length'] > 0) {
            foreach ($articles as &$article) {
                $article['subject'] = substr($article['subject'], 0, $options['max_subject_length']);
                $article['image']   = $article['image'] ?: $image;
            }
        }
        
        return array(
            'articles'  => $articles,
            'target'    => $options['target'],
            'style'     => $options['block-style'],
        );
    }
    
    /**
     * Listing articles defined by user.
     * 
     * @param array   $options
     * @param string  $module
     * @return boolean 
     */
    public static function customArticleList($options = array(), $module = null)
    {
        if (!$module) {
            return false;
        }
        
        $config   = Pi::service('module')->config('', $module);
        $image    = Pi::service('asset')->getModuleAsset($config['default_feature_image'], $module);
        
        $columns  = array('subject', 'summary', 'time_publish', 'image');
        $ids      = explode(',', $options['articles']);
        foreach ($ids as &$id) {
            $id = trim($id);
        }
        $where    = array('id' => $ids);
        $articles = Service::getAvailableArticlePage($where, 1, 10, $columns, null, $module);
        
        if ($options['max_subject_length'] > 0) {
            foreach ($articles as &$article) {
                $article['subject'] = substr($article['subject'], 0, $options['max_subject_length']);
                $article['image']   = $article['image'] ?: $image;
            }
        }
        
        return array(
            'articles'  => $articles,
            'target'    => $options['target'],
            'style'     => $options['block-style'],
        );
    }
    
    /**
     * Exporting a search form.
     * 
     * @param array   $options
     * @param string  $module
     * @return boolean 
     */
    public static function simpleSearch($options = array(), $module = null)
    {
        if (!$module) {
            return false;
        }
        
        return array(
            'url' => Pi::engine()->application()->getRouter()->assemble(
                array(
                    'module'     => $module,
                    'controller' => 'search',
                    'action'     => 'simple',
                ),
                array(
                    'name'       => 'default',
                )
            ),
        );
    }

    /**
     * Statistic all article count according to submitter.
     * 
     * @param array   $options
     * @param string  $module
     * @return boolean 
     */
    public static function submitterStatistics($options = array(), $module = null)
    {
        if (!$module) {
            return false;
        }
        
        $limit     = ($options['list-count'] <= 0) ? 10 : $options['list-count'];
        $time      = time();
        $today     = strtotime(date('Y-m-d', $time));
        $tomorrow  = $today + 24 * 3600;
        $week      = $tomorrow - 24 * 3600 * 7;
        $month     = $tomorrow - 24 * 3600 * 30;
        $daySets   = Service::getSubmittersInPeriod($today, $tomorrow, $limit, $module);
        $weekSets  = Service::getSubmittersInPeriod($week, $tomorrow, $limit, $module);
        $monthSets = Service::getSubmittersInPeriod($month, $tomorrow, $limit, $module);
        $historySets   = Service::getSubmittersInPeriod(0, $tomorrow, $limit, $module);
        
        return array(
            'day'     => $daySets,
            'week'    => $weekSets,
            'month'   => $monthSets,
            'history' => $historySets,
        );
    }
    
    /**
     * Listing newest topics.
     * 
     * @param array   $options
     * @param string  $module
     * @return boolean 
     */
    public static function newestTopic($options = array(), $module = null)
    {
        if (!$module) {
            return false;
        }
        
        $limit  = ($options['list-count'] <= 0) ? 10 : $options['list-count'];
        $order  = 'id DESC';
        $topics = Topic::getTopics(array(), 1, $limit, null, $order, $module);
        
        return $topics;
    }

    public static function topNByVisits($options = array(), $module = null)
    {
        if (!$module) {
            return false;
        }

        $channel            = isset($options['channel']) ? (int) $options['channel'] : null;
        $category           = isset($options['category']) ? intval($options['category']) : null;
        $limit              = isset($options['limit']) ? intval($options['limit']) : 10;
        $target             = isset($options['target']) ?: '_blank';
        $max_subject_length = isset($options['max_subject_length']) ? intval($options['max_subject_length']) : 0;

        $articles = Service::getTotalVisits($limit, $category, $channel, $module);

        return array(
            'articles'           => $articles,
            'target'             => $target,
            'max_subject_length' => $max_subject_length,
            'options'            => $options,
        );
    }

    /**
     * Listing hot articles by visiting count.
     * 
     * @param array   $options
     * @param string  $module
     * @return boolean 
     */
    public static function hotArticles($options = array(), $module = null)
    {
        if (!$module) {
            return false;
        }
        
        $limit  = isset($options['list-count']) ? intval($options['list-count']) : 10;
        $target = isset($options['target']) ?: '_blank';
        $length = isset($options['max_subject_length']) ? intval($options['max_subject_length']) : 0;
        $day    = $options['day-range'] ? intval($options['day-range']) : 7;

        if ($options['is-topic']) {
            $articles = Topic::getVisitsRecently($day, $limit, null, $module);
        } else {
            $articles = Service::getVisitsRecently($day, $limit, null, $module);
        }

        return array(
            'articles'           => $articles,
            'target'             => $target,
            'max_subject_length' => $length,
            'options'            => $options,
        );
    }
}
