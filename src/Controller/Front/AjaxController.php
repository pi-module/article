<?php
/**
 * Article module ajax controller
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

namespace Module\Article\Controller\Front;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Module\Article\Model\Article;
use Module\Article\Model\Draft;
use Module\Article\Model\Asset;
use Module\Article\Upload;
use Module\Article\Service;
use Module\Article\Controller\Admin\PermController;

class AjaxController extends ActionController
{
    const AJAX_RESULT_TRUE = 1;
    const AJAX_RESULT_FALSE = 0;
   
    public function indexAction()
    {
        //
    }

    public function getFuzzyAuthorAction()
    {
        Pi::service('log')->active(false);
        $resultset = $result = array();

        $name  = Service::getParam($this, 'name', '');
        $limit = Service::getParam($this, 'limit', 10);

        $model = $this->getModel('author');
        $select = $model->select()->columns(array('id', 'name', 'photo'))->order('name ASC')->limit($limit);
        if ($name) {
            $select->where->like('name', "{$name}%");
        }

        $result = $model->selectWith($select)->toArray();

        foreach ($result as $val) {
            $resultset[] = array(
                'id'    => $val['id'],
                'name'  => $val['name'],
                'photo' => $val['photo'],
            );
        }

        return array(
            'status'    => self::AJAX_RESULT_TRUE,
            'message'   => 'ok',
            'data'      => $resultset,
        );
    }

    public function getFuzzyUserAction()
    {
        Pi::service('log')->active(false);
        $resultset = $result = array();

        $name  = Service::getParam($this, 'name', '');
        $limit = Service::getParam($this, 'limit', 10);

        $model = Pi::model('user');
        $select = $model->select()->columns(array('id', 'name' => 'identity'))->order('identity ASC')->limit($limit);
        if ($name) {
            $select->where->like('identity', "{$name}%");
        }

        $result = $model->selectWith($select)->toArray();

        foreach ($result as $val) {
            $resultset[] = array(
                'id'   => $val['id'],
                'name' => $val['name'],
            );
        }

        return array(
            'status'    => self::AJAX_RESULT_TRUE,
            'message'   => 'ok',
            'data'      => $resultset,
        );
    }

    public function getFuzzyTagAction()
    {
        Pi::service('log')->active(false);
        $resultset = array();

        $name  = Service::getParam($this, 'name', '');
        $limit = Service::getParam($this, 'limit', 10);
        $limit = $limit > 100 ? 100 : $limit;
        $module = $this->getModule();

        if ($name && $this->config('enable_tag')) {
            $resultset = Pi::service('tag')->match($name, $limit, $module);
        }

        return array(
            'status'    => self::AJAX_RESULT_TRUE,
            'message'   => 'ok',
            'data'      => $resultset,
        );
    }

    public function getFuzzyArticleAction()
    {
        Pi::service('log')->active(false);
        $articles   = array();
        $pageCount  = $total = 0;
        $module     = $this->getModule();
        $where      = array('status' => Article::FIELD_STATUS_PUBLISHED);

        $keyword = Service::getParam($this, 'keyword', '');
        $type    = Service::getParam($this, 'type', 'title');
        $limit   = Service::getParam($this, 'limit', 10);
        $limit   = $limit > 100 ? 100 : $limit;
        $page    = Service::getParam($this, 'page', 1);
        $exclude = Service::getParam($this, 'exclude', 0);
        $offset  = $limit * ($page - 1);

        $articleModel   = $this->getModel('article');

        if (strcasecmp('tag', $type) == 0) {
            if ($keyword) {
                $total     = Pi::service('tag')->getCount($module, $keyword);
                $pageCount = ceil($total / $limit);

                // Get article ids
                $articleIds = Pi::service('tag')->getList($module, $keyword, null, $limit, $offset);
                if ($articleIds) {
                    $where['id'] = $articleIds;
                    $articles    = array_flip($articleIds);

                    // Get articles
                    $resultsetArticle = Service::getArticlePage($where, 1, $limit, null, null, $module);

                    foreach ($resultsetArticle as $key => $val) {
                        $articles[$key] = $val;
                    }

                    $articles = array_filter($articles, function($var) {
                        return is_array($var);
                    });
                }
            }
        } else {
            // Get resultset
            if ($keyword) {
                $where['subject like ?'] = sprintf('%%%s%%', $keyword);
            }

            $articles = Service::getArticlePage($where, $page, $limit, null, null, $module);

            // Get total
            $total      = $articleModel->getSearchRowsCount($where);
            $pageCount  = ceil($total / $limit);
        }

        foreach ($articles as $key => &$article) {
            if ($exclude && $exclude == $key) {
                unset($articles[$key]);
            }
            $article['time_publish_text'] = date('Y-m-d', $article['time_publish']);
        }

        return array(
            'status'    => self::AJAX_RESULT_TRUE,
            'message'   => 'ok',
            'data'      => array_values($articles),
            'paginator' => array(
                'currentPage' => $page,
                'pageCount'   => $pageCount,
                'keyword'     => $keyword,
                'type'        => $type,
                'limit'       => $limit,
                'totalCount'  => $total,
            ),
        );
    }

    public function removeCategoryImageAction()
    {
        Pi::service('log')->active(false);
        $id           = Service::getParam($this, 'id', 0);
        $fakeId       = Service::getParam($this, 'fake_id', 0);
        $affectedRows = 0;
        $module       = $this->getModule();

        if ($id) {
            $rowCategory = $this->getModel('category')->find($id);

            if ($rowCategory && $rowCategory->image) {
                // Delete image
                unlink(Pi::path($rowCategory->image));

                // Update db
                $rowCategory->image = '';
                $affectedRows       = $rowCategory->save();
            }
        } else if ($fakeId) {
            $session = Upload::getUploadSession($module, 'category');

            if (isset($session->$fakeId)) {
                $uploadInfo = isset($session->$id) ? $session->$id : $session->$fakeId;

                unlink(Pi::path($uploadInfo['tmp_name']));

                unset($session->$id);
                unset($session->$fakeId);
            }
        }

        return array(
            'status'    => $affectedRows ? self::AJAX_RESULT_TRUE : self::AJAX_RESULT_FALSE,
            'message'   => 'ok',
        );
    }

    public function removeFeatureImageAction()
    {
        Pi::service('log')->active(false);
        $id           = Service::getParam($this, 'id', 0);
        $fakeId       = Service::getParam($this, 'fake_id', 0);
        $affectedRows = 0;
        $module       = $this->getModule();

        if ($id) {
            $rowDraft = $this->getModel('draft')->find($id);

            if ($rowDraft && $rowDraft->image) {
    //            $fileName = Upload::getTargetFileName($id, 'draft', $this->getModule());

                if ($rowDraft->article) {
                    $rowArticle    = $this->getModel('article')->find($rowDraft->article);
                    if ($rowArticle && $rowArticle->image != $rowDraft->image) {
                        // Delete file
                        unlink(Pi::path($rowDraft->image));
                        unlink(Pi::path(Upload::getThumbFromOriginal($rowDraft->image)));
                    }
                } else {
                    unlink(Pi::path($rowDraft->image));
                    unlink(Pi::path(Upload::getThumbFromOriginal($rowDraft->image)));
                }

                // Update db
                $rowDraft->image = '';
                $affectedRows    = $rowDraft->save();
            }
        } else if ($fakeId) {
            $session = Upload::getUploadSession($module);

            if (isset($session->$fakeId)) {
                $uploadInfo = $session->$fakeId;

                $affectedRows = unlink(Pi::path($uploadInfo['tmp_name']));
                unlink(Pi::path(Upload::getThumbFromOriginal($uploadInfo['tmp_name'])));

                unset($session->$id);
                unset($session->$fakeId);
            }
        }

        return array(
            'status'    => $affectedRows ? self::AJAX_RESULT_TRUE : self::AJAX_RESULT_FALSE,
            'message'   => 'ok',
        );
    }

    public function removeAssetAction()
    {
        Pi::service('log')->active(false);
        $id    = Service::getParam($this, 'id', 0);

        $row = $this->getModel('draft_asset')->find($id);

        if (!$row->published) {
            unlink(Pi::path($row->path));

            if (Asset::FIELD_TYPE_IMAGE == $row->type) {
                unlink(Pi::path(Upload::getThumbFromOriginal($row->path)));
            }
        }
        $row->delete();

        return array(
            'status'    => self::AJAX_RESULT_TRUE,
            'message'   => 'ok',
        );
    }

    public function checkArticleExistsAction()
    {
        Pi::service('log')->active(false);
        $subject = trim(Service::getParam($this, 'subject', ''));
        $id      = Service::getParam($this, 'id', null);
        $result  = false;

        if ($subject) {
            $articleModel = $this->getModel('article');
            $result = $articleModel->checkSubjectExists($subject, $id);
        }

        return array(
            'status'    => $result ? self::AJAX_RESULT_FALSE : self::AJAX_RESULT_TRUE,
            'message'   => $result ? __('Subject is used by another article.') : __('ok'),
        );
    }
    
    /**#@+
    * Added by Zongshu Lin
    */
    public function permAction()
    {
        $channel = $this->params('channel');
        $role    = $this->params('role', PermController::PERM_EDITOR);
        
        $channels = Pi::service('api')->channel(array('role', 'getListByRole'), $role);
        $result   = array(
            'status'   => true,
        );
        if (!in_array($channel, array_keys($channels))) {
            $result = array(
                'status'   => false,
                'message'  => 'Current user has no right to operate the given channel!',
            );
        }
        
        return json_encode($result);
    }
    /**#@-*/
}
