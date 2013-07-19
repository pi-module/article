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
 * @copyright       Copyright (c) http://www.eefocus.com
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Lijun Dong <lijun@eefocus.com>
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article\Form;

use Pi;
use Zend\InputFilter\InputFilter;

class DraftEditFilter extends InputFilter
{
    public function __construct()
    {
        $module = Pi::service('module')->current();
        $config = Pi::service('module')->config('', $module);

        $this->add(array(
            'name'     => 'channel',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'subject',
            'required' => false,
            'filters'  => array(
                array(
                    'name' => 'StringTrim',
                ),
            ),
//            'validators' => array(
//                array(
//                    'name'    => 'StringLength',
//                    'options' => array(
//                        'max'      => $config['max_subject_length'],
//                        'encoding' => 'utf-8',
//                    ),
//                ),
//            ),
        ));

        $this->add(array(
            'name'     => 'subtitle',
            'required' => false,
            'filters'  => array(
                array(
                    'name' => 'StringTrim',
                ),
            ),
//            'validators' => array(
//                array(
//                    'name'    => 'StringLength',
//                    'options' => array(
//                        'max'      => $config['max_subtitle_length'],
//                        'encoding' => 'utf-8',
//                    ),
//                ),
//            ),
        ));

        if ($config['enable_summary']) {
            $this->add(array(
                'name'       => 'summary',
                'required'   => false,
//                'validators' => array(
//                    array(
//                        'name'    => 'StringLength',
//                        'options' => array(
//                            'max'      => $config['max_summary_length'],
//                            'encoding' => 'utf-8',
//                        ),
//                    ),
//                ),
            ));
        }

        $this->add(array(
            'name'     => 'image',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'user',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'author',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'source',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'category',
            'required' => false,
        ));

        if ($config['enable_tag']) {
            $this->add(array(
                'name'     => 'tag',
                'required' => false,
            ));
        }

        $this->add(array(
            'name'     => 'partnumber',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'manufacturer',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'related_type',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'related',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'slug',
            'required' => false,
            'filters'  => array(
                array(
                    'name' => 'StringTrim',
                ),
            ),
//            'validators' => array(
//                array(
//                    'name'    => 'StringLength',
//                    'options' => array(
//                        'max'      => $config['max_subject_length'],
//                        'encoding' => 'utf-8',
//                    ),
//                ),
//                array(
//                    'name'      => 'Regex',
//                    'options'   => array(
//                        'pattern'   => '/^[a-zA-Z0-9\_\-].$/',
//                    ),
//                ),
//            ),
        ));

        $this->add(array(
            'name'     => 'seo_title',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'seo_keywords',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'seo_description',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'time_publish',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'time_update',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'content',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'recommended',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'id',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'fake_id',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'article',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'jump',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'x',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'y',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'w',
            'required' => false,
        ));

        $this->add(array(
            'name'     => 'h',
            'required' => false,
        ));
    }
}
