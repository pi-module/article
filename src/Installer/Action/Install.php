<?php
/**
 * Article module install file
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
 * @subpackage      Installer\Action
 */

namespace Module\Article\Installer\Action;

use Pi;
use Pi\Application\Installer\Action\Install as BasicInstall;
use Zend\EventManager\Event;
use Module\Article\Service;
use Module\Article\File;

/**
 * Class for custom install 
 */
class Install extends BasicInstall
{
    /**
     * Attaching method to listener.
     * 
     * @return \Module\Article\Installer\Action\Install 
     */
    protected function attachDefaultListeners()
    {
        $events = $this->events;
        $events->attach('install.post', array($this, 'initCategory'), 1);
        $events->attach('install.post', array($this, 'initDraftEditPageForm'), -90);
        parent::attachDefaultListeners();
        return $this;
    }
    
    /**
     * Adding a root category, and its child as default category.
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
            'description' => __('The default category can not be delete, by can be modified!'),
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
return array(
    'mode'     => 'extension',
);
EOD;
        $filename = Service::getModuleConfigPath('draft-edit-form', $module);
        $result   = File::addContent($filename, $content);
        
        $e->setParam('result', $result);
    }
}
