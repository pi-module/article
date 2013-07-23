<?php
/**
 * Article module DraftEditForm form
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
use Module\Article\Model\Article;
use Module\Article\Controller\Admin\ConfigController as Config;

/**
 * Class for initializing form element 
 */
class DraftEditForm extends BaseForm
{
    /**
     * Initializing form element 
     */
    public function init()
    {
        $module = Pi::service('module')->current();
        $config = Pi::service('module')->config('', $module);

        $this->add(array(
            'name'       => 'subject',
            'options'    => array(
                'label'  => __('Subject'),
            ),
            'attributes' => array(
                'id'        => 'subject',
                'type'      => 'text',
                'data-size' => $config['max_subject_length'],
            ),
        ));

        $this->add(array(
            'name'       => 'subtitle',
            'options'    => array(
                'label' => __('Subtitle'),
            ),
            'attributes' => array(
                'id'        => 'subtitle',
                'type'      => 'text',
                'data-size' => $config['max_subtitle_length'],
            ),
        ));

        $this->add(array(
            'name' => 'uid',
            'options'    => array(
                'label' => __('Submitter'),
            ),
            'attributes' => array(
                'id'   => 'user',
                'type' => 'hidden',
            ),
        ));

        if (!empty($config['enable_summary'])) {
            $this->add(array(
                'name' => 'summary',
                'options' => array(
                    'label' => __('Summary'),
                ),
                'attributes' => array(
                    'type'      => 'textarea',
                    'data-size' => $config['max_summary_length']
                ),
            ));
        }

        $this->add(array(
            'name' => 'image',
            'options' => array(
                'label' => __('Image'),
            ),
            'attributes' => array(
                'id'   => 'image',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name' => 'author',
            'options' => array(
                'label' => __('Author'),
            ),
            'attributes' => array(
                'id'   => 'author',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name' => 'source',
            'options' => array(
                'label' => __('Source'),
            ),
            'attributes' => array(
                'id'   => 'source',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name' => 'category',
            'options' => array(
                'label' => __('Category'),
            ),
            'type' => 'Module\Article\Form\Element\Category',
        ));

        if ($config['enable__ag']) {
            $this->add(array(
                'name' => 'tag',
                'type' => "hidden"
            ));
        }

        $this->add(array(
            'name' => 'related',
            'options' => array(
                'label' => __('Related article'),
            ),
            'attributes' => array(
                'id'   => 'related',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name' => 'slug',
            'options' => array(
                'label' => __('Slug'),
            ),
            'attributes' => array(
                'id'        => 'slug',
                'type'      => 'text',
                'data-size' => $config['max_subject_length'],
            ),
        ));

        $this->add(array(
            'name' => 'seo__itle',
            'options' => array(
                'label' => __('SEO title'),
            ),
            'attributes' => array(
                'id'   => 'seo__itle',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name' => 'seo_keywords',
            'options' => array(
                'label' => __('SEO keywords'),
            ),
            'attributes' => array(
                'id'   => 'seo_keywords',
                'type' => 'text',
            ),
        ));

        $this->add(array(
            'name' => 'seo_description',
            'options' => array(
                'label' => __('SEO description'),
            ),
            'attributes' => array(
                'id'   => 'seo_description',
                'type' => 'textarea',
            ),
        ));

        $this->add(array(
            'name' => 'time_publish',
            'options' => array(
                'label' => __('Publish time'),
            ),
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name' => 'time_update',
            'options' => array(
                'label' => __('Update time'),
            ),
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name' => 'content',
            'options' => array(
                'label' => __('Content'),
                'editor' => 'html',
            ),
            'attributes' => array(
                'id'   => 'content',
                'type' => 'editor',
            ),
        ));
        $editorConfig = Pi::config()->load("module.{$module}.ckeditor.php");
        $editor = $this->get('content');
        $editor->setOptions(array_merge($editor->getOptions(), $editorConfig));

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
            'name'  => 'article',
            'attributes'    => array(
                'id'   => 'article',
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name'  => 'jump',
            'attributes' => array(
                'id'   => 'jump',
                'type'  => 'hidden',
                'value' => '',
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
            'name'          => 'do_submit',
            'attributes'    => array(               
                'value' => __('Save draft'),
            ),
            'type'  => 'submit',
        ));
    }
    
    /**
     * Getting defined form element
     * !!! The value of each field must be the name of each form
     * 
     * @return array 
     */
    public function getExistsFormElements()
    {
        return array(
            'subject'         => __('Subject'),
            'subtitle'        => __('Subtitle'),
            'summary'         => __('Summary'),
            'content'         => __('Content'),
            'image'           => __('Image'),
            'author'          => __('Author'),
            'source'          => __('Source'),
            'category'        => __('Category'),
            'tag'             => __('Tag'),
            'related'         => __('Related'),
            'slug'            => __('Slug'),
            'seo_title'       => __('SEO Title'),
            'seo_keywords'    => __('SEO Keywords'),
            'seo_description' => __('SEO Description'),
        );
    }
    
    /**
     * Getting default elements for displaying
     * 
     * @return array 
     */
    public function getDefaultElements($mode = Config::FORM_MODE_EXTENDED)
    {
        $normal = array(
            'subject',
            'subtitle',
            'summary',
            'content',
            'image',
            'author',
            'source',
            'category',
            'tag',
        );
        
        $extended = array_merge($normal, array(
            'related',
            'slug',
            'seo_title',
            'seo_keywords',
            'seo_description',
        ));
        
        return (Config::FORM_MODE_NORMAL == $mode) ? $normal : $extended;
    }
}
