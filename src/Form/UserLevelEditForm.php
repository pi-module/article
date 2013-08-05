<?php
/**
 * Article module UserLevelEditForm form
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

namespace Module\Article\Form;

use Pi;
use Pi\Form\Form as BaseForm;

/**
 * Initializing user level form 
 */
class UserLevelEditForm extends BaseForm
{
    /**
     * Initializing form 
     */
    public function init()
    {
        $this->add(array(
            'name'       => 'uid',
            'options'    => array(
                'label'       => __('User'),
            ),
            'attributes' => array(
                'description' => __('User account belong to article module'),
            ),
            'type'       => 'Module\Article\Form\Element\Account',
        ));
        
        $this->add(array(
            'name'       => 'category',
            'options'    => array(
                'label'       => __('Category'),
            ),
            'attributes' => array(
                'type'        => 'hidden',
                'description' => __('Categories allowed to manage'),
            ),
        ));
        
        $this->add(array(
            'name'       => 'level',
            'options'    => array(
                'label'       => __('Level'),
            ),
            'attributes' => array(
                'description' => __('Category level'),
            ),
            'type'       => 'Module\Article\Form\Element\Level',
        ));
        
        $this->add(array(
            'name'       => 'security',
            'type'       => 'csrf',
        ));

        $this->add(array(
            'name'       => 'id',
            'attributes' => array(
                'type'        => 'hidden',
            ),
        ));
        
        $this->add(array(
            'name'       => 'submit',
            'attributes' => array(                
                'value'       => __('Submit'),
            ),
            'type'       => 'submit',
        ));
    }
}
