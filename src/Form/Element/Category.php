<?php
/**
 * Article module category element
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
 * @author          Lijun Dong <lijun@eefocus.com>
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article\Form\Element;

use Pi;
use Zend\Form\Element\Select;

/**
 * Class for creating select of all category
 */
class Category extends Select
{
    /**
     * Reading added category from database
     * 
     * @return array 
     */
    public function getValueOptions()
    {
        if (empty($this->valueOptions)) {
            $module = $this->getOption('module') ?: Pi::service('module')->current();
            $this->valueOptions = Pi::model('category', $module)->getSelectOptions(true);
        }

        return $this->valueOptions;
    }
}
