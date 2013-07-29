<?php
/**
 * Article module AuthorEditForm form
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
use Pi\Form\Form as BaseForm;

/**
 * Class for initializing form of add author page
 */ 
class AuthorEditForm extends BaseForm
{
    /**
     * Initalizing form 
     */
    public function init()
    {
        $this->add(array(
            'name'       => 'name',
            'options'    => array(
                'label' => __('Name'),
            ),
            'attributes' => array(
                'id'   => 'name',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name' => 'photo',
            'options' => array(
                'label' => __('Photo'),
            ),
            'attributes' => array(
            ),
        ));

        $this->add(array(
            'name' => 'description',
            'options' => array(
                'label' => 'Biography',
            ),
            'attributes' => array(
                'id'   => 'bio',
                'type' => 'textarea',
            ),
        ));

        $this->add(array(
            'name'  => 'x',
            'attributes' => array(
                'id'   => 'x',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name'  => 'y',
            'attributes' => array(
                'id'   => 'y',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name'  => 'w',
            'attributes' => array(
                'id'   => 'w',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name'  => 'h',
            'attributes' => array(
                'id'   => 'h',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name'  => 'security',
            'type'  => 'csrf',
        ));

        $this->add(array(
            'name'  => 'id',
            'attributes'    => array(
                'id'   => 'id',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name'  => 'fake_id',
            'attributes'    => array(
                'id'   => 'fake_id',
                'type' => 'hidden',
            ),
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
