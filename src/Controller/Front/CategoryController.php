<?php
/**
 * Article module category controller
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
use Module\Article\Form\CategoryEditForm;
use Module\Article\Form\CategoryEditFilter;
use Module\Article\Form\CategoryMergeForm;
use Module\Article\Form\CategoryMergeFilter;
use Module\Article\Form\CategoryMoveForm;
use Module\Article\Form\CategoryMoveFilter;
use Module\Article\Model\Category;
use Module\Article\Upload;
use Zend\Db\Sql\Expression;
use Module\Article\Service;
use Module\Article\Cache;
use Module\Article\Model\Article;
use Module\Article\Entity;

/**
 * Public action controller for operating category
 */
class CategoryController extends ActionController
{
    /**
     * Getting category form object
     * 
     * @param string $action  Form name
     * @return \Module\Article\Form\CategoryEditForm 
     */
    protected function getCategoryForm($action = 'add')
    {
        $form = new CategoryEditForm();
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => $action)),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
            'class'   => 'form-horizontal',
        ));

        return $form;
    }

    /**
     * Saving category information
     * 
     * @param  array    $data  Category information
     * @return boolean
     * @throws \Exception 
     */
    protected function saveCategory($data)
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
    
    protected function getCacheKey($category)
    {
        $result = false;

        switch ($category) {
            case '2':
                $result = Cache::KEY_ARTICLE_NEWS_COUNT;
                break;
            case '3':
                $result = Cache::KEY_ARTICLE_PRODUCT_COUNT;
                break;
            case '4':
                $result = Cache::KEY_ARTICLE_DESIGN_COUNT;
                break;
        }

        return $result;
    }

    /**
     * Category index page, which will redirect to category article list page
     */
    public function indexAction()
    {
        return $this->redirect()->toRoute('', array(
            'action'    => 'list',
        ));
    }
    
    /**
     * Processing article list in category
     */
    public function listAction()
    {
        $modelCategory = $this->getModel('category');

        $category   = Service::getParam($this, 'category', '');
        $categoryId = is_numeric($category) ? (int) $category : $modelCategory->slugToId($category);
        $page       = Service::getParam($this, 'p', 1);
        $page       = $page > 0 ? $page : 1;

        $module = $this->getModule();
        $config = Pi::service('module')->config('', $module);
        $limit  = (int) $config['page_limit_front'] ?: 40;
        $where  = array();
        
        $route  = '.' . Service::getRouteName();

        // Get category info
        $categories = Cache::getCategoryList();
        foreach ($categories as &$row) {
            $row['url'] = $this->url($route, array(
                'category' => $row['slug'] ?: $row['id'],
            ));
        }
        $categoryIds = $modelCategory->getDescendantIds($categoryId);
        if (empty($categoryIds)) {
            return $this->jumpTo404(__('Invalid category id'));
        }
        $where['category']  = $categoryIds;
        $categoryInfo       = $categories[$categoryId];

        // Get articles
        $columns            = array('id', 'subject', 'time_publish', 'category');
        $resultsetArticle   = Entity::getAvailableArticlePage($where, $page, $limit, $columns, null, $module);

        // Total count
        $cacheKey   = $this->getCacheKey($categoryId);
        $totalCount = (int) Cache::getSimple($cacheKey);
        if (empty($totalCount)) {
            $where = array_merge($where, array(
                'time_publish <= ?' => time(),
                'status'            => Article::FIELD_STATUS_PUBLISHED,
                'active'            => 1,
            ));
            $modelArticle   = $this->getModel('article');
            $totalCount     = $modelArticle->getSearchRowsCount($where);

            Cache::setSimple($cacheKey, $totalCount);
        }

        // Pagination
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $route,
                'params'    => array(
                    'category'      => $category,
                ),
            ));

        $this->view()->assign(array(
            'title'         => __('Article List in Category'),
            'articles'      => $resultsetArticle,
            'paginator'     => $paginator,
            'categories'    => $categories,
            'categoryInfo'  => $categoryInfo,
            'category'      => $category,
            'p'             => $page,
            'config'        => $config,
            //'seo'           => $this->setupSeo($categoryId),
        ));

        $this->view()->viewModel()->getRoot()->setVariables(array(
            'breadCrumbs' => true,
            'Tag'         => $categoryInfo['title'],
        ));
    }
    
    /**
     * Adding category information
     * 
     * @return ViewModel 
     */
    public function addAction()
    {
        $allowed = Service::getModuleResourcePermission('category');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $parent = $this->params('parent', 0);

        $form   = $this->getCategoryForm('add');

        if ($parent) {
            $form->get('parent')->setAttribute('value', $parent);
        }

        $form->setData(array(
            'fake_id'  => Upload::randomKey(),
        ));

        Service::setModuleConfig($this);
        $this->view()->assign(array(
            'title'                 => __('Add Category Info'),
            'form'                  => $form,
        ));
        $this->view()->setTemplate('category-edit');
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new CategoryEditFilter);
            $form->setValidationGroup(Category::getAvailableFields());
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('There are some error occured!'), true);
            }
            
            $data = $form->getData();
            $id   = $this->saveCategory($data);
            if (!$id) {
                return Service::renderForm($this, $form, __('Can not save data!'), true);
            }
            return $this->redirect()->toRoute('', array('action' => 'list-category'));
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

    /**
     * Listing all added categories
     */
    public function listCategoryAction()
    {
        $allowed = Service::getModuleResourcePermission('category');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $model = $this->getModel('category');
        $rowset = $model->enumerate(null, null, true);

        $this->view()->assign('categories', $rowset);
        $this->view()->assign('title', __('Category List'));
    }

    /**
     * Merging source category to target category
     * 
     * @return ViewModel 
     */
    public function mergeAction()
    {
        $allowed = Service::getModuleResourcePermission('category');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $form = new CategoryMergeForm();
        $this->view()->assign('form', $form);
        $this->view()->assign('title', __('Merge Category'));

        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new CategoryMergeFilter);
        
            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('Can not merge category!'), true);
            }
            $data = $form->getData();

            $categoryModel = $this->getModel('category');

            // Deny to be merged to self or a child
            $descendant = $categoryModel->getDescendantIds($data['from']);
            if (array_search($data['to'], $descendant) !== false) {
                return Service::renderForm($this, $form, __('Category cannot be moved to self or a child!'), true);
            }

            // From node cannot be default
            if ($this->config('default_category') == $data['from']) {
               return Service::renderForm($this, $form, __('Cannot merge default category'), true);
            }

            // Move children node
            $children = $categoryModel->getChildrenIds($data['from']);
            foreach ($children as $objective) {
                if (!$categoryModel->move($objective, $data['to'])) {
                    return Service::renderForm($this, $form, __('Move children error.'), true);
                }
            }

            // Change relation between article and category
            $this->getModel('article')->update(
                array('category' => $data['to']),
                array('category' => $data['from'])
            );

            // remove category
            $categoryModel->remove($data['from']);

            // Go to list page
            return $this->redirect()->toRoute('', array('action' => 'list-category'));
        }
        
        $from = $this->params('from', 0);
        $to   = $this->params('to', 0);

        if ($from) {
            $form->get('from')->setAttribute('value', $from);
        }
        if ($to) {
            $form->get('to')->setAttribute('value', $to);
        }
    }

    /**
     * Moving source category as a child of target category
     * 
     * @return ViewModel 
     */
    public function moveAction()
    {
        $allowed = Service::getModuleResourcePermission('category');
        if (!$allowed) {
            return $this->jumpToDenied();
        }
        
        $form = new CategoryMoveForm();
        $this->view()->assign('form', $form);
        $this->view()->assign('title', __('Move Category'));
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $form->setData($post);
            $form->setInputFilter(new CategoryMoveFilter);

            if (!$form->isValid()) {
                return Service::renderForm($this, $form, __('Can not move category!'), true);
            }
                
            $data = $form->getData();
            $categoryModel = $this->getModel('category');

            // Deny to be moved to self or a child
            $children = $categoryModel->getDescendantIds($data['from']);
            if (array_search($data['to'], $children) !== false) {
                return Service::renderForm($this, $form, __('Category cannot be moved to self or a child!'), true);
            }

            // Move category
            $categoryModel->move($data['from'], $data['to']);

            // Go to list page
            return $this->redirect()->toRoute('', array('action' => 'list-category'));
        }
        
        $from = $this->params('from', 0);
        $to   = $this->params('to', 0);

        if ($from) {
            $form->get('from')->setAttribute('value', $from);
        }
        if ($to) {
            $form->get('to')->setAttribute('value', $to);
        }
    }
}
