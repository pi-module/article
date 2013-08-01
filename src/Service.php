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
 * @copyright       Copyright (c) Engine http://www.xoopsengine.org
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
use Module\Article\Statistics;

/**
 * Public APIs for article module itself 
 */
class Service
{
    protected static $module = 'article';

    /**
     * Rendering form
     * 
     * @param Pi\Mvc\Controller\ActionController $obj  ActionController instance
     * @param Zend\Form\Form $form     Form instance
     * @param string         $message  Message assign to template
     * @param bool           $isError  Whether is error message
     */
    public static function renderForm($obj, $form, $message = null, $isError = false)
    {
        $params = array('form' => $form);
        if ($isError) {
            $params['error'] = $message;
        } else {
            $params['message'] = $message;
        }
        $obj->view()->assign($params);
    }
    
    /**
     * Assign configuration data to template
     * 
     * @param ActionController  $handler  ActionController instance
     */
    public static function setModuleConfig(ActionController $handler)
    {
        $handler->view()->assign(array(
            'width'            => $handler->config('author_width'),
            'height'           => $handler->config('author_height'),
            'image_extension'  => $handler->config('image_extension'),
            'max_image_size'   => Upload::fromByteString($handler->config('max_image_size')),
            'media_extension'  => $handler->config('media_extension'),
            'max_media_size'   => Upload::fromByteString($handler->config('max_media_size')),
        ));
    }
    
    /**
     * Getting param post by post, get or query.
     * 
     * @param ActionController $handler
     * @param string  $param    Parameter key
     * @param mixed   $default  Default value if parameter is no exists
     * @return mixed 
     */
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
    
    /**
     * Getting draft article details.
     * 
     * @param int  $id  Draft article ID
     * @return array 
     */
    public static function getDraft($id)
    {
        $result = array();
        $module = Pi::service('module')->current();
        $config = Pi::service('module')->config('', $module);

        $row    = Pi::model('draft', $module)->findRow($id, 'id', false);
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
        $content = Compiled::compiled($row->markup, $row->content, 'html');

        $result = array(
            'title'         => $subject,
            'content'       => Service::breakPage($content),
            'slug'          => $row->slug,
            'seo'           => array(
                'title'         => $row->seo_title,
                'keywords'      => $row->seo_keywords,
                'description'   => $row->seo_description,
            ),
            'subtitle'      => $subtitle,
            'source'        => $row->source,
            'pages'         => $row->pages,
            'time_publish'  => $row->time_publish,
            'author'        => array(),
            'attachment'    => array(),
            'tag'           => $row->tag,
            'related'       => array(),
            'category'      => $row->category,
        );

        // Get author
        if ($row->author) {
            $author = Pi::model('author', $module)->find($row->author);

            if ($author) {
                $result['author'] = $author->toArray();
                if (empty($result['author']['photo'])) {
                    $result['author']['photo'] = Pi::service('asset')->getModuleAsset($config['default_author_photo'], $this->module);
                }
            }
        }

        // Get attachments
        /*$resultsetDraftAsset = Pi::model('draft_asset', $module)->select(array(
            'draft' => $id,
            'type'  => Asset::FIELD_TYPE_ATTACHMENT,
        ));

        foreach ($resultsetDraftAsset as $attachment) {
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

        // Get related articles
        $relatedIds = $related = array();
        $relatedIds = $row->related;
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

        if (empty($row->seo_keywords) && $config['seo_keywords']) {
            if ($config['seo_keywords'] == Article::FIELD_SEO_KEYWORDS_TAG) {
                $result['seo']['keywords'] = implode(' ', $result['tag']);
            } else if ($config['seo_keywords'] == Article::FIELD_SEO_KEYWORDS_CATEGORY) {
                $rowCategory = Pi::model('category', $module)->find($row->category);
                $result['seo']['keywords'] = $rowCategory->title;
            }
        }

        if (empty($row->seo_description) && $config['seo_description']) {
            if ($config['seo_description'] == Article::FIELD_SEO_DESCRIPTION_SUMMARY) {
                $result['seo']['description'] = $row->summary;
            }
        }

        return $result;
    }
    
    /**
     * Getting route name.
     * 
     * @return string 
     */
    public static function getRouteName()
    {
        $module      = Pi::service('module')->current();
        $resBasename = \Module\Article\Installer\Resource\Route::RESOURCE_CONFIG_NAME;
        $resFilename = sprintf('var/%s/config/%s', $module, $resBasename);
        $resPath     = Pi::path($resFilename);
        if (!file_exists($resPath)) {
            return 'article';
        }
        
        $configs = include $resPath;
        $class   = '';
        $name    = '';
        foreach ($configs as $key => $config) {
            $class = $config['type'];
            $name  = $key;
            break;
        }
        
        if (!class_exists($class)) {
            return 'article';
        }
        
        return $name;
    }
    
    /**
     * Checking whether a given user is current loged user.
     * 
     * @param int  $uid  User ID
     * @return boolean 
     */
    public static function isMine($uid)
    {
        $user   = Pi::service('user')->getUser();
        if ($uid == $user->account->id) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Changing status number to slug string
     * 
     * @param int  $status
     * @return string 
     */
    public static function getStatusSlug($status)
    {
        $slug = '';
        switch ($status) {
            case Draft::FIELD_STATUS_DRAFT:
                $slug = 'draft';
                break;
            case Draft::FIELD_STATUS_PENDING:
                $slug = 'pending';
                break;
            case Draft::FIELD_STATUS_REJECTED:
                $slug = 'approve';
                break;
            case Article::FIELD_STATUS_PUBLISHED:
                $slug = 'publish';
                break;
            default:
                $slug = 'draft';
                break;
        }
        
        return $slug;
    }
    
    /**
     * Getting user permission according to given category or operation name.
     * The return array has a format such as:
     * array('{Category ID}' => array('{Operation name}' => true));
     * 
     * @param string      $operation  Operation name
     * @param string|int  $category   Category name or ID
     * @param int         $uid
     * @return array|bool
     */
    public static function getPermission($isMine = false, $operation = null, $category = null, $uid = null)
    {
        $rules = array(
            
            3   => array(
                'compose'      => false,
                'approve'      => false,
                'pending-edit' => true,
                'publish-edit' => true,
                'approve-delete' => false,
                'active'         => true,
            ),
            4   => array(
                'compose'      => true,
                'approve'      => true,
                'pending-edit' => true,
                'draft-edit'   => true,
                'publish-edit' => true,
                'approve-delete' => false,
                'publish-delete' => true,
            ),
            6   => array(
                'compose'      => false,
                'pending-edit' => false,
                'publish-edit' => false,
            ),
        );
        
        if ($isMine) {
            $module   = Pi::service('module')->current();
            $category = Pi::model('category', $module)->getList(array('id'));
            $myRules  = array();
            foreach (array_keys($category) as $key) {
                $categoryRule = array();
                if (isset($rules[$key]['compose']) and $rules[$key]['compose']) {
                    $categoryRule = array(
                        'draft-edit'      => true,
                        'draft-delete'    => true,
                        'pending-edit'    => true,
                        'pending-delete'  => true,
                        'rejected-edit'   => true,
                        'rejected-delete' => true,
                    );
                }
                $myRules[$key] = array_merge(isset($rules[$key]) ? $rules[$key] : array(), $categoryRule);
            }
            $rules = $myRules;
        }
        d($rules);
        return $rules;
    }
}
