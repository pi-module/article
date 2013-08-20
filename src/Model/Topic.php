<?php
/**
 * Article module topic class
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

namespace Module\Article\Model;

use Pi;
use Pi\Application\Model\Model;
use Zend\Db\Sql\Expression;

/**
 * Public class for operating topic table. 
 */
class Topic extends Model
{
    /**
     * Getting available fields.
     * 
     * @return array 
     */
    public static function getAvailableFields()
    {
        return array('id', 'name', 'slug', 'title', 'template', 'description', 'image', 'content');
    }

    /**
     * Getting default columns
     * 
     * @return array 
     */
    public static function getDefaultColumns()
    {
        return array('id', 'slug', 'title', 'image', 'template');
    }

    /**
     * Changing topic slug to topic id
     * 
     * @param string  $slug  Topic unique flag
     * @return int 
     */
    public function slugToId($slug)
    {
        $result = false;

        if ($slug) {
            $row = $this->find($slug, 'slug');
            if ($row) {
                $result = $row->id;
            }
        }

        return $result;
    }
    
    /**
     * Setting active status.
     * 
     * @param int|string  $id      Topic ID or slug
     * @param int         $status  Status
     * @return bool 
     */
    public function setActiveStatus($id, $status = 1)
    {
        if (is_numeric($id)) {
            $row = $this->find($id);
        } else {
            $row = $this->find($id, 'slug');
        }
        
        $row->active = $status;
        $result = $row->save();
        
        return $result;
    }
    
    /**
     * Getting topic list.
     * 
     * @param array       $where
     * @param array|null  $columns
     * @param bool        $all      To fetch all details or only title
     * @return array 
     */
    public function getList($where = array(), $columns = null, $all = false)
    {
        if (empty($columns)) {
            $columns = $this->getDefaultColumns();
        }
        if (!isset($columns['id'])) {
            $columns[] = 'id';
        }
        
        $select = $this->select()
                       ->where($where)
                       ->columns($columns);
        $rowset = $this->selectWith($select);
        
        $list = array();
        foreach ($rowset as $row) {
            if ($all) {
                $list[$row->id] = $row->toArray();
            } else {
                $list[$row->id] = $row->title;
            }
        }
        
        return $list;
    }
    
    /**
     * Getting search row count.
     * 
     * @param array  $where
     * @return int 
     */
    public function getSearchRowsCount($where = array())
    {
        $select = $this->select()
                       ->where($where)
                       ->columns(array('count' => new Expression('count(id)')));
        $count  = (int) $this->selectWith($select)->current()->count;
        
        return $count;
    }
}
