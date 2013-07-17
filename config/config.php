<?php
/**
 * Article module config config
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

return array(
    'category' => array(
        array(
            'name'  => 'general',
            'title' => __('General'),
        ),
        array(
            'name'  => 'autosave',
            'title' => __('Autosave'),
        ),
        array(
            'name'  => 'seo',
            'title' => __('SEO'),
        ),
        array(
            'name'  => 'image',
            'title' => __('Image'),
        ),
        array(
            'name'  => 'summary',
            'title' => __('Summary and subject'),
        ),
        array(
            'name'  => 'media',
            'title' => __('Media'),
        ),
    ),

    'item' => array(
        // General
        'page_limit'      => array(
            'category'    => 'general',
            'title'       => __('Page limit'),
            'description' => __('Maximum count of articles in a front page.'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'enable_tag'      => array(
            'category'    => 'general',
            'title'       => __('Enable tag'),
            'description' => __('Enable tag (Tag module must be installed)'),
            'edit'        => 'checkbox',
            'value'       => 1,
            'filter'      => 'number_int',
        ),
        'default_source'  => array(
            'category'    => 'general',
            'title'       => __('Default source'),
            'description' => __('Display when no source is provided.'),
            'value'       => 'Pi',
        ),
        'default_category' => array(
            'category'    => 'general',
            'title'       => __('Default category'),
            'description' => __('Can not be deleted.'),
            'edit'        => 'Module\Article\Form\Element\Category',
            'value'       => 2,
            'filter'      => 'number_int',
        ),
        'max_related'     => array(
            'category'    => 'general',
            'title'       => __('Max related articles'),
            'description' => __('Maximum related articles to fetch.'),
            'value'       => 5,
            'filter'      => 'number_int',
        ),

        // Autosave
        'enable_autosave'   => array(
            'category'    => 'autosave',
            'title'       => __('Enable'),
            'description' => __('Enable autosave function of editor'),
            'edit'        => 'checkbox',
            'value'       => 1,
            'filter'      => 'number_int',
        ),
        'autosave_interval' => array(
            'category'    => 'autosave',
            'title'       => __('Interval'),
            'description' => __('How many minutes to save draft once again.'),
            'value'       => 5,
            'filter'      => 'number_int',
        ),

        // Image
        'path_author'  => array(
            'category'    => 'image',
            'title'       => __('Author'),
            'description' => __('Path to upload photo of author.'),
            'value'       => 'upload/article/author',
        ),
        'path_category' => array(
            'category'    => 'image',
            'title'       => __('Category'),
            'description' => __('Path to upload image of category.'),
            'value'       => 'upload/article/category',
        ),
        'path_feature'  => array(
            'category'    => 'image',
            'title'       => __('Feature'),
            'description' => __('Path to upload feature image of article.'),
            'value'       => 'upload/article/feature',
        ),
        
        'sub_dir_pattern' => array(
            'category'    => 'image',
            'title'       => __('Pattern'),
            'description' => __('Use datetime as pattern of sub directory.'),
            'value'       => __('Y/m/d'),
            'edit'        => array(
                'type'    => 'select',
                'options' => array(
                    'options' => array(
                        'Y/m/d' => 'Y/m/d',
                        'Y/m'   => 'Y/m',
                        'Ym'    => 'Ym',
                    ),
                ),
            ),
        ),
        'image_extension' => array(
            'category'    => 'image',
            'title'       => __('Image extension'),
            'description' => __('Images types which can be uploaded.'),
            'value'       => __('jpg,png,gif'),
        ),
        'max_image_size' => array(
            'category'    => 'image',
            'title'       => __('Max image size'),
            'description' => __('Max image size allowed'),
            'value'       => '8MB',
        ),
        'author_w'        => array(
            'category'    => 'image',
            'title'       => __('Author photo width'),
            'description' => __('Author photo width'),
            'value'       => 110,
            'filter'      => 'number_int',
        ),
        'author_h'        => array(
            'category'    => 'image',
            'title'       => __('Author photo height'),
            'description' => __('Author photo height'),
            'value'       => 110,
            'filter'      => 'number_int',
        ),
        'default_author_photo' => array(
            'category'    => 'image',
            'title'       => __('Default author photo'),
            'description' => __('Path to default photo of author.'),
            'value'       => 'image/default-author.png',
        ),
        'category_w'      => array(
            'category'    => 'image',
            'title'       => __('Category image width'),
            'description' => __('Category image width'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'category_h'      => array(
            'category'    => 'image',
            'title'       => __('Category image height'),
            'description' => __('Category image height'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'default_category_image' => array(
            'category'    => 'image',
            'title'       => __('Default category image'),
            'description' => __('Path to default image of category.'),
            'value'       => 'image/default-category.png',
        ),
        'feature_w'       => array(
            'category'    => 'image',
            'title'       => __('Feature image width'),
            'description' => __('Feature image width'),
            'value'       => 440,
            'filter'      => 'number_int',
        ),
        'feature_h'       => array(
            'category'    => 'image',
            'title'       => __('Feature image height'),
            'description' => __('Feature image height'),
            'value'       => 300,
            'filter'      => 'number_int',
        ),
        'default_feature_image' => array(
            'category'    => 'image',
            'title'       => __('Default feature image'),
            'description' => __('Path to default feature image of article.'),
            'value'       => 'image/default-feature.png',
        ),
        
        
        // Media
        'path_media'      => array(
            'category'    => 'media',
            'title'       => __('Media Path'),
            'description' => __('Path to save media file.'),
            'value'       => 'var/data/article/media',
        ),
        'media_extension' => array(
            'category'    => 'media',
            'title'       => __('Media extension'),
            'description' => __('Media types which can be uploaded.'),
            'value'       => 'pdf,rar,zip,doc,docx,xls,xlsx,ppt,pptx,jpg,png,gif',
        ),
        'max_media_size'  => array(
            'category'    => 'media',
            'title'       => __('Max media size'),
            'description' => __('Max media size'),
            'value'       => '20MB',
        ),
        'image_w'         => array(
            'category'    => 'media',
            'title'       => __('Image Width'),
            'description' => __('Max allowed image width'),
            'value'       => 540,
        ),
        'image_h'         => array(
            'category'    => 'media',
            'title'       => __('Image Height'),
            'description' => __('Max allowed image height'),
            'value'       => 460,
        ),

        // SEO
        'seo_keywords'    => array(
            'category'    => 'seo',
            'title'       => __('Keywords'),
            'description' => __('Setup head keywords.'),
            'value'       => 0,
            'filter'      => 'number_int',
            'edit'        => array(
                'type'    => 'select',
                'options' => array(
                    'options' => array(
                        0  => __('Site default'),
                        1  => __('Use tag'),
                        2  => __('Use category'),
                    ),
                ),
            ),
        ),
        'seo_description' => array(
            'category'    => 'seo',
            'title'       => __('Description'),
            'description' => __('Setup head description.'),
            'value'       => 0,
            'filter'      => 'number_int',
            'edit'        => array(
                'type'    => 'select',
                'options' => array(
                    'options' => array(
                        0  => __('Site default'),
                        1  => __('Use summary'),
                    ),
                ),
            ),
        ),

        // Summary
        'enable_summary'     => array(
            'category'    => 'summary',
            'title'       => __('Enable summary'),
            'description' => __('Enable summary'),
            'edit'        => 'checkbox',
            'value'       => 1,
            'filter'      => 'number_int',
        ),
        'max_summary_length' => array(
            'category'    => 'summary',
            'title'       => __('Max summary length'),
            'description' => __('Not more than 255'),
            'value'       => 255,
            'filter'      => 'number_int',
        ),
        'max_subject_length' => array(
            'category'    => 'summary',
            'title'       => __('Max subject length'),
            'description' => __('Not more than 255'),
            'value'       => 60,
            'filter'      => 'number_int',
        ),
        'max_subtitle_length' => array(
            'category'    => 'summary',
            'title'       => __('Max subtitle length'),
            'description' => __('Not more than 255'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
    ),
);
