<?php
/**
 * Article module DraftCustomForm form
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
use Module\Article\Controller\Admin\ConfigController;

/**
 * Initializing form 
 */
class DraftCustomForm extends BaseForm
{
    /**
     * Form elements in draft edit page
     * @var array 
     */
    protected $items = array();
    
    public function __construct($name, $options = array())
    {
        $this->items = isset($options['elements']) ? $options['elements'] : array();
        parent::__construct($name);
    }
    
    /**
     * Initializing form 
     */
    public function init()
    {
        $this->add(array(
            'name'       => 'mode',
            'options'    => array(
                'label'    => __('Form Mode'),
            ),
            'attributes' => array(
                'value'    => ConfigController::FORM_MODE_EXTENDED,
                'options'  => array(
                    ConfigController::FORM_MODE_NORMAL   => __('Normal'),
                    ConfigController::FORM_MODE_EXTENDED => __('Extended'),
                    ConfigController::FORM_MODE_CUSTOM   => __('Custom'),
                ),
            ),
            'type'       => 'radio',
        ));
        
        foreach ($this->items as $name => $title) {
            $this->add(array(
                'name'          => $name,
                'options'       => array(
                    'label'     => $title,
                ),
                'attributes'    => array(
                    'value'     => 0,
                ),
                'type'          => 'checkbox',
            ));
        }

        $this->add(array(
            'name'          => 'submit',
            'attributes'    => array(                
                'value' => __('Submit'),
            ),
            'type'  => 'submit',
        ));
    }
}
