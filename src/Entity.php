<?php
/**
 * Article module article api
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
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article;

use Pi;
use Zend\Db\Sql\Expression;
use Module\Article\Model\Article;
use Module\Article\Model\Asset;
use Module\Article\Upload;
use Module\Article\Cache;
use Module\Article\Compiled;
use Module\Article\Statistics;
use Module\Article\Service;

/**
 * Public APIs for article module itself 
 */
class Entity
{
    protected static $module = 'article';

    /**
     * Getting recently visited articles.
     * 
     * @param int     $dateFrom
     * @param int     $dateTo
     * @param int     $limit
     * @param int     $category
     * @param string  $module
     * @return array 
     */
    public static function getVisitsInPeriod($dateFrom, $dateTo, $limit = null, $category = null, $module = null)
    {
        $result = $where = array();
        $module = $module ?: Pi::service('module')->current();

        $modelArticle   = Pi::model('article', $module);
        $modelCategory  = Pi::model('category', $module);
        $modelVisit     = Pi::model('visit', $module);

        if (!empty($dateFrom)) {
            $where['time >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['time <= ?'] = $dateTo;
        }

        if ($category && $category > 1) {
            $categoryIds = $modelCategory->getDescendantIds($category);

            if ($categoryIds) {
                $where['a.category'] = $categoryIds;
            }
        }

        $where['status'] = Article::FIELD_STATUS_PUBLISHED;
        $where['active'] = 1;

        $select = $modelVisit->select()
            ->columns(array('article', 'total' => new Expression('count(article)')))
            ->join(
                array('a' => $modelArticle->getTable()),
                sprintf('%s.article = a.id', $modelVisit->getTable()),
                array()
            )
            ->where($where)
            ->group(array(sprintf('%s.article', $modelVisit->getTable())))
            ->order('total DESC');

        if ($limit) {
            $select->limit($limit);
        }

        $resultsetVisit = $modelVisit->selectWith($select)->toArray();
        foreach ($resultsetVisit as $row) {
            $result[$row['article']] = $row;
        }

        $articleIds = array_keys($result);
        if ($articleIds) {
            $resultsetArticle = self::getAvailableArticlePage(array('id' => $articleIds), 1, $limit, null, '', $module);

            foreach ($result as $key => &$row) {
                if (isset($resultsetArticle[$key])) {
                    $row = $row + $resultsetArticle[$key];
                }
            }
        }

        return $result;
    }

    /**
     * Getting recently visited articles.
     * 
     * @param int     $days
     * @param int     $limit
     * @param int     $category
     * @param string  $module
     * @return array 
     */
    public static function getVisitsRecently($days, $limit = null, $category = null, $module = null)
    {
        $dateTo   = time();
        $dateFrom = $dateTo - 24 * 3600 * $days;

        return self::getVisitsInPeriod($dateFrom, $dateTo, $limit, $category, $module);
    }

    /**
     * Getting total visits of articles.
     * 
     * @param int     $limit
     * @param int     $category
     * @param string  $module
     * @return array 
     */
    public static function getTotalVisits($limit = null, $category = null, $module = null)
    {
        $result = $where = $columns = array();
        $module = $module ?: self::$module;

        $modelCategory  = Pi::model('category', $module);
        if ($category && $category > 1) {
            $categoryIds = $modelCategory->getDescendantIds($category);

            if ($categoryIds) {
                $where['category'] = $categoryIds;
            }
        }
        
        $articles    = Statistics::getTopVisits($limit, $module);
        if (!empty($articles)) {
            $where['id'] = array_keys($articles);
        }
        
        $columns = array(
            'id',
            'article' => 'id',
            'subject',
            'source',
            'image',
            'pages',
            'summary',
            'time_publish',
        );

        $result = self::getAvailableArticlePage($where, 1, $limit, $columns, null, $module);

        return $result;
    }

    public static function getLatest($limit = null, $category = null, $channel = null, $module = null)
    {
        $result = $where = $columns = array();
        $module = $module ?: self::$module;

        if ($channel) {
            $where['channel'] = (int) $channel;
        }

        $modelCategory  = Pi::model('category', $module);
        if ($category && $category > 1) {
            $categoryIds = $modelCategory->getDescendantIds($category);

            if ($categoryIds) {
                $where['category'] = $categoryIds;
            }
        }

        $columns = array(
            'id',
            'article' => 'id',
            'total'   => 'visits',
            'subject',
            'source',
            'image',
            'pages',
            'slug',
            'summary',
            'time_publish',
        );

        $result = self::getAvailableArticlePage($where, 1, $limit, $columns, null, $module);

        return $result;
    }

    /**
     * Getting published article details
     * 
     * @param array   $where
     * @param int     $page
     * @param int     $limit
     * @param array   $columns
     * @param string  $order
     * @param string  $module
     * @return array 
     */
    public static function getArticlePage($where, $page, $limit, $columns = null, $order = null, $module = null)
    {
        $offset = ($limit && $page) ? $limit * ($page - 1) : null;

        $module = $module ?: Pi::service('module')->current();
        $config = Pi::service('module')->config('', $module);
        $articleIds = $userIds = $authorIds = $categoryIds = array();
        $categories = $authors = $users = $tags = $urls = array();

        $modelArticle  = Pi::model('article', $module);
        $modelUser     = Pi::model('user');
        $modelAuthor   = Pi::model('author', $module);

        $resultset = $modelArticle->getSearchRows($where, $limit, $offset, $columns, $order);

        if ($resultset) {
            foreach ($resultset as $row) {
                $articleIds[] = $row['id'];

                if (!empty($row['author'])) {
                    $authorIds[] = $row['author'];
                }

                if (!empty($row['uid'])) {
                    $userIds[] = $row['uid'];
                }
            }
            $authorIds = array_unique($authorIds);
            $userIds   = array_unique($userIds);
            
            // Getting statistics data
            $modelStatis = Pi::model('statistics', $module);
            $rowStatis   = $modelStatis->select(array('article' => $articleIds));
            $statis      = array();
            foreach ($rowStatis as $item) {
                $temp = $item->toArray();
                unset($temp['id']);
                unset($temp['article']);
                $statis[$item->article] = $temp;
            }
            
            // Getting extended data
            $modelExtended = Pi::model('extended', $module);
            $rowExtended   = $modelExtended->select(array('article' => $articleIds));
            $extended      = array();
            foreach ($rowExtended as $item) {
                $temp = $item->toArray();
                unset($temp['id']);
                unset($temp['article']);
                $extended[$item->article] = $temp;
            }

            $categories = Cache::getCategoryList();

            if (!empty($authorIds) && (empty($columns) || in_array('author', $columns))) {
                $resultsetAuthor = $modelAuthor->find($authorIds);
                foreach ($resultsetAuthor as $row) {
                    $authors[$row->id] = array(
                        'name' => $row->name,
                    );
                }
                unset($resultsetAuthor);
            }

            if (!empty($userIds) && (empty($columns) || in_array('uid', $columns))) {
                $resultsetUser = $modelUser->find($userIds);
                foreach ($resultsetUser as $row) {
                    $users[$row->id] = array(
                        'name' => $row->identity,
                    );
                }
                unset($resultsetUser);
            }

            if (!empty($articleIds)) {
                if ((empty($columns) || in_array('tag', $columns)) && $config['enable_tag']) {
                    $tags = Pi::service('api')->tag->multiple($module, $articleIds);
                }
            }

            foreach ($resultset as &$row) {
                if (empty($columns) || in_array('category', $columns)) {
                    if (!empty($categories[$row['category']])) {
                        $row['category_title'] = $categories[$row['category']]['title'];
                        $row['category_slug']  = $categories[$row['category']]['slug'];
                    }
                }

                if (empty($columns) || in_array('uid', $columns)) {
                    if (!empty($users[$row['uid']])) {
                        $row['user_name'] = $users[$row['uid']]['name'];
                    }
                }

                if (empty($columns) || in_array('author', $columns)) {
                    if (!empty($authors[$row['author']])) {
                        $row['author_name'] = $authors[$row['author']]['name'];
                    }
                }

                if (empty($columns) || in_array('image', $columns)) {
                    if ($row['image']) {
                        $row['thumb'] = Upload::getThumbFromOriginal($row['image']);
                    }
                }

                if ((empty($columns) || in_array('tag', $columns)) && $config['enable_tag']) {
                    if (!empty($tags[$row['id']])) {
                        $row['tag'] = $tags[$row['id']];
                    }
                }

                if (empty($columns) || in_array('subject', $columns)) {
                    $route      = $module . '-' . Service::getRouteName();
                    $row['url'] = Pi::engine()->application()
                                              ->getRouter()
                                              ->assemble(array(
                                                  'time'       => date('Ymd', $row['time_publish']),
                                                  'id'         => $row['id'],
                                              ), array('name' => $route));
                }
                
                if (isset($statis[$row['id']])) {
                    $row = array_merge($row, $statis[$row['id']]);
                }
                if (isset($extended[$row['id']])) {
                    $row = array_merge($row, $extended[$row['id']]);
                }
            }
        }

        return $resultset;
    }

    /**
     * Getting available articles which are published and active.
     * 
     * @param array   $where
     * @param int     $page
     * @param int     $limit
     * @param array   $columns
     * @param string  $order
     * @param string  $module
     * @return array 
     */
    public static function getAvailableArticlePage($where, $page, $limit, $columns = null, $order = null, $module = null)
    {
        $defaultWhere = array(
            'time_publish <= ?' => time(),
            'status'            => Article::FIELD_STATUS_PUBLISHED,
            'active'            => 1,
        );
        $where = $where ? array_merge($where, $defaultWhere) : $defaultWhere;

        return self::getArticlePage($where, $page, $limit, $columns, $order, $module);
    }

    /**
     * Getting published article details.
     * 
     * @param int  $id  Article ID
     * @return array 
     */
    public static function getEntity($id)
    {
        $result = array();
        $module = Pi::service('module')->current();
        $config = Pi::service('module')->config('', $module);

        $row = Pi::model('article', $module)->find($id);
        if (empty($row->id)) {
            return array();
        }
        $subject = $subtitle = $content = '';
        if ($row->markup) {
            $subject    = Pi::service('markup')->render($row->subject, 'html', $row->markup);
            $subtitle   = Pi::service('markup')->render($row->subtitle, 'html', $row->markup);
        } else {
            $subject    = Pi::service('markup')->render($row->subject, 'html');
            $subtitle   = Pi::service('markup')->render($row->subtitle, 'html');
        }
        $content = Compiled::getContent($row->id, 'html');

        $result  = array(
            'title'         => $subject,
            'content'       => Service::breakPage($content),
            'subtitle'      => $subtitle,
            'source'        => $row->source,
            'pages'         => $row->pages,
            'time_publish'  => $row->time_publish,
            'active'        => $row->active,
            'visits'        => '',
            'slug'          => '',
            'seo'           => array(),
            'author'        => array(),
            'attachment'    => array(),
            'tag'           => array(),
            'related'       => array(),
        );

        // Get author
        if ($row->author) {
            $author = Pi::model('author', $module)->find($row->author);

            if ($author) {
                $result['author'] = $author->toArray();
                if (empty($result['author']['photo'])) {
                    $result['author']['photo'] = Pi::service('asset')->getModuleAsset($config['default_author_photo'], $module);
                }
            }
        }

        // Get attachments
        /*$resultsetAsset = Pi::model('asset', $module)->select(array(
            'article'   => $id,
            'type'      => Asset::FIELD_TYPE_ATTACHMENT,
        ));

        foreach ($resultsetAsset as $attachment) {
            $result['attachment'][] = array(
                'original_name' => $attachment->original_name,
                'extension'     => $attachment->extension,
                'size'          => $attachment->size,
                'url'           => Pi::engine()->application()->getRouter()->assemble(
                    array(
                        'module'     => $this->getModule(),
                        'controller' => 'download',
                        'action'     => 'attachment',
                        'name'       => $attachment->name,
                    ),
                    array(
                        'name'       => 'default',
                    )
                ),
            );
        }*/

        // Get tag
        if ($config['enable_tag']) {
            $result['tag'] = Pi::service('tag')->get($module, $id);
        }

        // Get related articles
        $relatedIds = $related = array();  
        $relatedIds = Pi::model('related', $module)->getRelated($id);

        if ($relatedIds) {
            $related = array_flip($relatedIds);
            $where   = array('id' => $relatedIds);
            $columns = array('id', 'subject');

            $resultsetRelated = self::getArticlePage($where, 1, null, $columns, null, $module);

            foreach ($resultsetRelated as $key => $val) {
                if (array_key_exists($key, $related)) {
                    $related[$key] = $val;
                }
            }

            $result['related'] = array_filter($related, function($var) {
                return is_array($var);
            });
        }

        // Getting seo
        $modelExtended  = Pi::model('extended', $module);
        $rowExtended    = $modelExtended->find($row->id, 'article');
        $result['slug'] = $rowExtended->slug;
        $result['seo']  = array(
            'title'        => $rowExtended->seo_title,
            'keywords'     => $rowExtended->seo_keywords,
            'description'  => $rowExtended->seo_description,
        );
        
        // Getting statistics data
        $modelStatis    = Pi::model('statistics', $module);
        $rowStatis      = $modelStatis->find($row->id, 'article');
        $result['visits'] = $rowStatis->visits;

        return $result;
    }
}
