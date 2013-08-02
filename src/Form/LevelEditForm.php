<?php
/**
 * Article module LevelEditForm form
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
 * Initializing level form 
 */
class LevelEditForm extends BaseForm
{
    /**
     * Initializing form 
     */
    public function init()
    {
        $this->add(array(
            'name'       => 'name',
            'options'    => array(
                'label'       => __('Name'),
            ),
            'attributes' => array(
                'type'        => 'text',
                'description' => __('The unique identifier of level. REQUIRED!'),
            ),
        ));
        
        $this->add(array(
            'name'       => 'title',
            'options'    => array(
                'label'       => __('Title'),
            ),
            'attributes' => array(
                'type'        => 'text',
                'description' => __('Will be displayed on your website.'),
            ),
        ));
        
        $this->add(array(
            'name'       => 'description',
            'options'    => array(
                'label'       => __('Description'),
            ),
            'attributes' => array(
                'type'        => 'textarea',
                'description' => __('Display in the website depends on theme.'),
            ),
            
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
