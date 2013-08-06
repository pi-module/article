<?php
/**
 * Article module author controller
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
use Module\Article\Form\AuthorEditForm;
use Module\Article\Form\AuthorEditFilter;
use Module\Article\Model\Author;
use Module\Article\Upload;
use Zend\Db\Sql\Expression;
use Module\Article\Service;

/**
 * Public action controller for operating author
 */
class AuthorController extends ActionController
{
    /**
     * Getting form instance
     * 
     * @param string  $action  Action to request when submit
     * @return \Module\Article\Form\AuthorEditForm 
     */
    protected function getAuthorForm($action = 'add')
    {
        $form = new AuthorEditForm();
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => $action)),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }

    /**
     * Saving author information
     * 
     * @param array  $data  Author information
     * @return boolean 
     */
    protected function saveAuthor($data)
    {
        $module      = $this->getModule();
        $modelAuthor = $this->getModel('author');
        $fakeId      = $photo = null;

        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
        }

        $fakeId = Service::getParam($this, 'fake_id', 0);

        unset($data['photo']);

        if (empty($id)) {
            $rowAuthor = $modelAuthor->createRow($data);
            $rowAuthor->save();

            if (empty($rowAuthor->id)) {
                return false;
            }

            $id = $rowAuthor->id;
        } else {
            $rowAuthor = $modelAuthor->find($id);

            if (empty($rowAuthor)) {
                return false;
            }

            $rowAuthor->assign($data);
            $rowAuthor->save();
        }

        // Save photo
        $session    = Upload::getUploadSession($module, 'author');
        if (isset($session->$id) || ($fakeId && isset($session->$fakeId))) {
            $uploadInfo = isset($session->$id) ? $session->$id : $session->$fakeId;

            if ($uploadInfo) {
                $fileName = $rowAuthor->id;

                $pathInfo = pathinfo($uploadInfo['tmp_name']);
                if ($pathInfo['extension']) {
                    $fileName .= '.' . $pathInfo['extension'];
                }
                $fileName = $pathInfo['dirname'] . '/' . $fileName;

                $rowAuthor->photo = rename(Pi::path($uploadInfo['tmp_name']), Pi::path($fileName)) ? $fileName : $uploadInfo['tmp_name'];
                $rowAuthor->save();
            }

            unset($session->$id);
            unset($session->$fakeId);
        }

        return $id;
    }

    /**
     * Default action, jump to author list page
     * 
     * @return ViewModel 
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('', array(
            'action'    => 'list',
        ));
    }

    /**
     * Adding author
     * 
     * @return ViewModel 
     */
    public function addAction()
    {
        $allowed = Service::getModuleResourcePermission('author');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $form = $this->getAuthorForm('add');
        Service::setModuleConfig($this);
        $this->view()->assign('title', __('Add author info'));
        $this->view()->setTemplate('author-edit');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new AuthorEditFilter);
            $form->setValidationGroup(Author::getAvailableFields());

            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            
            $data = $form->getData();
            $id   = $this->saveAuthor($data);

            if (!$id) {
                return $this->renderForm($form, __('Can not save data!'), true);
            }
            $this->redirect()->toRoute('', array('action'=>'list'));
        }

        $form->setData(array(
            'fake_id'  => Upload::randomKey(),
        ));
        $this->view()->assign('form', $form);
    }
    
    /**
     * Editing author information
     * 
     * @return ViewModel
     */
    public function editAction()
    {
        $allowed = Service::getModuleResourcePermission('author');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $form = $this->getAuthorForm('edit');
        Service::setModuleConfig($this);
        $this->view()->assign('title', __('Edit Author Info'));
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new AuthorEditFilter);
            $form->setValidationGroup(Author::getAvailableFields());

            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            
            $data = $form->getData();
            $id   = $this->saveAuthor($data);

            return $this->redirect()->toRoute('', array('action' => 'list'));
        }
        
        $id  = $this->params('id', 0);
        if (empty($id)) {
            return $this->jumpto404(__('Invalid author id'));
        }

        $row = $this->getModel('author')->find($id);
        if (!$row->id) {
            return $this->jumpTo404(__('The author is not exists'));
        }
        $form->setData($row->toArray());
        $this->view()->assign('form', $form);
    }
    
    /**
     * Deleting authors by given id
     * 
     * @return ViewModel
     */
    public function deleteAction()
    {
        $allowed = Service::getModuleResourcePermission('author');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $id     = $this->params('id');
        $ids    = array_filter(explode(',', $id));
        if (empty($ids)) {
            return $this->jumpTo404(__('Invalid author id!'));
        }

        $modelAuthor = $this->getModel('author');
        // Clear article author
        $this->getModel('article')->update(array('author' => 0), array('author' => $ids));

        // Delete photo
        $resultset = $modelAuthor->select(array('id' => $ids));
        foreach ($resultset as $row) {
            if ($row->photo) {
                unlink(Pi::path($row->photo));
            }
        }

        // Delete author
        $modelAuthor->delete(array('id' => $ids));

        // Go to list page
        return $this->redirect()->toRoute('', array('action' => 'list'));
    }

    /**
     * Listing all authors 
     */
    public function listAction()
    {
        $allowed = Service::getModuleResourcePermission('author');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $page   = Service::getParam($this, 'p', 1);
        $name   = Service::getParam($this, 'name', '');
        $limit  = $this->config('author_limit') > 0 ? $this->config('author_limit') : 20;
        $offset = $limit * ($page - 1);

        $module = $this->getModule();
        $model  = $this->getModel('author');
        $select = $model->select();
        if ($name) {
            $select->where->like('name', "%{$name}%");
        }
        $select->order('name ASC')->offset($offset)->limit($limit);

        $resultset = $model->selectWith($select);

        // Total count
        $select = $model->select()->columns(array('total' => new Expression('count(id)')));
        if ($name) {
            $select->where->like('name', "%{$name}%");
        }
        $authorCountResultset = $model->selectWith($select);
        $totalCount = intval($authorCountResultset->current()->total);

        // PaginatorPaginator
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
                'params'    => array_filter(array(
                    'module'        => $module,
                    'controller'    => $this->getEvent()->getRouteMatch()->getParam('controller'),
                    'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
                    'name'          => $name,
                )),
            ));

        $this->view()->assign(array(
            'title'     => __('Author List'),
            'authors'   => $resultset,
            'paginator' => $paginator,
        ));
    }
}
