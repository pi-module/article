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
use Module\Article\Model\Article;

class DraftEditForm extends BaseForm
{
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
            'name' => 'user',
            'options'    => array(
                'label' => __('Submitter'),
            ),
            'attributes' => array(
                'id'   => 'user',
                'type' => 'hidden',
            ),
        ));

        if ($config['enable_summary']) {
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

        if ($config['enable_tag']) {
            $this->add(array(
                'name' => 'tag',
                'type' => "hidden"
            ));
        }

        $this->add(array(
            'name' => 'related_type',
            'attributes' => array(
                'value' => Article::FIELD_RELATED_TYPE_OFF,
            ),
        ));

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
            'name' => 'seo_title',
            'options' => array(
                'label' => __('SEO title'),
            ),
            'attributes' => array(
                'id'   => 'seo_title',
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
            'name'       => 'recommended',
            'options'    => array(
                'label' => __('Recommended'),
            ),
            'type' => 'checkbox'
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
}
