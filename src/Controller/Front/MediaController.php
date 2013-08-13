<?php
/**
 * Article module media controller
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

namespace Module\Article\Controller\Front;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Pi\Paginator\Paginator;
use Module\Article\Form\MediaEditForm;
use Module\Article\Form\MediaEditFilter;
use Module\Article\Form\SimpleSearchForm;
use Module\Article\Upload;
use Module\Article\Service;
use Zend\Db\Sql\Expression;
use Module\Article\Media;
use Pi\File\Transfer\Upload as UploadHandler;

/**
 * Public action controller for operating media
 */
class MediaController extends ActionController
{
    const AJAX_RESULT_TRUE  = true;
    const AJAX_RESULT_FALSE = false;

    /**
     * Getting media form object
     * 
     * @param string $action  Form name
     * @return \Module\Article\Form\MediaEditForm 
     */
    protected function getMediaForm($action = 'add')
    {
        $form = new MediaEditForm();
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => $action)),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }

    /**
     * Saving media information
     * 
     * @param  array    $data  Media information
     * @return boolean
     * @throws \Exception 
     */
    protected function saveMedia($data)
    {
        $module        = $this->getModule();
        $modelMedia    = $this->getModel('media');
        $fakeId        = $image = null;

        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
        }

        $fakeId = Service::getParam($this, 'fake_id', 0);

        unset($data['media']);
        
        // Getting media info
        $session    = Upload::getUploadSession($module, 'media');
        if (isset($session->$id) || ($fakeId && isset($session->$fakeId))) {
            $uploadInfo = isset($session->$id) ? $session->$id : $session->$fakeId;

            if ($uploadInfo) {
                $pathInfo = pathinfo($uploadInfo['tmp_name']);
                if ($pathInfo['extension']) {
                    $data['type'] = $pathInfo['extension'];
                }
                $data['size'] = filesize($uploadInfo['tmp_name']);
                
                // Meta
                $metaColumns = array('w', 'h');
                $meta        = array();
                foreach ($uploadInfo as $key => $info) {
                    if (in_array($key, $metaColumns)) {
                        $meta[$key] = $info;
                    }
                }
                $data['meta'] = json_encode($meta);
            }

            unset($session->$id);
            unset($session->$fakeId);
        }
        
        // Getting user ID
        $user   = Pi::service('user')->getUser();
        $uid    = $user->account->id ?: 0;
        $data['uid'] = $uid;
        
        if (empty($id)) {
            $data['time_upload'] = time();
            $row = $modelMedia->createRow($data);
            $row->save();
            $id = $row->id;
            $rowMedia = $modelMedia->find($id);
        } else {
            $data['time_update'] = time();
            $rowMedia = $modelMedia->find($id);

            if (empty($rowMedia)) {
                return false;
            }

            $rowMedia->assign($data);
            $rowMedia->save();
        }

        // Save image
        if (!empty($uploadInfo)) {
            $fileName = $rowMedia->id;

            if ($pathInfo['extension']) {
                $fileName .= '.' . $pathInfo['extension'];
            }
            $fileName = $pathInfo['dirname'] . '/' . $fileName;

            $rowMedia->url = rename(Pi::path($uploadInfo['tmp_name']), Pi::path($fileName)) ? $fileName : $uploadInfo['tmp_name'];
            $rowMedia->save();
        }

        return $id;
    }
    
    /**
     * Media index page, which will redirect to list page
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('', array(
            'action'    => 'list',
        ));
    }
    
    /**
     * Processing media list
     */
    public function listAction()
    {
        $type    = $this->params('type', '');
        $keyword = $this->params('keyword', '');
        
        $where = array();
        if (!empty($type)) {
            $where['type'] = $type;
        }
        if (!empty($keyword)) {
            $where['title like ?'] = '%' . $keyword . '%';
        }
        
        $model = $this->getModel('media');

        $page  = $this->params('p', 1);
        $page  = $page > 0 ? $page : 1;

        $module = $this->getModule();
        $config = Pi::service('module')->config('', $module);
        $limit  = (int) $config['page_limit_front'] ?: 40;
        $types  = array();
        foreach (explode(',', $config['media_extension']) as $item) {
            $types[$item] = ucfirst($item);
        }
        
        $resultSet = Media::getList($where, $page, $limit, null, null, $module);

        // Total count
        $select = $model->select()->where($where);
        $select->columns(array('count' => new Expression('count(*)')));
        $count  = (int) $model->selectWith($select)->current()->count;

        // Pagination
        $paginator = Paginator::factory($count);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
                'params'    => array(
                    'type'      => $type,
                    'keyword'   => $keyword,
                ),
            ));
        
        // Getting search form
        $form = new SimpleSearchForm;

        $this->view()->assign(array(
            'title'         => __('All Media'),
            'medias'        => $resultSet,
            'paginator'     => $paginator,
            'type'          => $type,
            'keyword'       => $keyword,
            'types'         => $types,
            'form'          => $form,
        ));
    }
    
    /**
     * Adding media information
     * 
     * @return ViewModel 
     */
    public function addAction()
    {
        $allowed = Service::getModuleResourcePermission('media');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $form   = $this->getMediaForm('add');

        $form->setData(array(
            'fake_id'  => Upload::randomKey(),
        ));

        Service::setModuleConfig($this);
        $this->view()->assign(array(
            'title'                 => __('Add Media'),
            'form'                  => $form,
        ));
        $this->view()->setTemplate('media-edit');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new MediaEditFilter);
            $columns = array('id', 'name', 'title', 'description', 'media');
            $form->setValidationGroup($columns);
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            
            $data = $form->getData();
            $id   = $this->saveMedia($data);
            if (!$id) {
                return Service::renderForm($this, $form, __('Can not save data!'), true);
            }
            return $this->redirect()->toRoute('', array('action' => 'list'));
        }
    }

    /**
     * Editing media information.
     * 
     * @return ViewModel
     */
    public function editAction()
    {
        $allowed = Service::getModuleResourcePermission('media');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        Service::setModuleConfig($this);
        $this->view()->assign('title', __('Edit Media Info'));
        
        $form = $this->getMediaForm('edit');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new MediaEditFilter);
            $columns = array('id', 'name', 'title', 'description', 'media');
            $form->setValidationGroup($columns);
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('Can not update data!'), true);
            }
            $data = $form->getData();
            $id   = $this->saveMedia($data);

            return $this->redirect()->toRoute('', array('action' => 'list'));
        }
        
        $id     = $this->params('id', 0);
        if (empty($id)) {
            $this->jumpto404(__('Invalid media ID!'));
        }

        $model = $this->getModel('media');
        $row   = $model->find($id);
        if (!$row->id) {
            return $this->jumpTo404(__('Can not find media!'));
        }
        
        $data = $row->toArray();
        $data['media'] = $data['url'];
        $form->setData($data);

        $this->view()->assign(array(
            'form' => $form,
            'type' => $data['type'],
        ));
    }
    
    /**
     * Deleting a media
     * 
     * @throws \Exception 
     */
    public function deleteAction()
    {
        $allowed = Service::getModuleResourcePermission('media');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $from   = Service::getParam($this, 'from', '');
        
        $id     = $this->params('id', 0);
        $ids    = array_filter(explode(',', $id));

        if (empty($ids)) {
            throw new \Exception(__('Invalid category id'));
        }
        
        // Removing media statistics
        $model = $this->getModel('media_statistics');
        $model->delete(array('media' => $ids));
        
        // Removing media
        $rowset = $this->getModel('media')->select(array('id' => $ids));
        foreach ($rowset as $row) {
            if ($row->url) {
                unlink(Pi::path($row->url));
            }
        }
        
        $this->getModel('media')->delete(array('id' => $ids));

        // Go to list page or original page
        if ($from) {
            $from = urldecode($from);
            return $this->redirect()->toUrl($from);
        } else {
            return $this->redirect()->toRoute('', array('action' => 'list'));
        }
    }
    
    /**
     * Processing media uploaded. 
     */
    public function uploadAction()
    {
        Pi::service('log')->active(false);
        
        $module = $this->getModule();

        $result = false; 
        $data   = $return = array();
        $id     = Service::getParam($this, 'id', 0);
        if (empty($id)) {
            $id = Service::getParam($this, 'fake_id', 0);
        }
        $source = Service::getParam($this, 'source', 'media');

        // Checking whether ID is empty
        if ($id) {
            $return['message'] = __('Invalid id');
        }
        
        $rawInfo = $this->request->getFiles('upload');
        $rename  = $id;

        $autoSplit   = ('media' == $source or 'feature' == $source) ? true : false;
        $destination = Upload::getTargetDir($source, $module, true, $autoSplit);
        $ext         = pathinfo($rawInfo['name'], PATHINFO_EXTENSION);
        if ($ext) {
            $rename .= '.' . $ext;
        }

        $allowedExtension = ($source == 'media') ? 
                            $this->config('media_extension')
                            : $this->config('image_extension');
        $mediaSize = ($source == 'media') ? $this->config('max_media_size')
                        : $this->config('max_image_size');
        $upload = new UploadHandler;
        $upload->setDestination(Pi::path($destination))
                ->setRename($rename)
                ->setExtension($allowedExtension)
                ->setSize($mediaSize);

        // Checking whether uploaded file is valid
        if ($upload->isValid()) {
            $return['message'] = $upload->getMessages();
        }

        $upload->receive();
        
        $basename = $rename;
        if ('feature' == $source) {
            $baseName = $upload->getUploaded('featureImage', false);
            if (is_array($baseName)) {
                $baseName = current($baseName);
            }
        }
        $fileName = $destination . '/' . $basename;

        $imageExt = explode(',', $this->config('image_extension'));
        foreach ($imageExt as &$value) {
            $value = trim($value);
        }
        // Scale image if file is image file
        $uploadInfo['tmp_name'] = $fileName;
        if (in_array($ext, $imageExt)) {
            $widthKey  = (('media' == $source) ? 'image' : $source) . '_width';
            $heightKey = (('media' == $source) ? 'image' : $source) . '_height';
            $uploadInfo['w']        = $this->config($widthKey);
            $uploadInfo['h']        = $this->config($heightKey);
            if ('feature' == $source) {
                $uploadInfo['thumb_w']  = $this->config('feature_thumb_width');
                $uploadInfo['thumb_h']  = $this->config('feature_thumb_height');
            }
            Upload::saveImage($uploadInfo);
        }

        // Save media
        switch ($source) {
            case 'category':
                $model = $this->getModel('category');
                $field = 'image';
                break;
            case 'feature':
                $model = $this->getModel('draft');
                $field = 'image';
                break;
            case 'author':
                $model = $this->getModel('author');
                $field = 'photo';
                break;
            case 'topic':
                $model = $this->getModel('topic');
                $field = 'image';
                break;
            case 'media':
            default:
                $model = $this->getModel('media');
                $field = 'url';
                break;
        }
 
        // Save info to session
        $session = Upload::getUploadSession($module, $source);
        $session->$id = $uploadInfo;

        $result = true;

        $imageSize = array();
        if (in_array($ext, $imageExt)) {
            $imageSizeRaw   = getimagesize(Pi::path($fileName));
            $imageSize['w'] = $imageSizeRaw['0'];
            $imageSize['h'] = $imageSizeRaw['1'];
        }
        

        // Prepare return data
        $return['data'] = array_merge(array(
            'originalName' => $rawInfo['name'],
            'size'         => $rawInfo['size'],
            'preview_url'  => Pi::url($fileName) . '?' . time(),
            'basename'     => basename($fileName),
            'type'         => $ext,
        ), $imageSize);

        $return['status'] = $result ? self::AJAX_RESULT_TRUE : self::AJAX_RESULT_FALSE;

        echo json_encode($return);
        exit;
    }
    
    /**
     * Remove uploaded but not saved media.
     * 
     * @return ViewModel 
     */
    public function removeAction()
    {
        Pi::service('log')->active(false);
        $id           = Service::getParam($this, 'id', 0);
        $fakeId       = Service::getParam($this, 'fake_id', 0);
        $affectedRows = 0;
        $module       = $this->getModule();

        if ($id) {
            $row = $this->getModel('media')->find($id);

            if ($row && $row->url) {
                // Delete media
                unlink(Pi::path($row->url));

                // Update db
                $row->url = '';
                $affectedRows = $row->save();
            }
        } else if ($fakeId) {
            $session = Upload::getUploadSession($module, 'media');

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
}
