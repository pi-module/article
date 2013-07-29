<?php
/**
 * Article module DraftCustomFilter filter
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
use Zend\InputFilter\InputFilter;
use Module\Article\Controller\Admin\ConfigController as Config;

/**
 * Class for verfying and filter form 
 */
class DraftCustomFilter extends InputFilter
{
    /**
     * Initializing validator and filter 
     */
    public function __construct($mode, $options = array())
    {
        $this->add(array(
            'name'     => 'mode',
            'required' => true,
            'filters'  => array(
                array(
                    'name' => 'StringTrim',
                ),
            ),
        ));
        
        if (Config::FORM_MODE_CUSTOM == $mode) {
            foreach ($options['needed'] as $element) {
                $this->add(array(
                    'name'       => $element,
                    'required'   => true,
                    'validators' => array(
                        array(
                            'name' => 'Module\Article\Validator\NotEmpty',
                        ),
                    ),
                ));
            }
        }
    }
}
