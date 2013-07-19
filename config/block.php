<?php
/**
 * Article module block config
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
    'all-categories'           => array(
        'title'       => __('All Categories'),
        'description' => __('Listing the parent category and its children'),
        'render'      => 'block::AllCategories',
        'template'    => 'all-categories',
        'config'      => array(
            'category-column'  => array(
                'title'        => __('Column Count'),
                'description'  => __('The max child category when to display'),
                'edit'         => array(
                    'type'        => 'select',
                    'attributes'  => array(
                        'options'    => array(
                            'top'    => __('Top'),
                            'first'  => __('First sub-category'),
                            'second' => __('Second sub-category'),
                        ),
                    ),
                ),
                'filter'       => 'string',
                'value'        => 'second',
            ),
            'default-category' => array(
                'title'        => __('Default Category Name'),
                'description'  => __('Default category name when there is no category acquired'),
                'edit'         => 'text',
                'filter'       => 'string',
                'value'        => 'None',
            ),
        ),
    ),
    'newest-published-article' => array(
        'title'       => __('Newest Published Articles'),
        'description' => __('Listing the newest published articles of topic or non-topic'),
        'render'      => 'block::NewestPublishedArticles',
        'template'    => 'newest-published-articles',
        'config'      => array(
            'list-count'       => array(
                'title'        => __('List Count'),
                'description'  => __('The max articles to display'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 10,
            ),
            'is-topic'         => array(
                'title'        => __('Is Topic'),
                'description'  => __('Whether to list topic articles'),
                'edit'         => array(
                    'type'        => 'checkbox',
                    'attributes'  => array(
                        'value'      => 0,
                    ),
                ),
                'filter'       => 'number_int',
            ),
            'category'         => array(
                'title'        => __('Category'),
                'description'  => __('Which category article want to list'),
                'edit'         => array(
                    'type'        => 'Module\Article\Form\Element\Category',
                ),
                'filter'       => 'string',
                'value'        => 0,
            ),
            'topic'            => array(
                'title'        => __('Topic'),
                'description'  => __('Which topic article want to list'),
                'edit'         => array(
                    'type'        => 'Module\Article\Form\Element\Topic',
                ),
                'filter'       => 'string',
                'value'        => 0,
            ),
            'block-style'      => array(
                'title'        => __('Template Style'),
                'description'  => __('The template style of list'),
                'edit'         => array(
                    'type'        => 'radio',
                    'attributes'  => array(
                        'options'    => array(
                            'common'    => __('Common'),
                            'summary'   => __('With summary'),
                            'feature'   => __('With feature'),
                        ),
                    ),
                ),
                'filter'       => 'string',
                'value'        => 'common',
            ),
            'target'           => array(
                'title'        => __('Target'),
                'description'  => __('Open url in which window'),
                'edit'         => array(
                    'type'        => 'select',
                    'attributes'  => array(
                        'options'    => array(
                            '_blank'    => 'Blank',
                            '_parent'   => 'Parent',
                            '_self'     => 'Self',
                            '_top'      => 'Top',
                        ),
                    ),
                ),
                'filter'        => 'string',
                'value'         => '_blank',
            ),
            'max_subject_length' => array(
                'title'         => __('Subject length'),
                'description'   => __('Maximum length of subject'),
                'edit'          => 'text',
                'filter'        => 'number_int',
                'value'         => 80,
            ),
        ),
    ),
    'recommended-article'      => array(
        'title'       => __('Recommended Articles'),
        'description' => __('Listing the recommended articles of topic or non-topic'),
        'render'      => 'block::RecommendedArticles',
        'template'    => 'recommended-article',
        'config'      => array(
            'list-count'       => array(
                'title'        => __('List Count'),
                'description'  => __('The max articles to display'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 10,
            ),
            'is-topic'         => array(
                'title'        => __('Is Topic'),
                'description'  => __('Whether to list topic articles'),
                'edit'         => array(
                    'type'        => 'checkbox',
                    'attributes'  => array(
                        'value'      => 0,
                    ),
                ),
                'filter'       => 'number_int',
            ),
            'block-style'      => array(
                'title'        => __('Template Style'),
                'description'  => __('The template style of list'),
                'edit'         => array(
                    'type'        => 'radio',
                    'attributes'  => array(
                        'options'    => array(
                            'common'    => __('Common'),
                            'summary'   => __('With summary'),
                            'feature'   => __('With feature'),
                        ),
                    ),
                ),
                'filter'       => 'string',
                'value'        => 'common',
            ),
            'target'           => array(
                'title'        => __('Target'),
                'description'  => __('Open url in which window'),
                'edit'         => array(
                    'type'        => 'select',
                    'attributes'  => array(
                        'options'    => array(
                            '_blank'    => 'Blank',
                            '_parent'   => 'Parent',
                            '_self'     => 'Self',
                            '_top'      => 'Top',
                        ),
                    ),
                ),
                'filter'        => 'string',
                'value'         => '_blank',
            ),
            'max_subject_length' => array(
                'title'         => __('Subject length'),
                'description'   => __('Maximum length of subject'),
                'edit'          => 'text',
                'filter'        => 'number_int',
                'value'         => 80,
            ),
        ),
    ),
    'recommended-slideshow'    => array(
        'title'       => __('Recommended Articles With Slideshow'),
        'description' => __('Listing a slideshow and recommended articles'),
        'render'      => 'block::RecommendedSlideshow',
        'template'    => 'recommended-slideshow',
        'config'      => array(
            'list-count'       => array(
                'title'        => __('List Count'),
                'description'  => __('The max articles to display'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 10,
            ),
            'is-topic'         => array(
                'title'        => __('Is Topic'),
                'description'  => __('Whether to list topic articles'),
                'edit'         => array(
                    'type'        => 'checkbox',
                    'attributes'  => array(
                        'value'      => 0,
                    ),
                ),
                'filter'       => 'number_int',
            ),
            'block-style'      => array(
                'title'        => __('Template Style'),
                'description'  => __('The template style of list'),
                'edit'         => array(
                    'type'        => 'radio',
                    'attributes'  => array(
                        'options'    => array(
                            'common'    => __('Common'),
                            'summary'   => __('With summary'),
                            'feature'   => __('With feature'),
                        ),
                    ),
                ),
                'filter'       => 'string',
                'value'        => 'common',
            ),
            'images'           => array(
                'title'        => __('Image ID'),
                'description'  => __('Images to display'),
                'edit'         => 'text',
                'filter'       => 'string',
                'value'        => 'image/default-recommended.png',
            ),
            'image-width'      => array(
                'title'        => __('Image Width'),
                'description'  => __('Image width'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 440,
            ),
            'image-height'     => array(
                'title'        => __('Image Height'),
                'description'  => __('Image height'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 300,
            ),
            'target'           => array(
                'title'        => __('Target'),
                'description'  => __('Open url in which window'),
                'edit'         => array(
                    'type'        => 'select',
                    'attributes'  => array(
                        'options'    => array(
                            '_blank'    => 'Blank',
                            '_parent'   => 'Parent',
                            '_self'     => 'Self',
                            '_top'      => 'Top',
                        ),
                    ),
                ),
                'filter'        => 'string',
                'value'         => '_blank',
            ),
            'max_subject_length' => array(
                'title'         => __('Subject length'),
                'description'   => __('Maximum length of subject'),
                'edit'          => 'text',
                'filter'        => 'number_int',
                'value'         => 80,
            ),
        ),
    ),
    'custom-article-list'      => array(
        'title'       => __('Custom Article List'),
        'description' => __('Listing custom articles'),
        'render'      => 'block::CustomArticleList',
        'template'    => 'custom-article-list',
        'config'      => array(
            'articles'         => array(
                'title'        => __('Article ID'),
                'description'  => __('Articles want to list'),
                'edit'         => 'text',
                'filter'       => 'string',
                'value'        => 0,
            ),
            'target'           => array(
                'title'        => __('Target'),
                'description'  => __('Open url in which window'),
                'edit'         => array(
                    'type'        => 'select',
                    'attributes'  => array(
                        'options'    => array(
                            '_blank'    => 'Blank',
                            '_parent'   => 'Parent',
                            '_self'     => 'Self',
                            '_top'      => 'Top',
                        ),
                    ),
                ),
                'filter'        => 'string',
                'value'         => '_blank',
            ),
            'max_subject_length' => array(
                'title'         => __('Subject length'),
                'description'   => __('Maximum length of subject'),
                'edit'          => 'text',
                'filter'        => 'number_int',
                'value'         => 80,
            ),
        ),
    ),
    'submitter-statistics'     => array(
        'title'       => __('Submitter Statistics'),
        'description' => __('Listing the total article count of submitters'),
        'render'      => 'block::SubmitterStatistics',
        'template'    => 'submitter-statistics',
        'config'      => array(
            'list-count'       => array(
                'title'        => __('List Count'),
                'description'  => __('The max articles to display'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 10,
            ),
        ),
    ),
    'newest-topic'             => array(
        'title'       => __('Newest Topic'),
        'description' => __('Listing the newest topic'),
        'render'      => 'block::NewestTopic',
        'template'    => 'newest-topic',
        'config'      => array(
            'list-count'       => array(
                'title'        => __('List Count'),
                'description'  => __('The max articles to display'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 10,
            ),
        ),
    ),
    'hot-article'              => array(
        'title'       => __('Hot Articles'),
        'description' => __('Listing the hotest articles'),
        'render'      => 'block::HotArticles',
        'template'    => 'hot-article',
        'config'      => array(
            'list-count'       => array(
                'title'        => __('List Count'),
                'description'  => __('The max articles to display'),
                'edit'         => 'text',
                'filter'       => 'number_int',
                'value'        => 10,
            ),
            'is-topic'         => array(
                'title'        => __('Is Topic'),
                'description'  => __('Whether to list topic articles'),
                'edit'         => array(
                    'type'        => 'checkbox',
                    'attributes'  => array(
                        'value'      => 0,
                    ),
                ),
                'filter'       => 'number_int',
            ),
            'block-style'      => array(
                'title'        => __('Template Style'),
                'description'  => __('The template style of list'),
                'edit'         => array(
                    'type'        => 'radio',
                    'attributes'  => array(
                        'options'    => array(
                            'common'    => __('Common'),
                            'summary'   => __('With summary'),
                            'feature'   => __('With feature'),
                        ),
                    ),
                ),
                'filter'       => 'string',
                'value'        => 'common',
            ),
            'target'           => array(
                'title'        => __('Target'),
                'description'  => __('Open url in which window'),
                'edit'         => array(
                    'type'        => 'select',
                    'attributes'  => array(
                        'options'    => array(
                            '_blank'    => 'Blank',
                            '_parent'   => 'Parent',
                            '_self'     => 'Self',
                            '_top'      => 'Top',
                        ),
                    ),
                ),
                'filter'        => 'string',
                'value'         => '_blank',
            ),
            'max_subject_length' => array(
                'title'         => __('Subject length'),
                'description'   => __('Maximum length of subject'),
                'edit'          => 'text',
                'filter'        => 'number_int',
                'value'         => 80,
            ),
        ),
    ),
);
