<?php
/**
 * Article module CategoryMoveForm form
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

namespace Module\Article\Form;

use Pi;
use Pi\Form\Form as BaseForm;

class CategoryMoveForm extends BaseForm
{
    public function init()
    {
        $this->add(array(
            'name'       => 'from',
            'options'    => array(
                'label' => __('From'),
            ),
            'attributes' => array(
                'id'   => 'from',
            ),
            'type' => 'Module\Article\Form\Element\Category',
        ));

        $this->add(array(
            'name'       => 'to',
            'options'    => array(
                'label' => __('To'),
            ),
            'attributes' => array(
                'id'   => 'to',
            ),
            'type' => 'Module\Article\Form\Element\Category',
        ));

        $this->add(array(
            'name'  => 'security',
            'type'  => 'csrf',
        ));

        $this->add(array(
            'name'          => 'submit',
            'attributes'    => array(              
                'value' => __('Submit'),
            ),
            'type'  => 'submit',
        ));
    }
}
