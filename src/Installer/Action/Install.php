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
        parent::attachDefaultListeners();
        return $this;
    }
    
    /**
     * Adding a root category.
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
        
        $e->setParam('result', $result);
    }
}
