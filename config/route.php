<?php
/**
 * Article module route config
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Copyright (c) Engine http://www.xoopsengine.org
 * @license         http://www.xoopsengine.org/license New BSD License
 * @author          Zongshu Lin <zongshu@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

return array(
    'article' => array(
        'section'  => 'front',
        'priority' => 100,

        'type'     => 'Module\Article\Route\Article',
        'options'  => array(
            'prefix'          => '/article',
            'structure_delimiter'   => '/',
            'param_delimiter'       => '-',
            'key_value_delimiter'   => '-',
            'defaults'        => array(
                'module'     => 'article',
                'controller' => 'index',
                'action'     => 'index',
            ),
        ),
    ),
);
