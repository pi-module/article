<?php
/**
 * Article module CategoryMergeFilter filter
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

namespace Module\Article\Form;

use Pi;
use Zend\InputFilter\InputFilter;

/**
 * Class for verifying and filtering form 
 */
class CategoryMergeFilter extends InputFilter
{
    /**
     * Initializing validator and filter 
     */
    public function __construct()
    {
        $this->add(array(
            'name'     => 'from',
            'required' => true,
        ));

        $this->add(array(
            'name'     => 'to',
            'required' => true,
        ));
    }
}
