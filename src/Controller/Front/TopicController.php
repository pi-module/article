<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link         http://code.pialog.org for the Pi Engine source repository
 * @copyright    Copyright (c) Pi Engine http://pialog.org
 * @license      http://pialog.org/license.txt New BSD License
 */

namespace Module\Article\Controller\Front;

use Pi\Mvc\Controller\ActionController;
use Pi;
use Pi\Paginator\Paginator;
use Module\Article\Form\TopicEditForm;
use Module\Article\Form\TopicEditFilter;
use Module\Article\Form\SimpleSearchForm;
use Module\Article\Model\Topic;
use Zend\Db\Sql\Expression;
use Module\Article\Service;
use Module\Article\Model\Article;
use Module\Article\Entity;
use Module\Article\Topic as TopicService;
use Pi\File\Transfer\Upload as UploadHandler;

/**
 * Topic controller
 * 
 * Feature list:
 * 
 * 1. Homepage of a certain topic
 * 2. Article list of a certain topic
 * 3. All topic list
 * 
 * @author Zongshu Lin <lin40553024@163.com>
 */
class TopicController extends ActionController
{
    /**
     * Homepage of a topic
     * 
     * @return ViewModel
     */
    public function indexAction()
    {
        $topic   = Service::getParam($this, 'topic', '');
        if (empty($topic)) {
            return $this->jumpTo404(__('Invalid topic ID!'));
        }
        if (is_numeric($topic)) {
            $row = $this->getModel('topic')->find($topic);
        } else {
            $row = $this->getModel('topic')->find($topic, 'slug');
        }
        if (!$row->id) {
            return $this->jumpTo404(__('Topic is not exists!'));
        }
        // Return 503 code if topic is not active
        if (!$row->active) {
            return $this->jumpToException(
                __('The topic requested is not active'),
                503
            );
        }
        
        // Get topic articles
        $rowRelations = $this->getModel('article_topic')
                             ->select(array('topic' => $row->id));
        $articleIds   = array();
        foreach ($rowRelations as $relation) {
            $articleIds[] = $relation->article;
        }
        $articleIds   = array_filter($articleIds);
        if (!empty($articleIds)) {
            $where    = array(
                'id'  => $articleIds,
            );
            $articles = Entity::getAvailableArticlePage($where, null, null);
        }
        
        $this->view()->assign(array(
            'content'   => $row->content,
            'title'     => $row->title,
            'image'     => Pi::url($row->image),
            'articles'  => $articles,
        ));
        
        $template = ('default' == $row->template)
            ? 'topic-index' : 'topic-custom-' . $row->template;
        $this->view()->setTemplate($template);
    }
    
    /**
     * Topic list page for viewing 
     */
    public function allTopicAction()
    {
        $page       = Service::getParam($this, 'p', 1);
        $page       = $page > 0 ? $page : 1;

        $config = Pi::service('module')->config('', $module);
        $limit  = (int) $config['page_topic_front'] ?: 20;
        
        $where = array(
            'active' => 1,
        );
        
        // Get topics
        $resultsetTopic = TopicService::getTopics($where, $page, $limit);
        
        // Get topic article counts
        $model  = $this->getModel('article_topic');
        $select = $model->select()
                        ->columns(array(
                            'count' => new Expression('count(id)'), 'topic'))
                        ->group(array('topic'));
        $rowRelation   = $model->selectWith($select);
        $articleCount  = array();
        foreach ($rowRelation as $row) {
            $articleCount[$row->topic] = $row->count;
        }

        // Total count
        $modelTopic = $this->getModel('topic');
        $totalCount = $modelTopic->getSearchRowsCount($where);

        // Pagination
        $route     = $this->getModule() . '-' . Service::getRouteName();
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
                  ->setCurrentPageNumber($page)
                  ->setUrlOptions(array(
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $route,
                'params'    => array(
                    'topic'      => 'all',
                ),
            ));

        $this->view()->assign(array(
            'title'         => __('All Topics'),
            'topics'        => $resultsetTopic,
            'paginator'     => $paginator,
            'count'         => $articleCount,
        ));
    }
    
    /**
     * list articles of a topic for users to view
     */
    public function listAction()
    {
        $topic   = Service::getParam($this, 'topic', '');
        if (empty($topic)) {
            return $this->jumpTo404(__('Invalid topic ID!'));
        }
        if (is_numeric($topic)) {
            $row = $this->getModel('topic')->find($topic);
        } else {
            $row = $this->getModel('topic')->find($topic, 'slug');
        }
        $title = $row->title;
        // Return 503 code if topic is not active
        if (!$row->active) {
            return $this->jumpToException(
                __('The topic requested is not active'),
                503
            );
        }
        
        $this->view()->assign('topic', $row->toArray());
        
        $topicId    = $row->id;
        $page       = Service::getParam($this, 'p', 1);
        $page       = $page > 0 ? $page : 1;

        $module = $this->getModule();
        $config = Pi::service('module')->config('', $module);
        $limit  = (int) $config['page_topic_front'] ?: 20;
        
        // Getting relations
        $modelRelation = $this->getModel('article_topic');
        $rowRelation   = $modelRelation->select(array('topic' => $topicId));
        $articleIds    = array();
        $lastAdded     = 0;
        foreach ($rowRelation as $row) {
            $articleIds[] = $row['article'];
            if ($row['time'] > $lastAdded) {
                $lastAdded = $row['time'];
            }
        }
        
        $where = array(
            'id' => $articleIds,
        );
        
        // Get articles
        $resultsetArticle = Entity::getAvailableArticlePage(
            $where,
            $page,
            $limit
        );

        // Total count
        $where = array_merge($where, array(
            'time_publish <= ?' => time(),
            'status'            => Article::FIELD_STATUS_PUBLISHED,
            'active'            => 1,
        ));
        $modelArticle   = $this->getModel('article');
        $totalCount     = $modelArticle->getSearchRowsCount($where);

        // Pagination
        $route     = $this->getModule() . '-' . Service::getRouteName();
        $paginator = Paginator::factory($totalCount);
        $paginator->setItemCountPerPage($limit)
            ->setCurrentPageNumber($page)
            ->setUrlOptions(array(
                'router'    => $this->getEvent()->getRouter(),
                'route'     => $route,
                'params'    => array(
                    'topic'      => $topic,
                    'list'       => 'all',
                ),
            ));

        $this->view()->assign(array(
            'title'         => empty($topic) ? __('All') : $title,
            'articles'      => $resultsetArticle,
            'paginator'     => $paginator,
            'lastAdded'     => $lastAdded,
            'count'         => $totalCount,
        ));
    }
}
