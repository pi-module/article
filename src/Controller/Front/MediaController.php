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

/**
 * Public action controller for operating media
 */
class MediaController extends ActionController
{
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
        $modelCategory = $this->getModel('category');
        $fakeId        = $image = null;

        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
        }

        $fakeId = Service::getParam($this, 'fake_id', 0);

        unset($data['image']);

        $parent = $data['parent'];
        unset($data['parent']);

        if (isset($data['slug']) && empty($data['slug'])) {
            unset($data['slug']);
        }

        if (empty($id)) {
            $id = $modelCategory->add($data, $parent);
            $rowCategory = $modelCategory->find($id);
        } else {
            $rowCategory = $modelCategory->find($id);

            if (empty($rowCategory)) {
                return false;
            }

            $rowCategory->assign($data);
            $rowCategory->save();

            // Move node position
            $parentNode    = $modelCategory->getParentNode($id);
            $currentParent = $parentNode['id'];
            if ($currentParent != $parent) {
                $children = $modelCategory->getDescendantIds($id);
                if (array_search($parent, $children) !== false) {
                    throw new \Exception(__('Category cannot be moved to self or a child'));
                } else {
                    $modelCategory->move($id, $parent);
                }
            }
        }

        // Save image
        $session    = Upload::getUploadSession($module, 'category');
        if (isset($session->$id) || ($fakeId && isset($session->$fakeId))) {
            $uploadInfo = isset($session->$id) ? $session->$id : $session->$fakeId;

            if ($uploadInfo) {
                $fileName = $rowCategory->id;

                $pathInfo = pathinfo($uploadInfo['tmp_name']);
                if ($pathInfo['extension']) {
                    $fileName .= '.' . $pathInfo['extension'];
                }
                $fileName = $pathInfo['dirname'] . '/' . $fileName;

                $rowCategory->image = rename(Pi::path($uploadInfo['tmp_name']), Pi::path($fileName)) ? $fileName : $uploadInfo['tmp_name'];
                $rowCategory->save();
            }

            unset($session->$id);
            unset($session->$fakeId);
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
     * Editing category information
     * 
     * @return ViewModel
     */
    public function editAction()
    {
        $allowed = Service::getModuleResourcePermission('category');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        Service::setModuleConfig($this);
        $this->view()->assign('title', __('Edit Category Info'));
        
        $form = $this->getCategoryForm('edit');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new CategoryEditFilter);
            $form->setValidationGroup(Category::getAvailableFields());
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('Can not update data!'), true);
            }
            $data = $form->getData();
            $id   = $this->saveCategory($data);

            return $this->redirect()->toRoute('', array('action' => 'list-category'));
        }
        
        $id     = $this->params('id', 0);
        if (empty($id)) {
            $this->jumpto404(__('Invalid category id!'));
        }

        $model = $this->getModel('category');
        $row   = $model->find($id);
        if (!$row->id) {
            return $this->jumpTo404(__('Can not find category!'));
        }
        
        $form->setData($row->toArray());

        $parent = $model->getParentNode($row->id);
        if ($parent) {
            $form->get('parent')->setAttribute('value', $parent['id']);
        }

        $this->view()->assign('form', $form);
    }
    
    /**
     * Deleting a category
     * 
     * @throws \Exception 
     */
    public function deleteAction()
    {
        $allowed = Service::getModuleResourcePermission('category');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $id     = $this->params('id');

        if ($id == 1) {
            throw new \Exception(__('Root node cannot be deleted.'));
        } else if ($id) {
            $categoryModel = $this->getModel('category');

            // Check default category
            if ($this->config('default_category') == $id) {
                throw new \Exception(__('Cannot remove default category'));
            }

            // Check children
            if ($categoryModel->hasChildren($id)) {
                throw new \Exception(__('Cannot remove category with children'));
            }

            // Check related article
            $linkedArticles = $this->getModel('article')->select(array('category' => $id));
            if ($linkedArticles->count()) {
                throw new \Exception(__('Cannot remove category in used'));
            }

            // Delete image
            $row = $categoryModel->find($id);
            if ($row && $row->image) {
                unlink(Pi::path($row->image));
            }

            // Remove node
            $categoryModel->remove($id);

            // Go to list page
            $this->redirect()->toRoute('', array('action' => 'list-category'));
            $this->view()->setTemplate(false);
        } else {
            throw new \Exception(__('Invalid category id'));
        }
    }
}
