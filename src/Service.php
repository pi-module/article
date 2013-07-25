<?php
/**
 * Article module service api
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

namespace Module\Article;

use Pi;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Module\Article\Model\Article;
use Module\Article\Model\Asset;
use Module\Article\Upload;
use Module\Article\Cache;
use Pi\Mvc\Controller\ActionController;
use Module\Article\Controller\Admin\ConfigController as Config;
use Module\Article\Form\DraftEditForm;
use Module\Article\Model\Draft;
use Module\Article\Compiled;

/**
 * Public APIs for article module itself 
 */
class Service
{
    protected static $module = 'article';

    public static function content($arrayOfContentKeys, $arrayOfConditions)
    {
        $result = $data = array();

        $module = isset($arrayOfConditions['module']) ? $arrayOfConditions['module'] : self::$module;
        $ids    = is_scalar($arrayOfConditions['id']) ? array($arrayOfConditions['id']) : $arrayOfConditions['id'];

        $columns = array('id', 'subject', 'summary', 'time_publish', 'author', 'user');
        $articleResultset   = self::getAvailableArticlePage(array('id' => $ids), null, null, $columns, '', $module);

        foreach ($articleResultset as $row) {
            $data = array(
                'title'   => $row['subject'],
                'time'    => $row['time_publish'],
            );
            if (!empty($row['summary'])) {
                $data['summary'] = $row['summary'];
            }
            if(!empty($row['author_name'])) {
                $data['author'] = $row['author_name'];
            }
            if (!empty($row['user_name'])) {
                $data['user'] = $row['user_name'];
            }
            if (!empty($row['url'])) {
                $data['url'] = $row['url'];
            }

            $result[$row['id']] = $data;
        }

        return $result;
    }

    public static function getVisitsInPeriod($dateFrom, $dateTo, $limit = null, $category = null, $channel = null, $module = null)
    {
        $result = $where = array();
        $module = $module ?: self::$module;

        $modelArticle   = Pi::model('article', $module);
        $modelCategory  = Pi::model('category', $module);
        $modelVisit     = Pi::model('visit', $module);

        if (!empty($dateFrom)) {
            $where['date >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['date <= ?'] = $dateTo;
        }

        if ($channel) {
            $where['a.channel'] = (int) $channel;
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
            ->columns(array('article', 'total' => new Expression('sum(count)')))
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
            $resultsetArticle   = self::getAvailableArticlePage(array('id' => $articleIds), 1, $limit, null, '', $module);

            foreach ($result as $key => &$row) {
                if (isset($resultsetArticle[$key])) {
                    $row = $row + $resultsetArticle[$key];
                }
            }
        }

        return $result;
    }

    public static function getVisitsRecently($days, $limit = null, $category = null, $channel = null, $module = null)
    {
        $dateFrom = date('Ymd', strtotime(sprintf('-%d day', $days)));
        $dateTo   = date('Ymd');

        return self::getVisitsInPeriod($dateFrom, $dateTo, $limit, $category, $channel, $module);
    }

    public static function getTotalVisits($limit = null, $category = null, $channel = null, $module = null)
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

        $order = 'total DESC';

        $result = self::getAvailableArticlePage($where, 1, $limit, $columns, $order, $module);

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

    public static function getTotalInPeriod($dateFrom, $dateTo, $module = null)
    {
        $result = 0;
        $where  = array();
        $module = $module ?: self::$module;

        if (!empty($dateFrom)) {
            $where['time_create >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['time_create <= ?'] = $dateTo;
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

    public static function getTotalRecently($days = null, $module = null)
    {
        $dateFrom = !is_null($days) ? strtotime(sprintf('-%d day', $days)) : 0;
        $dateTo   = time();

        return self::getTotalInPeriod($dateFrom, $dateTo, $module);
    }

    public static function getTotalInPeriodByCategory($dateFrom, $dateTo, $module)
    {
        $result = $where = array();
        $module = $module ?: self::$module;

        if (!empty($dateFrom)) {
            $where['time_create >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['time_create <= ?'] = $dateTo;
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

    public static function getTotalRecentlyByCategory($days = null, $module = null)
    {
        $dateFrom = !is_null($days) ? strtotime(sprintf('-%d day', $days)) : 0;
        $dateTo   = time();

        return self::getTotalInPeriodByCategory($dateFrom, $dateTo, $module);
    }

    public static function getSubmittersInPeriod($dateFrom, $dateTo, $limit = null, $module = null)
    {
        $result = $userIds = $users = $where = array();
        $module = $module ?: self::$module;

        if (!empty($dateFrom)) {
            $where['time_create >= ?'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where['time_create <= ?'] = $dateTo;
        }
        $where['status'] = Article::FIELD_STATUS_PUBLISHED;
        $where['active'] = 1;

        $modelArticle = Pi::model('article', $module);
        $modelUser    = Pi::model('user');

        $select = $modelArticle->select()
            ->columns(array('user', 'total' => new Expression('count(user)')))
            ->where($where)
            ->group('user')
            ->order('total DESC');

        if ($limit) {
            $select->limit($limit);
        }

        $result = $modelArticle->selectWith($select)->toArray();

        foreach ($result as $row) {
            if (!empty($row['user'])) {
                $userIds[] = $row['user'];
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
            if (!empty($users[$row['user']])) {
                $row['identity'] = $users[$row['user']]['name'];
            }
        }

        return $result;
    }

    public static function getSubmittersRecently($days = null, $limit = null, $module = null)
    {
        $dateFrom = !is_null($days) ? strtotime(sprintf('-%d day', $days)) : 0;
        $dateTo   = time();

        return self::getSubmittersInPeriod($dateFrom, $dateTo, $limit, $module);
    }
    
    public static function getParam(ActionController $handler = null, $param = null, $default = null)
    {      
        // Route parameter first
        $result = $handler->params()->fromRoute($param);

        // Then query string
        if (is_null($result) || '' === $result) {
            $result = $handler->params()->fromQuery($param);

            // Then post data
            if (is_null($result) || '' === $result) {
                $result = $handler->params()->fromPost($param);

                if (is_null($result) || '' === $result) {
                    $result = $default;
                }
            }
        }

        return $result;
    }

    public static function getRecommended($limit = null, $category = null, $channel = null, $module = null)
    {
        $result = $where = $columns = array();

        $module = $module ?: self::$module;

        // Build where
        if ($channel) {
            $where['channel'] = (int) $channel;
        }

        if ($category && $category > 1) {
            $categoryIds = Pi::model('category', $module)->getDescendantIds($category);

            if ($categoryIds) {
                $where['category'] = $categoryIds;
            }
        }

        $where['recommended'] = 1;

        $columns = array(
            'id',
            'article' => 'id',
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

    public static function getDraftPage($where, $page, $limit, $columns = null, $order = null, $module = null)
    {
        $offset     = ($limit && $page) ? $limit * ($page - 1) : null;

        $module     = $module ?: Pi::service('module')->current();
        $draftIds   = $userIds = $authorIds = $categoryIds = array();
        $categories = $authors = $users = $tags = $urls = array();

        $modelDraft     = Pi::model('draft', $module);
        $modelUser      = Pi::model('user');
        $modelAuthor    = Pi::model('author', $module);

        $resultset      = $modelDraft->getSearchRows($where, $limit, $offset, $columns, $order);

        if ($resultset) {
            foreach ($resultset as $row) {
                $draftIds[] = $row['id'];

                if (!empty($row['author'])) {
                    $authorIds[] = $row['author'];
                }

                if (!empty($row['uid'])) {
                    $userIds[] = $row['uid'];
                }
            }
            $authorIds = array_unique($authorIds);
            $userIds   = array_unique($userIds);

            $categories = Cache::getCategoryList();

            if (!empty($authorIds)) {
                $resultsetAuthor = $modelAuthor->find($authorIds);
                foreach ($resultsetAuthor as $row) {
                    $authors[$row->id] = array(
                        'name' => $row->name,
                    );
                }
                unset($resultsetAuthor);
            }

            if (!empty($userIds)) {
                $resultsetUser = $modelUser->find($userIds);
                foreach ($resultsetUser as $row) {
                    $users[$row->id] = array(
                        'name' => $row->identity,
                    );
                }
                unset($resultsetUser);
            }

            foreach ($resultset as &$row) {
                if (empty($columns) || isset($columns['category'])) {
                    $row['category_title'] = $categories[$row['category']]['title'];
                }

                if (empty($columns) || isset($columns['uid'])) {
                    if (!empty($users[$row['uid']])) {
                        $row['user_name'] = $users[$row['uid']]['name'];
                    }
                }

                if (empty($columns) || isset($columns['author'])) {
                    if (!empty($authors[$row['author']])) {
                        $row['author_name'] = $authors[$row['author']]['name'];
                    }
                }

                if (empty($columns) || isset($columns['image'])) {
                    if ($row['image']) {
                        $row['thumb'] = Upload::getThumbFromOriginal($row['image']);
                    }
                }
            }
        }

        return $resultset;
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
                $statis[$item->article] = $item->visits;
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
                    $url = Pi::engine()->application()
                                              ->getRouter()
                                              ->assemble(array(
                                                  'module'     => $module,
                                                  'controller' => 'article',
                                                  'action'     => 'detail',
                                                  'id'         => $row['id'],
                                              ), array('name' => 'default'));
                    $row['url'] = $url;
                }
                
                $row['visits'] = $statis[$row['id']];
            }
        }

        return $resultset;
    }

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
     * Deleting draft, along with featured image and attachment.
     * 
     * @param array   $ids     Draft ID
     * @param string  $module  Current module name
     * @return int             Affected rows
     */
    public static function deleteDraft($ids, $module = null)
    {
        $affectedRows   = false;
        $module         = $module ?: Pi::service('module')->current();

        $modelDraft     = Pi::model('draft', $module);
        $modelArticle   = Pi::model('article', $module);

        // Delete feature image
        $resultsetFeatureImage = $modelDraft->select(array('id' => $ids));
        foreach ($resultsetFeatureImage as $featureImage) {
            if ($featureImage->article) {
                $rowArticle = $modelArticle->find($featureImage->article);
                if ($featureImage->image && strcmp($featureImage->image, $rowArticle->image) != 0) {
                    unlink(Pi::path($featureImage->image));
                    unlink(Pi::path(Upload::getThumbFromOriginal($featureImage->image)));
                }
            } else if ($featureImage->image) {
                unlink(Pi::path($featureImage->image));
                unlink(Pi::path(Upload::getThumbFromOriginal($featureImage->image)));
            }
        }

        // Delete assets
        /*$modelDraftAsset = Pi::model('draft_asset', $module);
        $resultsetAsset = $modelDraftAsset->select(array(
            'draft'     => $ids,
            'published' => 0,
        ));
        foreach ($resultsetAsset as $asset) {
            unlink(Pi::path($asset->path));

            if (Asset::FIELD_TYPE_IMAGE == $asset->type) {
                unlink(Pi::path(Upload::getThumbFromOriginal($asset->path)));
            }
        }
        $modelDraftAsset->delete(array('draft' => $ids));*/

        // Delete draft
        $affectedRows = $modelDraft->delete(array('id' => $ids));

        return $affectedRows;
    }

    public static function breakPage($content)
    {
        $result = $matches = $row = array();
        $title = $body = '';
        $page = 0;

        $matches = preg_split(Article::PAGE_BREAK_PATTERN, $content, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($matches as $text) {
            if (preg_match(Article::PAGE_BREAK_PATTERN, $text)) {
                if (isset($row['title']) || isset($row['content'])) {
                    $row['page'] = ++$page;
                    $result[] = $row;
                    $row = array();
                }

                $row['title'] = trim(strip_tags($text));
            } else {
                $row['content'] = trim($text);
            }
        }

        if (!empty($row)) {
            $row['page'] = ++$page;
            $result[]    = $row;
        }

        return $result;
    }

    public static function generateArticleSummary($content, $length)
    {
        $result = '';

        // Remove title
        $result = preg_replace(array(Article::PAGE_BREAK_PATTERN, '/&nbsp;/'), '', $content);
        // Strip tags
        $result = preg_replace('/<[^>]*>/', '', $result);
        // Trim blanks
        $result = trim($result);
        // Get first paragraph
//        if (preg_match('|(.+)[\r\n]+|', $result, $matches)) {
//            $result = $matches[1];
//        }
        // Limit length
        $result = mb_substr($result, 0, $length, 'utf-8');

        return $result;
    }

    public static function transformArticleImages($content, $module = null)
    {
        $module = $module ?: self::$module;

        if (preg_match_all(Upload::PATTERN_HTML_IMAGE, $content, $matches)) {
            // Get host uri
            $uris = array();
            $host = Pi::config()->load('host.php');
            foreach ($host['uri'] as $uri) {
                $uris[] = preg_quote($uri, '/');
            }
            $patternUris = sprintf('/(%s)/i', implode('|', $uris));

            // Filter host url
            $urls  = array_flip($matches[1]);
            $index = 0;
            foreach ($urls as $url => $val) {
                if (preg_match($patternUris, $url)) {
                    unset($urls[$url]);
                } else {
                    $dest = Upload::remoteToLocal($url, $module);
                    if (false !== $dest) {
                        $urls[$url] = array(
                            'source_url'    => Pi::url($dest),
                            'thumb_url'     => Pi::url(Upload::getThumbFromOriginal($dest)),
                        );
                        $index     += 1;
                    } else {
                        unset($urls[$url]);
                    }
                }
            }

            // Replace image tags
            $replacement = array();
            foreach ($matches[1] as $key => $url) {
                if (isset($urls[$url])) {
                    $tagImage       = $matches[0][$key];
                    $patternSource  = sprintf('/%s/i', preg_quote($url, '/'));
                    $patternImage   = sprintf('/%s/i', preg_quote($tagImage, '/'));
                    $newTagImage    = preg_replace($patternSource, $urls[$url]['thumb_url'], $tagImage);
                    $replacement[$patternImage] = sprintf(Upload::FORMAT_IMAGE_ANCHOR, $urls[$url]['source_url'], $newTagImage);
                }
            }

            $content = preg_replace(array_keys($replacement), $replacement, $content);
        }

        return $content;
    }

    /**
     * Apply htmlspecialchars() on each value of an array
     *
     * @param mixed $data
     */
    public static function deepHtmlspecialchars($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = static::deepHtmlspecialchars($val);
            }
        } else {
            $data = is_string($data) ? htmlspecialchars($data, ENT_QUOTES, 'utf-8') : $data;
        }

        return $data;
    }
    
    /**
     * Reading configuration of form to display from file define by user
     * 
     * @return array 
     */
    public static function getFormConfig()
    {
        $filename = Pi::path(Config::ELEMENT_EDIT_PATH);
        if (!file_exists($filename)) {
            return false;
        }
        $config   = include $filename;
        
        if (Config::FORM_MODE_CUSTOM != $config['mode']) {
            $config['elements'] = DraftEditForm::getDefaultElements($config['mode']);
        }
        
        return $config;
    }
    
    /**
     * Getting count statistics of draft with different status and published article
     * @param type $from
     * @return type 
     */
    public static function getSummary($from = 'my')
    {
        $module = Pi::service('module')->current();
        
        $result = array(
            'published' => 0,
            'draft'     => 0,
            'pending'   => 0,
            'rejected'  => 0,
        );

        $where['article < ?'] = 1;
        if ('my' == $from) {
            $where['uid'] = Pi::registry('user')->id;
        }
        $modelDraft = Pi::model('draft', $module);
        $select     = $modelDraft->select()
            ->columns(array('status', 'total' => new Expression('count(status)')))
            ->where($where)
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

        $modelArticle = Pi::model('article', $module);
        $where        = array(
            'status' => Article::FIELD_STATUS_PUBLISHED,
        );
        if ('my' == $from) {
            $where['uid'] = Pi::registry('user')->id;
        }
        $select = $modelArticle->select()
                               ->columns(array('total' => new Expression('count(id)')))
                               ->where($where);
        $resultset = $modelArticle->selectWith($select);
        if ($resultset->count()) {
            $result['published'] = $resultset->current()->total;
        }

        return $result;
    }
    
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

            $resultsetRelated = Service::getArticlePage($where, 1, null, $columns, null, $module);

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
