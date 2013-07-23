<?php
/**
 * Article module DraftEditFilter filter
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
use Module\Article\Form\DraftEditForm;

/**
 * Class for initializing validator and filter 
 */
class DraftEditFilter extends InputFilter
{
    /**
     * The mode of displaying for elements
     * @var string 
     */
    protected $mode = Config::FORM_MODE_EXTENDED;
    
    /**
     * Elements to display
     * @var array 
     */
    protected $items = array();
    
    /**
     * Initializing class and filter
     * 
     * @param array $options 
     */
    public function __construct($options = array())
    {
        if (isset($options['mode'])) {
            $this->mode = $options['mode'];
        }
        if (Config::FORM_MODE_CUSTOM == $this->mode) {
            $this->items = isset($options['elements']) ? $options['elements'] : array();
        } elseif (!empty($options['elements'])) {
            $this->items = $options['elements'];
        } else {
            $this->items = DraftEditForm::getDefaultElements($this->mode);
        }

        $filterParams = $this->getFilterParameters();
        foreach (array_keys($filterParams) as $name) {
            if (in_array($name, $this->items)) {
                $this->add($filterParams[$name]);
            }
        }

        $this->add($filterParams['id']);
        $this->add($filterParams['fake_id']);
        $this->add($filterParams['uid']);
        $this->add($filterParams['time_publish']);
        $this->add($filterParams['time_update']);
        $this->add($filterParams['time_submit']);
        $this->add($filterParams['article']);
        $this->add($filterParams['jump']);

        if (isset($this->items['image'])) {
            $this->add($filterParams['x']);
            $this->add($filterParams['y']);
            $this->add($filterParams['w']);
            $this->add($filterParams['h']);
        }
    }
    
    /**
     * Getting filter parameters
     * 
     * @return array 
     */
    protected function getFilterParameters()
    {
        $module = Pi::service('module')->current();
        $config = Pi::service('module')->config('', $module);

        $parameters = array(
            'subject'       => array(
                'name'         => 'subject',
                'required'     => false,
                'filters'      => array(
                    array(
                        'name' => 'StringTrim',
                    ),
                ),
            ),
            
            'subtitle'      => array(
                'name'         => 'subtitle',
                'required'     => false,
                'filters'      => array(
                    array(
                        'name' => 'StringTrim',
                    ),
                ),
            ),
            
            'image'         => array(
                'name'         => 'image',
                'required'     => false,
            ),
            
            'uid'           => array(
                'name'         => 'uid',
                'required'     => false,
            ),

            'author'        => array(
                'name'         => 'author',
                'required'     => false,
            ),

            'source'        => array(
                'name'         => 'source',
                'required'     => false,
            ),

            'category'      => array(
                'name'         => 'category',
                'required'     => false,
            ),
            
            'related'       => array(
                'name'         => 'related',
                'required'     => false,
            ),

            'slug'          => array(
                'name'         => 'slug',
                'required'     => false,
                'filters'      => array(
                    array(
                        'name' => 'StringTrim',
                    ),
                ),
            ),

            'seo_title'     => array(
                'name'         => 'seo_title',
                'required'     => false,
            ),

            'seo_keywords'  => array(
                'name'         => 'seo_keywords',
                'required'     => false,
            ),

            'seo_description' => array(
                'name'           => 'seo_description',
                'required'       => false,
            ),

            'time_publish'  => array(
                'name'         => 'time_publish',
                'required'     => false,
            ),

            'time_update'   => array(
                'name'         => 'time_update',
                'required'     => false,
            ),
            
            'time_submit'   => array(
                'name'         => 'time_submit',
                'required'     => false,
            ),

            'content'       => array(
                'name'         => 'content',
                'required'     => false,
            ),

            'id'            => array(
                'name'         => 'id',
                'required'     => false,
            ),

            'fake_id'       => array(
                'name'         => 'fake_id',
                'required'     => false,
            ),

            'article'       => array(
                'name'         => 'article',
                'required'     => false,
            ),

            'jump'          => array(
                'name'         => 'jump',
                'required'     => false,
            ),

            'x'             => array(
                'name'         => 'x',
                'required'     => false,
            ),

            'y'             => array(
                'name'         => 'y',
                'required'     => false,
            ),

            'w'             => array(
                'name'         => 'w',
                'required'     => false,
            ),

            'h'             => array(
                'name'         => 'h',
                'required'     => false,
            ),
        );

        if ($config['enable_summary']) {
            $parameters['summary'] = array(
                'name'       => 'summary',
                'required'   => false,
            );
        }

        if ($config['enable_tag']) {
            $parameters['tag'] = array(
                'name'        => 'tag',
                'required'    => false,
            );
        }

        return $parameters;
    }
}
