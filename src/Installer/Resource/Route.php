<?php
/**
 * Article module route installer resource
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

namespace Module\Article\Installer\Resource;

use Pi\Application\Installer\Resource\Route as BasicRoute;
use Pi;

/**
 * Class for installing route resource self-defined
 */
class Route extends BasicRoute
{
    /**
     * Route resource file 
     */
    const RESOURCE_CONFIG_NAME = 'resource.route.php';
    
    /**
     * Installing route resource
     * 
     * @return bool 
     */
    public function installAction()
    {
        $module     = $this->event->getParam('module');
        $filename   = sprintf('var/%s/config/%s', $module, self::RESOURCE_CONFIG_NAME);
        $configPath = Pi::path($filename);
        if (file_exists($configPath)) {
            $configs      = include $configPath;
            $class        = '';
            foreach ($configs as $config) {
                $class    = $config['type'];
                break;
            }
            if (class_exists($class)) {
                $this->config = $configs;
            }
        }
        
        return parent::installAction();
    }

    /**
     * Updating route resource
     * 
     * @return bool 
     */
    public function updateAction()
    {
        $module     = $this->event->getParam('module');
        $filename   = sprintf('var/%s/config/%s', $module, self::RESOURCE_CONFIG_NAME);
        $configPath = Pi::path($filename);
        if (file_exists($configPath)) {
            $configs      = include $configPath;
            $class        = '';
            foreach ($configs as $config) {
                $class    = $config['type'];
                break;
            }
            if (class_exists($class)) {
                $this->config = $configs;
            }
        }
        
        return parent::updateAction();
    }
}
