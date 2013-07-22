<?php
/**
 * Article module navigation config
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
    'meta'  => array(
        'cms'    => array(
            'title'     => _t('Article site navigation'),
            'section'   => 'front',
        ),
    ),
    'item'  => array(
        // Default front navigation
        'front'   => array(
            'article-homepage'  => array(
                'label'         => _t('Article Homepage'),
                'route'         => 'default',
                'controller'    => 'index',
                'action'        => 'index',
            ),
            'topic-homepage'    => array(
                'label'         => _t('Topic Homepage'),
                'route'         => 'default',
                'controller'    => 'topic',
                'action'        => 'index',
            ),
            'my'                => array(
                'label'         => _t('My Article'),
                'route'         => 'default',
                'controller'    => 'my',
                'action'        => 'index',
            ),
        ),
        
        // Default admin navigation
        'admin'   => array(
            'article'           => array(
                'label'         => _t('All Articles'),
                'route'         => 'admin',
                'controller'    => 'article',
                'resource'      => array(
                    'resource'  => 'article',
                ),
            ),
            
            'topic'             => array(
                'label'         => _t('Topic'),
                'route'         => 'admin',
                'controller'    => 'topic',
                'resource'      => array(
                    'resource'  => 'topic',
                ),
            ),
            
            'media'             => array(
                'label'         => _t('Media'),
                'route'         => 'admin',
                'controller'    => 'media',
                'resource'      => array(
                    'resource'  => 'media',
                ),
            ),

            'author'            => array(
                'label'         => _t('Author'),
                'route'         => 'admin',
                'controller'    => 'author',
                'resource'      => array(
                    'resource'  => 'author',
                ),
            ),

            'category'          => array(
                'label'         => _t('Category'),
                'route'         => 'admin',
                'controller'    => 'category',
                'resource'      => array(
                    'resource'  => 'category',
                ),
            ),
            
            'permission'        => array(
                'label'         => _t('Permission'),
                'route'         => 'admin',
                'controller'    => 'permission',
                'resource'      => array(
                    'resource'  => 'permission',
                ),
            ),

            'analysis'          => array(
                'label'         => _t('Statistics'),
                'route'         => 'admin',
                'controller'    => 'statistics',
                'resource'      => array(
                    'resource'  => 'statistics',
                ),
            ),
        ),
        
        // Custom front navigation, need setup at backend
        'cms'     => array(
            'article-homepage'  => array(
                'label'         => _t('Article Homepage'),
                'route'         => 'default',
                'controller'    => 'index',
            ),
            'topic-homepage'    => array(
                'label'         => _t('Topic Homepage'),
                'route'         => 'default',
                'controller'    => 'topic',
            ),
            'my'                => array(
                'label'         => _t('My Article'),
                'route'         => 'default',
                'controller'    => 'my',
            ),
        ),
    ),
);
