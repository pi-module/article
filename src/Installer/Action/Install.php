<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link         http://code.pialog.org for the Pi Engine source repository
 * @copyright    Copyright (c) Pi Engine http://pialog.org
 * @license      http://pialog.org/license.txt New BSD License
 */

namespace Module\Article\Installer\Action;

use Pi;
use Pi\Application\Installer\Action\Install as BasicInstall;
use Zend\EventManager\Event;
use Module\Article\Service;
use Module\Article\File;

/**
 * Custom install class
 * 
 * @author Zongshu Lin <lin40553024@163.com>
 */
class Install extends BasicInstall
{
    /**
     * Attach method to listener
     * 
     * @return \Module\Article\Installer\Action\Install 
     */
    protected function attachDefaultListeners()
    {
        $events = $this->events;
        $events->attach('install.post', array($this, 'initCategory'), 1);
        $events->attach(
            'install.post',
            array($this, 'initDraftEditPageForm'),
            -90
        );
        $events->attach(
            'install.post',
            array($this, 'initDefaultTopicTemplateScreenshot'),
            -90
        );
        parent::attachDefaultListeners();
        return $this;
    }
    
    /**
     * Add a root category, and its child as default category
     * 
     * @param Event $e 
     */
    public function initCategory(Event $e)
    {
        $module = $this->event->getParam('module');
        $model  = Pi::model('category', $module);
        $data   = array(
            'id'          => null,
            'name'        => 'root',
            'slug'        => 'root',
            'title'       => __('Root'),
            'description' => __('Module root category'),
        );
        $result = $model->add($data);
        $defaultCategory = array(
            'id'          => null,
            'name'        => 'default',
            'slug'        => 'slug',
            'title'       => __('Default'),
            'description' => __('The default category can not be delete, but can be modified!'),
        );
        $parent = $model->select(array('name' => 'root'))->current();
        $itemId = $model->add($defaultCategory, $parent);
        
        $e->setParam('result', $result);
    }
    
    /**
     * Add a config file to initilize draft edit page form type as extended.
     * 
     * @param Event $e 
     */
    public function initDraftEditPageForm(Event $e)
    {
        $module = $this->event->getParam('module');
        $content  =<<<EOD
<?php
return array(
    'mode'     => 'extension',
);
EOD;
        $filename = Service::getModuleConfigPath('draft-edit-form', $module);
        $result   = File::addContent($filename, $content);
        
        $e->setParam('result', $result);
    }
    
    /**
     * Add a folder in static folder and copy the default topic template
     * screenshot into this folder.
     * 
     * @param Event $e 
     */
    public function initDefaultTopicTemplateScreenshot(Event $e)
    {
        $module = $this->event->getParam('module');
        
        // Create folder in static folder
        $destFilename = sprintf(
            '%s/%s/topic-template',
            Pi::path('static'),
            $module
        );
        
        $result = true;
        if (!file_exists($destFilename)) {
            $result = File::mkdir($destFilename);
        }
        
        // Copy screenshot into target folder
        if ($result) {
            chmod($destFilename, 0777);
            $config = Pi::service('module')->config('', $module);
            $basename = $config['default_topic_template_image'];
            $srcFilename = sprintf(
                '%s/article/asset/%s',
                Pi::path('module'),
                $basename
            );
            
            if (file_exists($srcFilename)) {
                $result = copy(
                    $srcFilename,
                    $destFilename . '/' . basename($basename)
                );
            }
        }
        
        $e->setParam('result', $result);
    }
}
