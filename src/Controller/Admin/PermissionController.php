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
use Module\Article\Form\UserLevelEditForm;
use Module\Article\Form\UserLevelEditFilter;
use Module\Article\Service;
use Zend\Db\Sql\Expression;
use Pi\Paginator\Paginator;
use Pi\Acl\Acl;

/**
 * Public class for operating permission
 */
class PermissionController extends ActionController
{
    /**
     * Getting module resources.
     * 
     * @param  bool   $columns  Whether to fetch columns or full resources
     * @return array 
     */
    protected function getResources($column = false)
    {
        $resources = array(
            // Article resources
            __('article')    => array(
                'active'             => __('publish') . '-' . __('active'),
                'publish-edit'       => __('publish') . '-' . __('edit'),
                'publish-delete'     => __('publish') . '-' . __('delete'),
            ),

            // Draft resources
            __('draft')      => array(
                'compose'            => __('draft') . '-' . __('compose'),
                'rejected-edit'      => __('rejected') . '-' . __('edit'),
                'rejected-delete'    => __('rejected') . '-' . __('delete'),
                'pending-edit'       => __('pending') . '-' . __('edit'),
                'pending-delete'     => __('pending') . '-' . __('delete'),
                'approve'            => __('pending') . '-' . __('approve'),
            ),

            // Controller resources, this will not be display in level page
            'controller'     => array(
                'topic'              => 'topic',
                'media'              => 'media',
                'author'             => 'author',
                'category'           => 'category',
            ),
        );
        
        // Return only valid columns
        $columns = array();
        if ($column) {
            foreach ($resources as $key => $res) {
                if ('controller' == $key) {
                    continue;
                }

                foreach (array_keys($res) as $item) {
                    $columns[$key][] = $item;
                }
            }
            
            return $columns;
        }
        
        return $resources;
    }
    
    /**
     * Setting module rules.
     * 
     * @param string  $role   Role name
     * @param array   $rules  Resource name and its permission
     * @return boolean 
     */
    protected function setRules($role, $rules)
    {
        $aclHandler = new Acl('admin');
        foreach ($rules as $name => $permission) {
            $aclHandler->setRule($permission, $role, 'admin', $this->getModule(), $name);
        }
        
        return true;
    }
    
    /**
     * Getting module rules.
     * 
     * @param string  $role  Role name
     * @return array 
     */
    protected function getRules($role)
    {
        $resources  = $this->getResources(true);
        
        $aclHandler = new Acl('admin');
        $aclHandler->setModule($this->getModule());
        $rules      = array();
        foreach ($resources as $row) {
            foreach ($row as $resource) {
                $rules[$resource] = $aclHandler->isAllowed($role, $resource);
            }
        }
        
        return $rules;
    }

    /**
     * Getting level form object
     * 
     * @param string $action  Form name
     * @return \Module\Article\Form\LevelEditForm 
     */
    protected function getLevelForm($action = 'add-level')
    {
        $options = array(
            'resources'  => $this->getResources(),
        );
        $form = new LevelEditForm('level', $options);
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => $action)),
            'method'  => 'post',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }
    
    /**
     * Getting valid column name.
     * 
     * @return array 
     */
    protected function getValidColumns()
    {
        $columns   = array('id', 'name', 'title', 'description');
        $resources = $this->getResources(true);
        foreach ($resources as $row) {
            $columns = array_merge($columns, $row);
        }

        return $columns;
    }
    
    /**
     * Getting user level form object
     * 
     * @param string $action  Form name
     * @return \Module\Article\Form\UserLevelEditForm 
     */
    protected function getUserLevelForm($action = 'add')
    {
        $form = new UserLevelEditForm;
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => $action)),
            'method'  => 'post',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }
    
    /**
     * Getting category id and title by passed un-resolve category ids.
     * 
     * @param string|array  $categoryIds
     * @return array 
     */
    protected function resolveCategory($categoryIds)
    {
        if (!is_array($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }
        
        $rowCategory = $this->getModel('category')->getRows($categoryIds, array('id', 'title'));
        $categories  = array();
        foreach ($rowCategory as $row) {
            if (!in_array($row['id'], $categoryIds)) {
                continue;
            }
            $categories[$row['id']] = $row['title'];
        }
        
        return $categories;
    }
    
    /**
     * Default page, jump to user level list page
     */
    public function indexAction()
    {
        $this->redirect()->toRoute('admin', array('action' => 'list'));
    }
    
    /**
     * Listing levels.
     *  
     */
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
        $form      = $this->getLevelForm('add-level');

        $resources = $this->getResources(true);
        $this->view()->assign(array(
            'title'     => __('Add Level Info'),
            'form'      => $form,
            'resources' => $resources,
        ));
        $this->view()->setTemplate('permission-edit-level');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new LevelEditFilter);
            $form->setValidationGroup($this->getValidColumns());
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            
            $data = $form->getData();
            
            $rules = array();
            foreach ($resources as $row) {
                foreach ($row as $resource) {
                    $rules[$resource] = $data[$resource];
                    unset($data[$resource]);
                }
            }
            
            // Storing user level
            $data['active']      = 1;
            $data['time_create'] = time();
            $row = $this->getModel('level')->createRow($data);
            $row->save();
            if (!$row->id) {
                return Service::renderForm($this, $form, __('Can not save data!'), true);
            }
            
            // Setting rules
            $this->setRules($data['name'], $rules);
            
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
        
        $resources = $this->getResources(true);
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new LevelEditFilter);
            $form->setValidationGroup($this->getValidColumns());
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            $data = $form->getData();
            
            $rules = array();
            foreach ($resources as $row) {
                foreach ($row as $resource) {
                    $rules[$resource] = $data[$resource];
                    unset($data[$resource]);
                }
            }
            
            // Saving level
            $data['time_update'] = time();
            $this->getModel('level')->update($data, array('id' => $data['id']));
            
            // Updating rules
            $this->setRules($data['name'], $rules);

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
        
        $rules = $this->getRules($row->name);
        
        $form->setData(array_merge($row->toArray(), $rules));

        $this->view()->assign('form', $form);
        $this->view()->assign('resources', $resources);
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
        $rowLevel   = $levelModel->find($id);

        // Remove relationship between level and user
        $this->getModel('user_level')->delete(array('level' => $id));
        
        // Remove rules
        $aclHandler = new Acl('admin');
        $resources  = $this->getResources(true);
        foreach ($resources as $row) {
            foreach ($row as $resource) {
                $aclHandler->removeRule($rowLevel->name, 'admin', $this->getModule(), $resource);
            }
        }

        // Remove level
        $rowLevel->delete();

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
    
    /**
     * Listing user levels. 
     */
    public function listAction()
    {
        $module = $this->getModule();
        $config = Pi::service('module')->config('', $module);
        $limit  = (int) $config['page_limit_management'] ?: 20;
        $page   = Service::getParam($this, 'p', 1);
        $page   = $page > 0 ? $page : 1;
        $offset = ($page - 1) * $limit;
        
        // Getting user level
        $model  = $this->getModel('user_level');
        $select = $model->select()
                        ->offset($offset)
                        ->limit($limit);
        $rowset = $model->selectWith($select);
        $userLevels  = array();
        $categoryIds = array(0);
        $levelIds    = array(0);
        $uids        = array(0);
        foreach ($rowset as $row) {
            $item                 = $row->toArray();
            $item['category']     = explode(',', $row->category);
            $userLevels[$row->id] = $item;
            
            foreach ($item['category'] as $category) {
                $categoryIds[$category] = $category;
            }
            $levelIds[$item['level']] = $item['level'];
            $uids[$item['uid']]       = $item['uid'];
        }
        
        // Getting category
        $rowCategory = $this->getModel('category')->select(array('id' => $categoryIds));
        $categories  = array();
        foreach ($rowCategory as $row) {
            $categories[$row->id] = $row->title;
        }
        
        // Getting level
        $rowLevel = $this->getModel('level')->select(array('id' => $levelIds));
        $levels   = array();
        foreach ($rowLevel as $row) {
            $levels[$row->id] = $row->title;
        }
        
        // Getting users
        $rowUser = Pi::model('user_account')->select(array('id' => $uids));
        $users   = array();
        foreach ($rowUser as $row) {
            $users[$row->id] = $row->name;
        }
        
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
                'action'        => 'list',
            ),
        ));

        $this->view()->assign(array(
            'userLevels'   => $userLevels,
            'title'        => __('User Level List'),
            'action'       => 'list',
            'categories'   => $categories,
            'levels'       => $levels,
            'users'        => $users,
        ));
    }
    
    public function addAction()
    {
        $form      = $this->getUserLevelForm('add');

        $this->view()->assign(array(
            'title'     => __('Add User Level'),
            'form'      => $form,
        ));
        $this->view()->setTemplate('permission-edit');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new UserLevelEditFilter);
            $columns = array('id', 'uid', 'category', 'level');
            $form->setValidationGroup($columns);
            $this->view()->assign('categories', $this->resolveCategory($post['category']));
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            
            $data = $form->getData();
            if (empty($data['uid']) or empty($data['level'])) {
                return Service::renderForm($this, $form, __('Invalid user or level!'), true);
            }
            
            $row = $this->getModel('user_level')->createRow($data);
            $row->save();
            if (!$row->id) {
                return Service::renderForm($this, $form, __('Can not save data!'), true);
            }
            
            return $this->redirect()->toRoute('', array('action' => 'list'));
        }
    }
    
    /**
     * Getting available categories by AJAX.
     * 
     * @return JSON 
     */
    public function getCategoryAction()
    {
        $title  = $this->params('category', '');
        
        $result = array();
        
        $model  = $this->getModel('category');
        if (!empty($title)) {
            $rows = $model->select(array('title like ?' => '%' . $title . '%'))->toArray();
        } else {
            $rows   = $model->getList(array('id', 'title'));
        }
        $categories = array();
        foreach ($rows as $row) {
            $categories[$row['id']] = $row['title'];
        }
        
        if (empty($categories)) {
            $result['status'] = false;
            $result['content'] = __('No category availabled!');
        } else {
            $result['status'] = true;
            $result['content'] = $categories;
        }
        
        return json_encode($result);
    }
}
