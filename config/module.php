<?php
/**
 * Article module module config
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
    // Module meta
    'meta'         => array(
        'title'         => __('Content Management'),
        'description'   => __('General module for content management.'),
        'version'       => '1.0.1-beta.1',
        'license'       => 'New BSD',
        'logo'          => 'image/logo.png',
        'readme'        => 'README.md',
        'clonable'      => false,
    ),
    // Author information
    'author'        => array(
        'name'          => 'Zongshu Lin',
        'email'         => 'zongshu@eefocus.com',
        'website'       => 'http://www.github.com/pi-engine/pi',
        'credits'       => 'Pi Engine Team.'
    ),
    // Module dependency: list of module directory names, optional
    'dependency'    => array(
    ),
    // Maintenance actions
    'maintenance'   => array(
        'resource'      => array(
            'database'      => array(
                'sqlfile'      => 'sql/mysql.sql',
                'schema'       => array(
                    'article'       => 'table',
                    'extended'      => 'table',
                    'field'         => 'table',
                    'compiled'      => 'table',
                    'draft'         => 'table',
                    'related'       => 'table',
                    'visit'         => 'table',
                    'category'      => 'table',
                    'author'        => 'table',
                    'statistics'    => 'table',
                ),
            ),
            // Database meta
            'navigation'    => 'navigation.php',
            'block'         => 'block.php',
            'config'        => 'config.php',
        ),
    ),
);
