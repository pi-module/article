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
}
