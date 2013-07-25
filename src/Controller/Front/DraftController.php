<?php
/**
 * Article module draft controller
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
use Pi\Paginator\Paginator;
use Module\Article\Form\DraftEditForm;
use Module\Article\Form\DraftEditFilter;
use Module\Article\Model\Draft;
use Module\Article\Model\Article;
use Module\Article\Model\Asset;
use Module\Article\Upload;
use Zend\Db\Sql\Expression;
use Module\Article\Service;
use Module\Article\Compiled;
use Module\Article\Controller\Admin\ConfigController as Config;

/**
 * Public action for operating draft 
 */
class DraftController extends ActionController
{
    const TAG_DELIMITER = ',';
    
    const RESULT_FALSE = false;
    const RESULT_TRUE  = true;

    /**
     * Rendering form
     * 
     * @param Zend\Form\Form $form     Form instance
     * @param string         $message  Message assign to template
     * @param bool           $isError  Whether is error message
     */
    protected function renderForm($form, $message = null, $isError = false)
    {
        $params = array('form' => $form);
        if ($isError) {
            $params['error'] = $message;
        } else {
            $params['message'] = $message;
        }
        $this->view()->assign($params);
        $this->view()->assign('form', $form);
    }
    
    /**
     * Getting draft form instance
     * 
     * @param string  $action   The action to request when forms are submitted
     * @param array   $options  Optional parameters
     * @return \Module\Article\Form\DraftEditForm 
     */
    protected function getDraftForm($action, $options = array())
    {
        $form = new DraftEditForm('draft', $options);
        $form->setAttributes(array(
            'action'  => $this->url('', array('action' => $action)),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ));

        return $form;
    }
    
    /**
     * Reading configuration data and assigning to template 
     */
    protected function setModuleConfig()
    {
        $this->view()->assign(array(
            'width'                 => $this->config('feature_width'),
            'height'                => $this->config('feature_height'),
            'image_extension'       => $this->config('image_extension'),
            'max_image_size'        => Upload::fromByteString($this->config('max_image_size')),
            'attachment_extension'  => $this->config('attachment_extension'),
            'max_attachment_size'   => Upload::fromByteString($this->config('max_attachment_size')),
            'autosave_interval'     => $this->config('autosave_interval'),
            'max_summary_length'    => $this->config('max_summary_length'),
            'max_subject_length'    => $this->config('max_subject_length'),
            'max_subtitle_length'   => $this->config('max_subtitle_length'),
            'default_source'        => $this->config('default_source'),
        ));
    }

    /**
     * Validating form such as subject, subtitle, category, slug etc.
     * 
     * @param array   $data      Posted data for validating
     * @param string  $article   
     * @param array   $elements  Displaying form defined by user
     * @return array 
     */
    protected function validateForm($data, $article = null, $elements = array())
    {
        $result = array(
            'status'    => self::RESULT_TRUE,
            'message'   => array(),
            'data'      => array(),
        );
        $config       = Pi::service('module')->config('', $this->getModule());
        $modelArticle = $this->getModel('article');

        // Validate subject
        if (in_array('subject', $elements)) {
            $subjectLength = new \Zend\Validator\StringLength(array(
                'max'       => $config['max_subject_length'],
                'encoding'  => 'utf-8',
            ));
            if (empty($data['subject'])) {
                $result['status'] = self::RESULT_FALSE;
                $result['message']['subject'] = array('isEmpty' => __('Subject cannot be empty.'));
            } else if (!$subjectLength->isValid($data['subject'])) {
                $result['status'] = self::RESULT_FALSE;
                $result['message']['subject'] = $subjectLength->getMessages();
            } else if ($modelArticle->checkSubjectExists($data['subject'], $article)) {
                $result['status'] = self::RESULT_FALSE;
                $result['message']['subject'] = array('duplicated' => __('Subject is used by another article.'));
            }
        }

        // Validate slug
        if (in_array('slug', $elements) and !empty($data['slug'])) {
            if (!$subjectLength->isValid($data['slug'])) {
                $result['status'] = self::RESULT_FALSE;
                $result['message']['slug'] = $subjectLength->getMessages();
            }
        }

        // Validate subtitle
        if (in_array('subtitle', $elements)) {
            $subtitleLength = new \Zend\Validator\StringLength(array(
                'max'       => $config['max_subtitle_length'],
                'encoding'  => 'utf-8',
            ));
            if (isset($data['subtitle']) && !$subtitleLength->isValid($data['subtitle'])) {
                $result['status'] = self::RESULT_FALSE;
                $result['message']['subtitle'] = $subtitleLength->getMessages();
            }
        }

        // Validate summary
        if (in_array('summary', $elements)) {
            $summaryLength = new \Zend\Validator\StringLength(array(
                'max'       => $config['max_summary_length'],
                'encoding'  => 'utf-8',
            ));
            if (isset($data['summary']) && !$summaryLength->isValid($data['summary'])) {
                $result['status'] = self::RESULT_FALSE;
                $result['message']['summary'] = $summaryLength->getMessages();
            }
        }

        // Validate category
        if (in_array('category', $elements) and empty($data['category'])) {
            $result['status'] = self::RESULT_FALSE;
            $result['message']['category'] = array('isEmpty' => __('Category cannot be empty.'));
        }

        return $result;
    }

    /**
     * Publishing a article, the status of article will be changed to pendding
     * 
     * @param int  $id  Article ID
     * @return array 
     */
    protected function publish($id)
    {
        $result = array(
            'status'    => self::RESULT_FALSE,
            'message'   => array(),
            'data'      => array(),
        );

        if (!$id) {
            return array('message' => __('Not enough parameter.'));
        }
        
        $modelDraft = $this->getModel('draft');
        $rowDraft   = $modelDraft->find($id);

        if (!$rowDraft->id or !in_array($rowDraft->status, array(Draft::FIELD_STATUS_DRAFT, Draft::FIELD_STATUS_REJECTED))) {
            return array('message' => __('Invalid draft.'));
        }
        
        if ($rowDraft->article) {
            return array('message' => __('Draft has been published.'));
        }
        
        $rowDraft->status = Draft::FIELD_STATUS_PENDING;
        $rowDraft->save();

        $result['status']   = self::RESULT_TRUE;
        $result['message']  = __('Draft submitted successfully.');
        $result['data']     = array(
            'id'        => $id,
            'status'    => __('Pending'),
            'btn_value' => __('Approve'),
        );

        return $result;
    }

    /**
     * Approving an article, the article details will be storing into article table,
     * status of article in article table will be changed to published, and draft will
     * be deleted.
     * 
     * @param int    $id        Article ID
     * @param array  $elements  Form elements to display
     * @return array
     */
    protected function approve($id, $elements = array())
    {
        $result = array(
            'status'    => self::RESULT_FALSE,
            'message'   => array(),
            'data'      => array(),
        );

        if (!$id) {
            return array('message' => __('Not enough parameter.'));
        }
        
        $model  = $this->getModel('draft');
        $row    = $model->find($id);
        if (!$row->id or $row->status != Draft::FIELD_STATUS_PENDING) {
            return array('message' => __('Invalid draft.'));
        }
        
        $result = $this->validateForm($row->toArray(), null, $elements);
        if (!$result['status']) {
            return $result;
        }
        
        $module         = $this->getModule();
        $modelArticle   = $this->getModel('article');
        
        // move draft to article
        $timestamp = time();
        $article = array(
            'subject'         => $row->subject,
            'subtitle'        => $row->subtitle,
            'summary'         => $row->summary,
            'content'         => $row->content,
            'markup'          => $row->markup,
            'uid'             => $row->uid,
            'author'          => $row->author,
            'source'          => $row->source,
            'pages'           => $row->pages,
            'category'        => $row->category,
            'status'          => Article::FIELD_STATUS_PUBLISHED,
            'active'          => 1,
            'time_submit'     => $row->time_submit,
            'time_publish'    => $row->time_publish ? $row->time_publish : $timestamp,
            'time_update'     => $row->time_update ? $row->time_update : 0,
            'image'           => $row->image,
        );
        $rowArticle = $modelArticle->createRow($article);
        $rowArticle->save();
        $articleId = $rowArticle->id;
        $refreshArticle = false;
        
        // Moving extended fields to extended table
        $modelExtended  = $this->getModel('extended');
        $extended       = array(
            'article'         => $articleId,
            'seo_title'       => $row->seo_title,
            'seo_keywords'    => $row->seo_keywords,
            'seo_description' => $row->seo_description,
            'slug'            => $row->slug ?: null,
        );
        $rowExtended = $modelExtended->createRow($extended);
        $rowExtended->save();
        
        // Compiled article content
        $modelCompiled   = $this->getModel('compiled');
        $compiledType    = $this->config('compiled_type') ?: 'html';
        $compiledContent = Compiled::compiled($rowArticle->markup, $rowArticle->content, $compiledType);
        $compiled        = array(
            'name'            => $articleId . '-' . $compiledType,
            'article'         => $articleId,
            'type'            => $compiledType,
            'content'         => $compiledContent,
        );
        $rowCompiled     = $modelCompiled->createRow($compiled);
        $rowCompiled->save();

        // Transform foreign images
        $content = Service::transformArticleImages($row->content, $module);
        if ($content) {
            $rowArticle->content = $content;
            $refreshArticle      = true;
        }

        // Move asset
        /*$modelDraftAsset     = $this->getModel('draft_asset');
        $resultsetDraftAsset = $modelDraftAsset->select(array(
            'draft' => $id,
        ));
        $modelAsset = $this->getModel('asset');
        foreach ($resultsetDraftAsset as $asset) {
            $data = array(
                'original_name' => $asset->original_name,
                'name'          => $asset->name,
                'extension'     => $asset->extension,
                'size'          => $asset->size,
                'mime_type'     => $asset->mime_type,
                'type'          => $asset->type,
                'path'          => $asset->path,
                'time_create'   => $asset->time_create,
                'user'          => $asset->user,
                'article'       => $articleId,
            );
//                    $data['path'] = Upload::moveTmpToAsset($attachment->path, $module, $attachment->type);
            $rowAsset = $modelAsset->createRow($data);
            $rowAsset->save();
        }*/

        // Clear draft assets info
        //$modelDraftAsset->delete(array('draft' => $id));

        if ($refreshArticle) {
            $rowArticle->save();
        }

        // Save tag
        if ($this->config('enable_tag') && !empty($row->tag)) {
            Pi::service('tag')->add($module, $articleId, null, $row->tag, $timestamp);
        }

        // Save related articles
        $relatedArticles = $row->related;
        if ($relatedArticles) {
            $relatedModel = $this->getModel('related');
            $relatedModel->saveRelated($articleId, $relatedArticles);
        }

        // delete draft
        $row->delete();

        $result['status']   = self::RESULT_TRUE;
        $result['data']['redirect'] = $this->url('', array('action' => 'published', 'controller' => 'article'));

        return $result;
    }

    /**
     * Updating published article data
     * 
     * @param  int $id  Article ID
     * @return array 
     */
    protected function update($id)
    {
        $result = array(
            'status'    => self::RESULT_FALSE,
            'message'   => array(),
            'data'      => array(),
        );

        if (!$id) {
            $result['message'] = __('Not enough parameter.');
        }
        
        $modelDraft = $this->getModel('draft');
        $rowDraft   = $modelDraft->find($id);

        if (!$rowDraft->id or !$rowDraft->article) {
            $result['message'] = __('Invalid draft.');
        }
        
        $module         = $this->getModule();
        $modelArticle   = $this->getModel('article');

        // Update draft to article
        $articleId = $rowDraft->article;
        $timestamp = time();

        // Transform foreign images in content
        $content = Service::transformArticleImages($rowDraft->content, $module);

        $article = array(
            'subject'         => $rowDraft->subject,
            'subtitle'        => $rowDraft->subtitle,
            'summary'         => $rowDraft->summary,
            'content'         => $content ?: $rowDraft->content,
            'uid'             => $rowDraft->uid,
            'author'          => $rowDraft->author,
            'source'          => $rowDraft->source,
            'pages'           => $rowDraft->pages,
            'time_submit'     => $rowDraft->time_submit,
            'time_publish'    => $rowDraft->time_publish,
            'time_update'     => $rowDraft->time_update > $timestamp ? $rowDraft->time_update : $timestamp,
            'user_update'     => Pi::registry('user')->id,
        );
        $rowArticle = $modelArticle->find($articleId);
        $rowArticle->assign($article);
        $rowArticle->save();
        
        // Compiled article content
        $modelCompiled   = $this->getModel('compiled');
        $compiledType    = $this->config('compiled_type') ?: 'html';
        $compiledContent = Compiled::compiled($rowArticle->markup, $rowArticle->content, $compiledType);
        $name            = $articleId . '-' . $compiledType;
        $compiled        = array(
            'name'            => $name,
            'article'         => $articleId,
            'type'            => $compiledType,
            'content'         => $compiledContent,
        );
        $rowCompiled     = $modelCompiled->find($name, 'name');
        if ($rowCompiled->id) {
            $rowCompiled->assign($compiled);
            $rowCompiled->save();
        } else {
            $rowCompiled     = $modelCompiled->createRow($compiled);
            $rowCompiled->save();
        }
        
        // Updating value of extended fields
        $extended = array(
            'seo_title'       => $rowDraft->seo_title,
            'seo_keywords'    => $rowDraft->seo_keywords,
            'seo_description' => $rowDraft->seo_description,
            'slug'            => $rowDraft->slug ?: null,
        );
        $modelExtended = $this->getModel('extended');
        $rowExtended   = $modelExtended->find($articleId, 'article');
        $rowExtended->assign($extended);
        $rowExtended->save();

        // Move feature image
        if (strcmp($rowDraft->image, $rowArticle->image) != 0) {
            if ($rowArticle->image) {
                $image = Pi::path($rowArticle->image);

                unlink($image);
                unlink(Upload::getThumbFromOriginal($image));
            }

            $rowArticle->image = $rowDraft->image;
            $rowArticle->save();
        }

        // Merge assets
        /*$draftAssetFiles     = $articleAssetFiles = $diffDraft = $diffArticle = array();
        $modelAsset          = $this->getModel('asset');
        $modelDraftAsset     = $this->getModel('draft_asset');
        $resultsetDraftAsset = $modelDraftAsset->select(array(
            'draft' => $id,
        ));
        foreach ($resultsetDraftAsset as $asset) {
            $draftAssetFiles[$asset->name] = $asset;

            // Add new assets
            if (!$asset->published) {
                $data = array(
                    'original_name' => $asset->original_name,
                    'name'          => $asset->name,
                    'extension'     => $asset->extension,
                    'size'          => $asset->size,
                    'type'          => $asset->type,
                    'path'          => $asset->path,
                    'time_create'   => $asset->time_create,
                    'user'          => $asset->user,
                    'article'       => $articleId,
                );
//                        $data['path'] = Upload::moveTmpToAsset($attachment['path'], $module, $attachment['type']);
                $rowAsset = $modelAsset->createRow($data);
                $rowAsset->save();
            }
        }

        $resultsetAsset = $modelAsset->select(array(
            'article' => $articleId,
        ));
        foreach ($resultsetAsset as $asset) {
            $articleAssetFiles[$asset->name] = $asset;
        }

        // Assets need to remove
        $needToDelete = array();
        $diffArticle  = array_diff(array_keys($articleAssetFiles), array_keys($draftAssetFiles));
        foreach ($diffArticle as $key) {
            $asset = $articleAssetFiles[$key];

            unlink(Pi::path($asset->path));
            if (Asset::FIELD_TYPE_IMAGE == $asset->type) {
                unlink(Pi::path(Upload::getThumbFromOriginal($asset->path)));
            }

            $needToDelete[] = $asset->id;
        }
        if ($needToDelete) {
            $modelAsset->delete(array('id' => $needToDelete));
        }

        // Clear draft assets
        $modelDraftAsset->delete(array('draft' => $id));*/

        // Save tag
        if ($this->config('enable_tag')) {
            Pi::service('tag')->update($module, $articleId, null, $rowDraft->tag, $timestamp);
        }

        // Save related articles
        $relatedArticles = $rowDraft->related;
        if ($relatedArticles) {
            $modelRelated = $this->getModel('related');
            $modelRelated->saveRelated($articleId, $relatedArticles);
        }

        // Delete draft
        $rowDraft->delete();

        $result['status']   = self::RESULT_TRUE;
        $result['data']['redirect'] = $this->url('', array('action' => 'published', 'controller' => 'article'));
        $result['message']= __('Article update successfully.');

        return $result;
    }

    /**
     * Saving new article into draft table, and the status of article will be draft.
     * 
     * @param array  $data  Posted article details
     * @return boolean 
     */
    protected function saveDraft($data)
    {
        $rowDraft   = $id = $fakeId = null;
        $module     = $this->getModule();
        $modelDraft = $this->getModel('draft');

        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
        }

        $fakeId = Service::getParam($this, 'fake_id', 0);

        unset($data['article']);
        unset($data['image']);

        if ($this->config('enable_summary') && !$data['summary']) {
            $data['summary'] = Service::generateArticleSummary($data['content'], $this->config('max_summary_length'));
        }

        $pages = Service::breakPage($data['content']);
        $data['pages'] = count($pages);

        $data['time_publish'] = $data['time_publish'] ? strtotime($data['time_publish']) : 0;
        $data['time_update']  = $data['time_update'] ? strtotime($data['time_update']) : 0;
        $data['time_submit']  = $data['time_submit'] ? strtotime($data['time_submit']) : 0;
        $data['time_save']    = time();

        if (isset($data['related'])) {
            $data['related'] = array_filter(explode(self::TAG_DELIMITER, $data['related']));
        }

        if (isset($data['tag'])) {
            $data['tag']     = array_filter(explode(self::TAG_DELIMITER, $data['tag']));
        }

        if (empty($id)) {
            $data['uid']    = Pi::registry('user')->id;
            $data['status'] = Draft::FIELD_STATUS_DRAFT;

            $rowDraft = $modelDraft->createRow($data);
            $rowDraft->save();

            if (empty($rowDraft->id)) {
                return false;
            }
            $id = $rowDraft->id;
        } else {
            if (isset($data['status'])) {
                unset($data['status']);
            }

            $rowDraft = $modelDraft->find($id);

            if (empty($rowDraft)) {
                return false;
            }

            $rowDraft->assign($data);
            $rowDraft->save();
        }

        // Save image
        $session    = Upload::getUploadSession($module);
        if (isset($session->$id) || ($fakeId && isset($session->$fakeId))) {
            $uploadInfo = isset($session->$id) ? $session->$id : $session->$fakeId;

            if ($uploadInfo) {
                $rowDraft->image = $uploadInfo['tmp_name'];
                $rowDraft->save();
            }

            unset($session->$id);
            unset($session->$fakeId);
        }

        // Update assets linked to fake id
        /*if ($fakeId) {
            $this->getModel('draft_asset')->update(
                array('draft' => $id),
                array('draft' => $fakeId)
            );
        }*/

        return $id;
    }
    
    /**
     * Getting list articles by condition
     * 
     * @param int     $status   Draft status flag
     * @param string  $from     Show all articles or my articles
     * @param array   $options  Where condition
     */
    public function showDraftPage($status, $from = 'my', $options = array())
    {
        $where  = array();
        $page   = Service::getParam($this, 'page', 1);
        $limit  = Service::getParam($this, 'limit', 20);

        $where['status']        = $status;
        $where['article < ?']   = 1;
        if ('my' == $from) {
            $where['uid']       = Pi::registry('user')->id;
        }
        if (isset($options['keyword'])) {
            $where['subject like ?'] = sprintf('%%%s%%', $options['keyword']);
        }

        $module         = $this->getModule();
        $modelDraft     = $this->getModel('draft');

        $resultsetDraft = Service::getDraftPage($where, $page, $limit, null, null, $module);

        // Total count
        $totalCount     = (int) $modelDraft->getSearchRowsCount($where);

        // Paginator
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
                  ->setCurrentPageNumber($page)
                  ->setUrlOptions(array(
                    'pageParam' => 'page',
                    'router'    => $this->getEvent()->getRouter(),
                    'route'     => $this->getEvent()->getRouteMatch()->getMatchedRouteName(),
                    'params'    => array(
                        'module'        => $module,
                        'controller'    => 'draft',
                        'action'        => $this->getEvent()->getRouteMatch()->getParam('action'),
                        'status'        => $status,
                        'from'          => $from,
                        'where'         => urlencode(json_encode($options)),
                        'limit'         => $limit,
                    ),
                ));

        $this->view()->assign(array(
            'data'      => $resultsetDraft,
            'paginator' => $paginator,
            'status'    => $status,
            'from'      => $from,
            'page'      => $page,
            'limit'     => $limit,
        ));
    }
    
    /**
     * Processing article saving.
     * 
     * @return ViewModel 
     */
    public function saveAction()
    {
        $result = array(
            'status'    => self::RESULT_FALSE,
            'message'   => array(),
            'data'      => array(),
        );
        $id = 0;

        $options = Service::getFormConfig();
        $form    = $this->getDraftForm('save', $options);
        $form->setData($this->request->getPost());
        $form->setInputFilter(new DraftEditFilter($options));
        $form->setValidationGroup(Draft::getValidFields());
        
        if (!$form->isValid()) {
            return array(
                'message' => $form->getMessages(),
            );
        }
        
        $data = $form->getData();
        $id   = $this->saveDraft($data);
        if (!$id) {
            return array(
                'message' => __('Failed to save draft.'),
            );
        }
        
        $result['status']   = self::RESULT_TRUE;
        $result['data']     = array('id' => $id);

        $route = '.' . Service::getRouteName();
        $result['data']['preview_url'] = $this->url($route, array('time' => date('Ymd', time()), 'id' => $id, 'preview' => 1));
        $result['message'] = __('Draft saved successfully.');

        return $result;
    }

    /**
     * Default action. 
     */
    public function indexAction()
    {
        // @todo use transaction
    }
    
    /**
     * Listing articles 
     */
    public function listAction()
    {
        $status = Service::getParam($this, 'status', Draft::FIELD_STATUS_DRAFT);
        $from   = Service::getParam($this, 'from', 'my');
        $where  = Service::getParam($this, 'where', '');
        $where  = json_decode(urldecode($where), true);
        if (!in_array($from, array('my', 'all'))) {
            throw new \Exception(__('Invalid source'));
        }
        
        $this->showDraftPage($status, $from, $where);
        
        $title  = '';
        switch ($status) {
            case Draft::FIELD_STATUS_DRAFT:
                $title = __('Draft');
                $name  = 'draft';
                break;
            case Draft::FIELD_STATUS_PENDING:
                $title = __('Pending');
                $name  = 'pending';
                break;
            case Draft::FIELD_STATUS_REJECTED:
                $title = __('Rejected');
                $name  = 'rejected';
                break;
        }
        $flags = array(
            'draft'     => Draft::FIELD_STATUS_DRAFT,
            'pending'   => Draft::FIELD_STATUS_PENDING,
            'rejected'  => Draft::FIELD_STATUS_REJECTED,
            'published' => \Module\Article\Model\Article::FIELD_STATUS_PUBLISHED,
        );

        $this->view()->assign(array(
            'title'   => $title,
            'summary' => Service::getSummary($from),
            'flags'   => $flags,
        ));
        
        if ('all' == $from) {
            $template = sprintf('%s-%s', 'article', $name);
            $this->view()->setTemplate($template);
        }
    }
    
    /**
     * Processing article adding
     * 
     * @return ViewModel 
     */
    public function addAction()
    {
        $options = Service::getFormConfig();
        $form    = $this->getDraftForm('add', $options);

        $form->setData(array(
            'category'      => $this->config('default_category'),
            'source'        => $this->config('default_source'),
            'fake_id'       => Upload::randomKey(),
            'uid'           => Pi::registry('user')->id,
        ));

        $this->setModuleConfig();
        $this->view()->assign(array(
            'title'     => __('Create Article'),
            'form'      => $form,
            'config'    => Pi::service('module')->config('', $this->getModule()),
            'elements'  => $options['elements'],
        ));
        $this->view()->setTemplate('draft-edit');
    }
    
    /**
     * Processing article editing
     * 
     * @return ViewModel 
     */
    public function editAction()
    {
        $id       = Service::getParam($this, 'id', 0);
        $module   = $this->getModule();
        $options  = Service::getFormConfig();
        $elements = $options['elements'];

        if (!$id) {
            return ;
        }
        
        $draftModel = $this->getModel('draft');
        $row        = $draftModel->find($id);

        /**#@+
        * Added by Zongshu Lin
        */
        /*$status = '';
        switch ((int) $row->status) {
            case 1:
                $status = 'draft';
                break;
            case 2:
                $status = 'pending';
                break;
            case 3:
                $status = 'rejected';
                break;
        }
        if ('all' === Service::getParam($this, 'from')) {
            $role   = PermController::PERM_EDITOR;
            $status = 'draft' === $status ? 'published' : $status;
        } else {
            $role = PermController::PERM_WRITER;
        }
        $rules    = Role::getAllowedResources($this, $role, $status);
        if (PermController::PERM_WRITER === $role) {
            $isAllowed = Role::isAllowed(PermController::PERM_EDITOR, 'pending-approve-reject');
            $isEditor  = Pi::service('api')->channel(array('role', 'isEditor'), null, $row->channel);
            $acl       = new \Pi\Acl\Acl('admin');
            if ('admin' === $acl->getRole()) {
                $rules['approve-reject'] = true;
            } else {
                $rules['approve-reject'] = ($isAllowed and $isEditor);
            }
        }
        $channels = Pi::service('api')->channel(array('role', 'getListByRole'), $role);
        foreach ($channels as $key => $channel) {
            $channels[$key] = $channel['title'];
        }
        if (!$rules['edit'] or !in_array($row->channel, array_keys($channels))) {
            return $this->jumpToDenied('__denied__');
        }*/
        /**#@-*/

        if (empty($row)) {
            return ;
        }
        
        // prepare data
        $data                 = $row->toArray();
        $data['category']     = $data['category'] ?: $this->config('default_category');
        $data['related']      = $data['related'] ? implode(self::TAG_DELIMITER, $data['related']) : '';
        $data['tag']          = $data['tag'] ? implode(self::TAG_DELIMITER, $data['tag']) : '';

        $data['time_publish'] = $data['time_publish'] ? date('Y-m-d H:i:s', $data['time_publish']) : '';
        $data['time_update']  = $data['time_update'] ? date('Y-m-d H:i:s', $data['time_update']) : '';

        $featureImage = $data['image'] ? Pi::url($data['image']) : '';
        $featureThumb = $data['image'] ? Pi::url(Upload::getThumbFromOriginal($data['image'])) : '';

        $form = $this->getDraftForm('edit', $options);
        /**#@+
        * Added by Zongshu Lin
        */
        /*$channels = array(0 => __('Select channel')) + $channels;
        $form->get('channel')->setValueOptions($channels);*/
        /**#@-*/
        $form->setData($data);

        // Get author info
        if (isset($elements['author']) and $data['author']) {
            $author = $this->getModel('author')->find($data['author']);
            if ($author) {
                $this->view()->assign('author', array(
                    'id'   => $author->id,
                    'name' => $author->name,
                ));
            }
        }

        // Get submitter info
        if ($data['uid']) {
            $user = Pi::model('user')->find($data['user']);
            if ($user) {
                $this->view()->assign('user', array(
                    'id'   => $user->id,
                    'name' => $user->identity,
                ));
            }
        }

        // Get related articles
        if (isset($elements['related'])) {
            $related = $relatedIds = array();
            if (Article::FIELD_RELATED_TYPE_CUSTOM == $data['related_type'] && !empty($row->related)) {
                $relatedIds = array_flip($row->related);

                $related = Service::getArticlePage(array('id' => $row->related), 1, null, null, null, $module);

                foreach ($related as $item) {
                    if (array_key_exists($item['id'], $relatedIds)) {
                        $relatedIds[$item['id']] = $item;
                    }
                }

                $related = array_filter($relatedIds, function($var) {
                    return is_array($var);
                });
            }
        }

        // Get assets
        /*$attachments = $images = array();
        $resultsetDraftAsset = $this->getModel('draft_asset')->select(array(
            'draft' => $id,
        ));
        foreach ($resultsetDraftAsset as $asset) {
            if (Asset::FIELD_TYPE_ATTACHMENT == $asset->type) {
                $attachments[] = array(
                    'id'           => $asset->id,
                    'originalName' => $asset->original_name,
                    'size'         => $asset->size,
                    'delete_url'   => $this->url(
                        '',
                        array(
                            'controller' => 'ajax',
                            'action'     => 'remove.asset',
                            'id'         => $asset->id,
                        )
                    ),
                    'download_url' => $this->url(
                        '',
                        array(
                            'controller' => 'draft',
                            'action'     => 'download.asset',
                            'id'         => $asset->id,
                        )
                    ),
                );
            } else {
                $imageSize = getimagesize(Pi::path($asset->path));

                $images[] = array(
                    'id'           => $asset->id,
                    'originalName' => $asset->name,
                    'size'         => $asset->size,
                    'w'            => $imageSize['0'],
                    'h'            => $imageSize['1'],
                    'delete_url'   => $this->url(
                        '',
                        array(
                            'controller' => 'ajax',
                            'action'     => 'remove.asset',
                            'id'         => $asset->id,
                        )
                    ),
                    'preview_url' => Pi::url($asset->path),
                    'thumb_url'   => Pi::url(Upload::getThumbFromOriginal($asset->path)),
                );
            }
        }*/

        $this->setModuleConfig();
        $this->view()->assign(array(
            'title'         => __('Edit Article'),
            'form'          => $form,
            'draft'         => $row,
            'related'       => $related,
            'attachments'   => $attachments,
            'images'        => $images,
            'featureImage'  => $featureImage,
            'featureThumb'  => $featureThumb,
            'config'        => Pi::service('module')->config('', $module),
            'from'          => Service::getParam($this, 'from', ''),
            'elements'      => $elements,
            /**#@+
            * Added by Zongshu Lin
            */
            //'rules'         => $rules,
            /**#@-*/
        ));
    }

    /**
     * Processing draft deletion
     * 
     * @throws \Exception 
     */
    public function deleteAction()
    {
        $id     = Service::getParam($this, 'id', '');
        $ids    = array_filter(explode(',', $id));
        $from   = Service::getParam($this, 'from', '');

        if (empty($ids)) {
            throw new \Exception(__('Invalid draft id'));
        }
        
        Service::deleteDraft($ids, $this->getModule());

        if ($from) {
            $from = urldecode($from);
            $this->redirect()->toUrl($from);
        } else {
            // Go to list page
            $this->redirect()->toRoute('', array(
                'action'        => 'list',
                'controller'    => 'draft',
                'status'        => 'draft',
            ));
        }
        $this->view()->setTemplate(false);
    }

    /**
     * Processing article publishing
     * 
     * @return ViewModel
     */
    public function publishAction()
    {
        $result = array(
            'status'    => self::RESULT_FALSE,
            'message'   => array(),
            'data'      => array(),
        );

        $options = Service::getFormConfig();
        $form    = $this->getDraftForm('save', $options);
        $form->setInputFilter(new DraftEditFilter($options));
        $form->setValidationGroup(Draft::getValidFields());
        $form->setData($this->request->getPost());

        if (!$form->isValid()) {
            return array('message' => $form->getMessages());
        }
        
        $data     = $form->getData();
        $validate = $this->validateForm($data, null, $options['elements']);

        if (!$validate['status']) {
            $form->setMessages($validate['message']);
            return $validate;
        }
        
        $id = $this->saveDraft($data);
        if (!$id) {
            return array('message' => __('Failed to save draft.'));
        }
        
        $result = $this->publish($id);
        /**#@+
        * Added by Zongshu Lin
        */
        /*$allowApprove = Role::isAllowed(PermController::PERM_EDITOR, 'pending-approve-reject');
        $editor       = Pi::service('api')->channel(array('role', 'isEditor'), null, $data['channel']);
        $acl          = new \Pi\Acl\Acl('admin');
        if ('admin' === $acl->getRole()) {
            $result['data']['approve'] = true;
        } else {
            $result['data']['approve'] = ($allowApprove and $editor) ? true : false;
        }*/
        /**#@-*/

        return $result;
    }

    /**
     * Processing article rejected, article status will be changed to rejected
     * 
     * @return ViewModel 
     */
    public function rejectAction()
    {
        $result = array(
            'status'    => self::RESULT_TRUE,
            'message'   => array(),
            'data'      => array(),
        );

        $id           = Service::getParam($this, 'id', 0);
        $rejectReason = Service::getParam($this, 'memo', '');

        if (!$id) {
            return array('message' => __('Not enough parameter.'));
        }
        
        $model = $this->getModel('draft');
        $row   = $model->find($id);
        if (!$row->id or $row->status != Draft::FIELD_STATUS_PENDING) {
            return array('message' => __('Invalid draft.'));
        }
        
        $row->status        = Draft::FIELD_STATUS_REJECTED;
        $row->reject_reason = $rejectReason;
        $row->save();

        $result['status']   = self::RESULT_TRUE;
        $result['data']['redirect'] = $this->url('', array('action'=>'list', 'controller' => 'draft'));

        return $result;
    }

    /**
     * Processing article approving.
     * 
     * @return ViewModel 
     */
    public function approveAction()
    {
        /**#@+
        * Added by Zongshu Lin
        */
        /*$rules = Role::isAllowed(PermController::PERM_EDITOR, 'pending-approve-reject');
        if (!isset($rules)) {
            return array(
                'status'   => PermController::AJAX_RESULT_FALSE,
                'message'  => PermController::DENIED_MESSAGE,
            );
        }*/
        /**#@-*/
        
        $result = array(
            'status'    => self::RESULT_FALSE,
            'message'   => array(),
            'data'      => array(),
        );

        $options = Service::getFormConfig();
        $form    = $this->getDraftForm('save', $options);
        $form->setInputFilter(new DraftEditFilter($options));
        $form->setValidationGroup(Draft::getValidFields());
        $form->setData($this->request->getPost());

        if (!$form->isValid()) {
            return array('message' => $form->getMessages());
        }
        
        $data     = $form->getData();
        $validate = $this->validateForm($data, null, $options['elements']);

        if (!$validate['status']) {
            $form->setMessages($validate['message']);
            return $validate;
        }
        
        $id = $this->saveDraft($data);

        if (!$id) {
            return array('message' => __('Failed to save draft.'));
        }
        
        $result = $this->approve($id, $options['elements']);
        $result['message'] = __('approve successfully.');

        return $result;
    }

    /**
     * Processing batch article approving 
     */
    public function batchApproveAction()
    {
        $id     = Service::getParam($this, 'id', '');
        $ids    = array_filter(explode(',', $id));
        $from   = Service::getParam($this, 'from', '');

        $options = Service::getFormConfig();
        if ($ids) {
            foreach ($ids as $id) {
                $this->approve($id, $options['elements']);
            }
        }

        if ($from) {
            $from = urldecode($from);
            $this->redirect()->toUrl($from);
        } else {
            // Go to list page
            $this->redirect()->toRoute('', array('controller' =>'article', 'action' => 'published'));
        }
    }

    public function previewAction()
    {
        $id = Service::getParam($this, 'id', 0);

        $this->redirect()->toRoute('', array(
            'module'     => $this->getModule(),
            'controller' => 'preview',
            'action'     => 'single-page',
            'id'         => $id,
        ));
        $this->view()->setTemplate(false);
    }

    /**
     * Processing published article updating
     * 
     * @return ViewModel 
     */
    public function updateAction()
    {
        $result = array(
            'status'    => self::RESULT_FALSE,
            'message'   => array(),
            'data'      => array(),
        );

        $options = Service::getFormConfig();
        $form    = $this->getDraftForm('save', $options);
        $form->setInputFilter(new DraftEditFilter($options));
        $form->setValidationGroup(Draft::getValidFields());
        $form->setData($this->request->getPost());

        if (!$form->isValid()) {
            return array('message' => $form->getMessages());
        }
        
        $data     = $form->getData();
        $validate = $this->validateForm($data, Service::getParam($this, 'article', 0), $options['elements']);

        if (!$validate['status']) {
            $form->setMessages($validate['message']);
            return $validate;
        }
        
        $id = $this->saveDraft($data);
        if (!$id) {
            return array('message', __('Failed to save draft.'));
        }
        $result = $this->update($id);

        return $result;
    }
}
