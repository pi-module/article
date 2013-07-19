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
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

return array(
    'category' => array(
        array(
            'name'  => 'general',
            'title' => _t('General'),
        ),
        array(
            'name'  => 'autosave',
            'title' => _t('Autosave'),
        ),
        array(
            'name'  => 'seo',
            'title' => _t('SEO'),
        ),
        array(
            'name'  => 'summary',
            'title' => _t('Summary and subject'),
        ),
        array(
            'name'  => 'media',
            'title' => _t('Media'),
        ),
    ),

    'item' => array(
        // General
        'page_limit_all'  => array(
            'category'    => 'general',
            'title'       => _t('Article List Page Limit'),
            'description' => _t('Maximum count of articles in a front page.'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'page_limit_topic' => array(
            'category'    => 'general',
            'title'       => _t('Topic Article List Page Limit'),
            'description' => _t('Maximum count of topic articles in a front page.'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'page_limit_management' => array(
            'category'    => 'general',
            'title'       => _t('Article Management Page Limit'),
            'description' => _t('Maximum count of articles in a management page.'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'enable_tag'      => array(
            'category'    => 'general',
            'title'       => _t('Enable Tag'),
            'description' => _t('Enable tag (Tag module must be installed)'),
            'edit'        => 'checkbox',
            'value'       => 1,
            'filter'      => 'number_int',
        ),
        'default_source'  => array(
            'category'    => 'general',
            'title'       => _t('Default Source'),
            'description' => _t('Display when no source is provided.'),
            'value'       => 'Pi',
        ),
        'default_category' => array(
            'category'    => 'general',
            'title'       => _t('Default Category'),
            'description' => _t('Can not be deleted.'),
            'edit'        => 'Module\Article\Form\Element\Category',
            'value'       => 2,
            'filter'      => 'number_int',
        ),
        'max_related'     => array(
            'category'    => 'general',
            'title'       => _t('Max Related Articles'),
            'description' => _t('Maximum related articles to fetch.'),
            'value'       => 5,
            'filter'      => 'number_int',
        ),

        // Autosave
        'autosave_interval' => array(
            'category'    => 'autosave',
            'title'       => _t('Interval'),
            'description' => _t('How many minutes to save draft once again, there will not autosave if it set to 0.'),
            'value'       => 5,
            'filter'      => 'number_int',
        ),
        
        // Summary
        'enable_summary'     => array(
            'category'    => 'summary',
            'title'       => _t('Enable Summary'),
            'description' => _t('Enable summary'),
            'edit'        => 'checkbox',
            'value'       => 1,
            'filter'      => 'number_int',
        ),
        'max_summary_length' => array(
            'category'    => 'summary',
            'title'       => _t('Max Summary Length'),
            'description' => _t('Not more than 255'),
            'value'       => 255,
            'filter'      => 'number_int',
        ),
        'max_subject_length' => array(
            'category'    => 'summary',
            'title'       => _t('Max Subject Length'),
            'description' => _t('Not more than 255'),
            'value'       => 60,
            'filter'      => 'number_int',
        ),
        'max_subtitle_length' => array(
            'category'    => 'summary',
            'title'       => _t('Max Subtitle Length'),
            'description' => _t('Not more than 255'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),

        // Media
        'path_media'      => array(
            'category'    => 'media',
            'title'       => _t('Media Path'),
            'description' => _t('Path to save media file.'),
            'value'       => 'var/data/article/media',
        ),
        'media_extension' => array(
            'category'    => 'media',
            'title'       => _t('Media Extension'),
            'description' => _t('Media types which can be uploaded.'),
            'value'       => 'pdf,rar,zip,doc,docx,xls,xlsx,ppt,pptx,jpg,png,gif',
        ),
        'max_media_size'  => array(
            'category'    => 'media',
            'title'       => _t('Max Media Size'),
            'description' => _t('Max media size'),
            'value'       => '2MB',
        ),
        'image_width'     => array(
            'category'    => 'media',
            'title'       => _t('Image Width'),
            'description' => _t('Max allowed image width'),
            'value'       => 540,
        ),
        'image_height'    => array(
            'category'    => 'media',
            'title'       => _t('Image Height'),
            'description' => _t('Max allowed image height'),
            'value'       => 460,
        ),
        'image_extension' => array(
            'category'    => 'media',
            'title'       => _t('Image Extension'),
            'description' => _t('Images types which can be uploaded.'),
            'value'       => 'jpg,png,gif',
        ),
        'max_image_size' => array(
            'category'    => 'image',
            'title'       => _t('Max Image Size'),
            'description' => _t('Max image size allowed'),
            'value'       => '2MB',
        ),
        'path_author'  => array(
            'category'    => 'media',
            'title'       => _t('Author Path'),
            'description' => _t('Path to upload photo of author.'),
            'value'       => 'upload/article/author',
        ),
        'path_category' => array(
            'category'    => 'media',
            'title'       => _t('Category Path'),
            'description' => _t('Path to upload image of category.'),
            'value'       => 'upload/article/category',
        ),
        'path_feature'  => array(
            'category'    => 'media',
            'title'       => _t('Feature Path'),
            'description' => _t('Path to upload feature image of article.'),
            'value'       => 'upload/article/feature',
        ),
        'sub_dir_pattern' => array(
            'category'    => 'media',
            'title'       => _t('Pattern'),
            'description' => _t('Use datetime as pattern of sub directory.'),
            'value'       => _t('Y/m/d'),
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
        'author_width'    => array(
            'category'    => 'media',
            'title'       => _t('Author Photo Width'),
            'description' => _t('Author photo width'),
            'value'       => 110,
            'filter'      => 'number_int',
        ),
        'author_h'        => array(
            'category'    => 'media',
            'title'       => _t('Author Photo Height'),
            'description' => _t('Author photo height'),
            'value'       => 110,
            'filter'      => 'number_int',
        ),
        'default_author_photo' => array(
            'category'    => 'media',
            'title'       => _t('Default Author Photo'),
            'description' => _t('Path to default photo of author.'),
            'value'       => 'image/default-author.png',
        ),
        'category_width'  => array(
            'category'    => 'media',
            'title'       => _t('Category Image Width'),
            'description' => _t('Category image width'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'category_height' => array(
            'category'    => 'media',
            'title'       => _t('Category Image Height'),
            'description' => _t('Category image height'),
            'value'       => 40,
            'filter'      => 'number_int',
        ),
        'default_category_image' => array(
            'category'    => 'media',
            'title'       => _t('Default Category Image'),
            'description' => _t('Path to default image of category.'),
            'value'       => 'image/default-category.png',
        ),
        'feature_width'   => array(
            'category'    => 'media',
            'title'       => _t('Feature Image Width'),
            'description' => _t('Feature image width'),
            'value'       => 440,
            'filter'      => 'number_int',
        ),
        'feature_height'  => array(
            'category'    => 'image',
            'title'       => _t('Feature Image Height'),
            'description' => _t('Feature image height'),
            'value'       => 300,
            'filter'      => 'number_int',
        ),
        'default_feature_image' => array(
            'category'    => 'image',
            'title'       => _t('Default Feature Image'),
            'description' => _t('Path to default feature image of article.'),
            'value'       => 'image/default-feature.png',
        ),

        // SEO
        'seo_keywords'    => array(
            'category'    => 'seo',
            'title'       => _t('Keywords'),
            'description' => _t('Setup head keywords.'),
            'value'       => 0,
            'filter'      => 'number_int',
            'edit'        => array(
                'type'    => 'select',
                'options' => array(
                    'options' => array(
                        0  => _t('Site default'),
                        1  => _t('Use tag'),
                        2  => _t('Use category'),
                    ),
                ),
            ),
        ),
        'seo_description' => array(
            'category'    => 'seo',
            'title'       => _t('Description'),
            'description' => _t('Setup head description.'),
            'value'       => 0,
            'filter'      => 'number_int',
            'edit'        => array(
                'type'    => 'select',
                'options' => array(
                    'options' => array(
                        0  => _t('Site default'),
                        1  => _t('Use summary'),
                    ),
                ),
            ),
        ),
    ),
);
