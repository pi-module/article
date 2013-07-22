<?php
/**
 * Article module author class
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

namespace Module\Article\Model;

use Pi;
use Pi\Application\Model\Model;

/**
 * Model class for operating author table 
 */
class Author extends Model
{
    /**
     * Getting available fields
     * 
     * @return array 
     */
    public static function getAvailableFields()
    {
        return array('id', 'name', 'photo', 'description');
    }

    /**
     * Getting author name
     * 
     * @return array 
     */
    public function getSelectOptions()
    {
        $result = array('0' => '');

        $select = $this->sql->select()
            ->columns(array('id', 'name'))->order('name ASC');
        $authors = $this->selectWith($select);

        foreach ($authors as $author) {
            $result[$author->id] = $author->name;
        }

        return $result;
    }
}
