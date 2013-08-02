<?php
/**
 * Article module permission controller
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

namespace Module\Article\Controller\Admin;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Module\Article\Form\LevelEditForm;
use Module\Article\Form\LevelEditFilter;
use Module\Article\Service;
use Zend\Db\Sql\Expression;
use Pi\Paginator\Paginator;

/**
 * Public class for operating permission
 */
class PermissionController extends ActionController
{
    /**
     * Getting level form object
     * 
     * @param string $action  Form name
     * @return \Module\Article\Form\LevelEditForm 
     */
    protected function getLevelForm($action = 'add-level')
    {
        $form = new LevelEditForm();
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => $action)),
            'method'  => 'post',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }
    
    /**
     * Default page, jump to user level list page
     */
    public function indexAction()
    {
        $this->redirect()->toRoute('admin', array('action' => 'list'));
    }
    
    public function listLevelAction()
    {
        $module = $this->getModule();
        $config = Pi::service('module')->config('', $module);
        $limit  = (int) $config['page_limit_management'] ?: 20;
        $page   = Service::getParam($this, 'p', 1);
        $page   = $page > 0 ? $page : 1;
        $offset = ($page - 1) * $limit;
        
        $model  = $this->getModel('level');
        $select = $model->select()
                        ->offset($offset)
                        ->limit($limit);
        $rowset = $model->selectWith($select);
        
        $select = $model->select()->columns(array('count' => new Expression('count(*)')));
        $count  = (int) $model->selectWith($select)->current()->count;
        
        $paginator = Paginator::factory($count);
        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);
        $paginator->setUrlOptions(array(
            'router'        => $this->getEvent()->getRouter(),
            'route'         => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
            'params'        => array(
                'module'        => $module,
                'controller'    => 'permission',
                'action'        => 'list-level',
            ),
        ));

        $this->view()->assign('levels', $rowset);
        $this->view()->assign('title', __('Level List'));
        $this->view()->assign('action', 'list-level');
    }
    
    /**
     * Adding a level.
     * 
     * @return ViewModel 
     */
    public function addLevelAction()
    {
        $form   = $this->getLevelForm('add-level');

        $this->view()->assign(array(
            'title'   => __('Add Level Info'),
            'form'    => $form,
        ));
        $this->view()->setTemplate('permission-edit-level');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new LevelEditFilter);
            $columns = array('id', 'name', 'title', 'description');
            $form->setValidationGroup($columns);
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            
            $data = $form->getData();
            $data['active']      = 1;
            $data['time_create'] = time();
            
            $row = $this->getModel('level')->createRow($data);
            $row->save();
            
            if (!$row->id) {
                return Service::renderForm($this, $form, __('Can not save data!'), true);
            }
            return $this->redirect()->toRoute('', array('action' => 'list-level'));
        }
    }
    
    /**
     * Editing a level.
     * 
     * @return ViewModel 
     */
    public function editLevelAction()
    {
        $this->view()->assign('title', __('Edit Level Info'));
        
        $form = $this->getLevelForm('edit-level');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new LevelEditFilter);
            $columns = array('id', 'name', 'title', 'description');
            $form->setValidationGroup($columns);
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            $data = $form->getData();
            $data['time_update'] = time();
            
            $this->getModel('level')->update($data, array('id' => $data['id']));

            return $this->redirect()->toRoute('', array('action' => 'list-level'));
        }
        
        $id     = $this->params('id', 0);
        if (empty($id)) {
            $this->jumpto404(__('Invalid level id!'));
        }

        $model = $this->getModel('level');
        $row   = $model->find($id);
        if (!$row->id) {
            return $this->jumpTo404(__('Can not find level!'));
        }
        
        $form->setData($row->toArray());

        $this->view()->assign('form', $form);
    }
    
    /**
     * Deleting a level.
     * 
     * @return ViewModel
     * @throws \Exception 
     */
    public function deleteLevelAction()
    {
        $id     = $this->params('id');
        if (empty($id)) {
            throw new \Exception(__('Invalid level id'));
        }

        $levelModel = $this->getModel('level');

        // Remove relationship between level and user
        $this->getModel('user_level')->delete(array('level' => $id));

        // Remove level
        $levelModel->delete(array('id' => $id));

        // Go to list page
        return $this->redirect()->toRoute('', array('action' => 'list-level'));
    }
    
    /**
     * Active or deactivate a level.
     * 
     * @return ViewModel 
     */
    public function activeAction()
    {
        $status = Service::getParam($this, 'status', 0);
        $id     = Service::getParam($this, 'id', 0);
        $from   = Service::getParam($this, 'from', 0);
        if (empty($id)) {
            return $this->jumpTo404(__('Invalid ID!'));
        }
        
        $model  = $this->getModel('level');
        if (is_numeric($id)) {
            $row = $model->find($id);
        } else {
            $row = $model->find($id, 'name');
        }
        
        $row->active = $status;
        $result = $row->save();
        
        if ($from) {
            $from = urldecode($from);
            return $this->redirect()->toUrl($from);
        } else {
            return $this->redirect()->toRoute('', array('action' => 'list-level'));
        }
    }
}
