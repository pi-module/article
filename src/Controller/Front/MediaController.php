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
use Pi\File\Transfer\Download;
use ZipArchive;

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
                    $data['type'] = strtolower($pathInfo['extension']);
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
            $types[$item] = trim($item);
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
                    'module'     => $this->getModule(),
                    'controller' => 'media',
                    'action'     => 'list',
                    'type'       => $type,
                    'keyword'    => $keyword,
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
     * Details page to implement media. 
     */
    public function detailAction()
    {
        $this->view()->assign(array(
            'content' => __('This page have not been considered yet!'))
        );
        $this->view()->setTemplate(false);
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
            $columns = array('id', 'name', 'title', 'description', 'url');
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
            $options = array(
                'id'   => $post['id'],
            );
            $form->setInputFilter(new MediaEditFilter($options));
            $columns = array('id', 'name', 'title', 'description', 'url');
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
        $form->setData($data);

        $this->view()->assign(array(
            'form' => $form,
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
        $return = array('status' => false);
        $id     = Service::getParam($this, 'id', 0);
        if (empty($id)) {
            $id = Service::getParam($this, 'fake_id', 0) ?: uniqid();
        }
        $type   = Service::getParam($this, 'type', 'attachment');
        $formName = Service::getParam($this, 'form_name', 'upload');

        // Checking whether ID is empty
        if (empty($id)) {
            $return['message'] = __('Invalid id');
            return json_encode($return);
            exit ;
        }
        
        $width   = Service::getParam($this, 'width', $this->config('image_width'));
        $height  = Service::getParam($this, 'height', $this->config('image_height'));
        
        $rawInfo = $this->request->getFiles($formName);
        $rename  = $id;

        $destination = Upload::getTargetDir('media', $module, true, true);
        $ext         = pathinfo($rawInfo['name'], PATHINFO_EXTENSION);
        if ($ext) {
            $rename .= '.' . $ext;
        }

        $allowedExtension = ($type == 'image') ? 
                            $this->config('image_extension')
                            : $this->config('media_extension');
        $mediaSize = ($type == 'image') ? $this->config('max_image_size')
                        : $this->config('max_media_size');
        $upload = new UploadHandler;
        $upload->setDestination(Pi::path($destination))
                ->setRename($rename)
                ->setExtension($allowedExtension)
                ->setSize($mediaSize);

        // Checking whether uploaded file is valid
        if (!$upload->isValid()) {
            $return['message'] = $upload->getMessages();
            echo json_encode($return);
            exit ;
        }

        $upload->receive();
        
        $basename = $rename;
        $fileName = $destination . '/' . $basename;

        $imageExt = explode(',', $this->config('image_extension'));
        foreach ($imageExt as &$value) {
            $value = trim($value);
        }
        // Scale image if file is image file
        $uploadInfo['tmp_name'] = $fileName;
        if (in_array($ext, $imageExt)) {
            $uploadInfo['w']        = $width;
            $uploadInfo['h']        = $height;
            Upload::saveImage($uploadInfo);
        }

        // Save uploaded media
        $rowMedia = $this->getModel('media')->find($id);
        if ($rowMedia) {
            if ($rowMedia->url && $rowMedia->url != $fileName) {
                unlink(Pi::path($rowMedia->url));
            }

            $rowMedia->url = $fileName;
            $rowMedia->save();
        } else {
            // Or save info to session
            $session = Upload::getUploadSession($module, 'media');
            $session->$id = $uploadInfo;
        }

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
            'preview_url'  => Pi::url($fileName),
            'basename'     => basename($fileName),
            'type'         => $ext,
            'id'           => $id,
            'filename'     => $fileName,
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
                $row->url  = '';
                $row->type = '';
                $row->size = 0;
                $row->meta = '';
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
            $affectedRows = 1;
        }

        return array(
            'status'    => $affectedRows ? self::AJAX_RESULT_TRUE : self::AJAX_RESULT_FALSE,
            'message'   => 'ok',
        );
    }
    
    /**
     * Downloading a media.
     * 
     * @return ViewModel
     * @throws \Exception 
     */
    public function downloadAction()
    {
        $id     = $this->params('id', 0);
        $ids    = array_filter(explode(',', $id));

        if (empty($ids)) {
            throw new \Exception(__('Invalid media ID!'));
        }
        
        // Export files
        $rowset     = $this->getModel('media')->select(array('id' => $ids));
        $affectRows = array();
        $files      = array();
        foreach ($rowset as $row) {
            if (!empty($row->url) and file_exists(Pi::path($row->url))) {
                $files[]      = Pi::path($row->url);
                $affectRows[] = $row->id;
            }
        }
        
        if (empty($affectRows)) {
            return $this->jumpTo404(__('Can not find file!'));
        }
        
        // Statistics
        $model  = $this->getModel('media_statistics');
        $rowset = $model->select(array('media' => $affectRows));
        $exists = array();
        foreach ($rowset as $row) {
            $exists[] = $row->media;
        }
        
        if (!empty($exists)) {
            foreach ($exists as $item) {
                $row = $model->find($item, 'media');
                $row->download = $row->download + 1;
                $row->save();
            }
        }
        
        $newRows = array_diff($affectRows, $exists);
        foreach ($newRows as $item) {
            $data = array(
                'media'    => $item,
                'download' => 1,
            );
            $row = $model->createRow($data);
            $row->save();
        }
        
        $filePath = 'upload/temp';
        Upload::mkdir($filePath);
        $filename = sprintf('%s/media-%s.zip', $filePath, time());
        $filename = Pi::path($filename);
        $zip      = new ZipArchive();
        if ($zip->open($filename, ZIPARCHIVE::CREATE)!== TRUE) {
            exit ;
        }
        $compress = count($files) > 1 ? true : false;
        if ($compress) {
            foreach( $files as $file) {
                if (file_exists($file)) {  
                    $zip->addFile( $file , basename($file));
                }
            }  
            $zip->close();
        } else {
            $filename = Pi::path(array_shift($files));
        }
        
        $options = array(
            'file'       => $filename,
            'fileName'   => basename($filename),
        );
        if ($compress) {
            $options['deleteFile'] = true;
        }
        Upload::httpOutputFile($options, $this);
    }
    
    /**
     * Searching image details by AJAX according to posted parameters. 
     */
    public function searchAction()
    {
        Pi::service('log')->active(false);
        
        $type = Service::getParam($this, 'type', '');
        
        $where = array();
        // Resolving type
        if ($type == 'image') {
            $extensionDesc = $this->config('image_extension');
        } else {
            $extensionDesc = $type;
        }
        $extensions = explode(',', $extensionDesc);
        foreach ($extensions as &$ext) {
            $ext = trim($ext);
        }
        $type = array_filter($extensions);
        if (!empty($type)) {
            $where['type'] = $type;
        }
        
        // Resolving ID
        $id  = Service::getParam($this, 'id', 0);
        $ids = array_filter(explode(',', $id));
        if (!empty($ids)) {
            $where['id'] = $ids;
        }
        
        // Getting title condition
        $title = Service::getParam($this, 'title', '');
        if (!empty($title)) {
            $where['title like ?'] = '%' . $title . '%';
        }
        
        $page   = (int) Service::getParam($this, 'page', 1);
        $limit  = (int) Service::getParam($this, 'limit', 10);
        
        $rowset = Media::getList($where, $page, $limit);
        
        echo json_encode($rowset);
        exit();
    }
    
    /**
     * Saving image into media table by AJAX. 
     */
    public function saveAction()
    {
        Pi::service('log')->active(false);
        
        $id     = $this->params('id', 0);
        $fakeId = $this->params('fake_id', 0);
        $result = array();
        if (empty($fakeId) and empty($id)) {
            $result = array(
                'status'     => self::AJAX_RESULT_FALSE,
                'data'       => array(
                    'message'   => __('Invalid ID!'),
                ),
            );
        } else {
            $data = array();
            if ($id) {
                $data['id'] = $id;
            } else {
                $data = array(
                    'id'    => 0,
                    'name'  => $fakeId,
                    'title' => 'File ' . $fakeId . ' from outside',
                );
            }
            $mediaId = $this->saveMedia($data);
            if (empty($mediaId)) {
                $result = array(
                    'status'     => self::AJAX_RESULT_FALSE,
                    'data'       => array(
                        'message'   => __('Can not save data!'),
                    ),
                );
            } else {
                $result = array(
                    'status'     => self::AJAX_RESULT_TRUE,
                    'data'       => array(
                        'id'        => $mediaId,
                        'newid'     => uniqid(),
                        'message'   => __('Media data saved successful!'),
                    ),
                );
            }
        }
        
        echo json_encode($result);
        exit;
    }
}
