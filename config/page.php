<?php
/**
 * Article module page config
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
    'front'   => array(
        array(
            'title'      => _t('Article Homepage'),
            'controller' => 'article',
            'action'     => 'index',
            'block'      => 1,
        ),
        array(
            'title'      => _t('All Article List Page'),
            'controller' => 'list',
            'action'     => 'all',
            'block'      => 1,
        ),
        array(
            'title'      => _t('Category Related Article List Page'),
            'controller' => 'category',
            'action'     => 'list',
            'block'      => 1,
        ),
        array(
            'title'      => _t('Tag Related Article List Page'),
            'controller' => 'tag',
            'action'     => 'list',
            'block'      => 1,
        ),
        array(
            'title'      => _t('Article Detail Page'),
            'controller' => 'article',
            'action'     => 'detail',
            'block'      => 1,
        ),
        array(
            'title'      => _t('Search Result Page'),
            'controller' => 'search',
            'action'     => 'simple',
            'block'      => 1,
        ),
        array(
            'title'      => _t('Topic Homepage'),
            'controller' => 'topic',
            'action'     => 'index',
            'block'      => 1,
        ),
        array(
            'title'      => _t('Topic Article List Page'),
            'controller' => 'topic',
            'action'     => 'list',
            'block'      => 1,
        ),
    ),
    
    'admin'   => array(
        array(
            'controller'   => 'config',
            'permission'   => array(
                'parent'       => 'config',
            ),
        ),
        array(
            'controller'   => 'permission',
            'permission'   => array(
                'parent'       => 'permission',
            ),
        ),
        array(
            'controller'   => 'statistics',
            'permission'   => array(
                'parent'       => 'statistics',
            ),
        ),
    ),
);
