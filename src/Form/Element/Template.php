<?php
/**
 * Article module template element
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
 * @subpackage      Form\Element
 */

namespace Module\Article\Form\Element;

use Pi;
use Zend\Form\Element\Select;

/**
 * Public class for defining custom select 
 */
class Template extends Select
{
    /**
     * Custom template path 
     */
    const TEMPLATE_PATH = 'article/template/front';
    
    /**
     * Custom template format 
     */
    const TEMPLATE_FORMAT = '/^topic-custom-(.+)/';
    
    /**
     * Resolving select options
     * 
     * @return array 
     */
    public function getValueOptions()
    {
        if (empty($this->valueOptions)) {
            $path      = sprintf('%s/%s', rtrim(Pi::path('module'), '/'), self::TEMPLATE_PATH);
            $iterator  = new \DirectoryIterator($path);
            $templates = array('default' => __('Default'));
            foreach ($iterator as $fileinfo) {
                if (!$fileinfo->isFile()) {
                    continue;
                }
                $filename = $fileinfo->getFilename();
                $name     = substr($filename, 0, strrpos($filename, '.'));
                if (!preg_match(self::TEMPLATE_FORMAT, $name, $matches)) {
                    continue;
                }
                $displayName = preg_replace('/[-_]/', ' ', $matches[1]);
                $templates[$name] = ucfirst($displayName);
            }
            $this->valueOptions = $templates;
        }

        return $this->valueOptions;
    }
}
