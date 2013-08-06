<?php
/**
 * Article module acl config
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

return array(
    'roles'      => array(
        // Front role for article module
        'article-manager' => array(
            'title'     => _t('Article Manager'),
        ),
        'contributor'     => array(
            'title'     => _t('Contributor'),
            'section'   => 'admin',
            'parents'   => array('staff'),
        )
    ),
    
    'resources'  => array(
        'front'          => array(
            // Article author resource
            'author'     => array(
                'module'      => 'article',
                'title'       => _t('Author management'),
                'access'      => array(
                    'article-manager' => 0,
                    'member'          => 0,
                    'guest'           => 0,
                    'inactive'        => 0,
                    'banned'          => 0,
                ),
            ),
            // Article category resource
            'category'   => array(
                'module'      => 'article',
                'title'       => _t('Category management'),
                'access'      => array(
                    'article-manager' => 0,
                    'member'          => 0,
                    'guest'           => 0,
                    'inactive'        => 0,
                    'banned'          => 0,
                ),
            ),
            // Topic resource
            'topic'      => array(
                'module'      => 'article',
                'title'       => _t('Topic management'),
                'access'      => array(
                    'article-manager' => 0,
                    'member'          => 0,
                    'guest'           => 0,
                    'inactive'        => 0,
                    'banned'          => 0,
                ),
            ),
            // Media resource
            'media'      => array(
                'module'      => 'article',
                'title'       => _t('Media management'),
                'access'      => array(
                    'article-manager' => 0,
                    'member'          => 0,
                    'guest'           => 0,
                    'inactive'        => 0,
                    'banned'          => 0,
                ),
            ),
        ),
        
        'admin'          => array(
            // Article statistics resource
            'statistics' => array(
                'module'      => 'article',
                'title'       => _t('Statistics page view'),
                'access'      => array(
                    'contributor'  => 1,
                ),
            ),
            // Module permission controller
            'permission' => array(
                'module'      => 'article',
                'title'       => _t('Permission management'),
            ),
            // Article configuration
            'config'     => array(
                'module'      => 'article',
                'title'       => _t('Configuration management'),
            ),
        ),
    ),
);
