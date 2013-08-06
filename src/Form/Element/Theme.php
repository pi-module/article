<?php
/**
 * Article module theme element
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

class Theme extends Select
{
    public function getValueOptions()
    {
        if (empty($this->valueOptions)) {
            $rowset = Pi::model('theme')->select(array());
            $options = array();
            foreach ($rowset as $row) {
                $options[strtolower($row->name)] = ucfirst($row->name);
            }
            $this->valueOptions = $options;
        }

        return $this->valueOptions;
    }
}
