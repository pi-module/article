<?php
/**
 * Article module draft class
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
use Module\Article\Service;

class Draft extends Model
{
    const FIELD_STATUS_DRAFT    = 1;
    const FIELD_STATUS_PENDING  = 2;
    const FIELD_STATUS_REJECTED = 3;

    protected $encodeColumns = array(
//        'category' => true,
        'related'      => true,
        'tag'          => true,
    );

    public static function getAvailableFields($module = null)
    {
        $result = array('id', 'article', 'subject', 'subtitle', 'image', 'author', 'uid', 'source', 'content',
            'category', 'related', 'time_publish', 'time_update', 'time_submit', 'time_save', 'slug', 'seo_title',
            'seo_keywords', 'seo_description');

        $module = $module ?: Pi::service('module')->current();
        $moduleConfig = Pi::service('registry')->config->read($module);

        if ($moduleConfig['enable_summary']) {
            $result[] = 'summary';
        }

        if ($moduleConfig['enable_tag']) {
            $result[] = 'tag';
        }

        return $result;
    }
    
    /**
     * Getting the fields needed defines by user
     * 
     * @param string  $module  Module name
     * @return array 
     */
    public static function getValidFields($module = null)
    {
        $options         = Service::getFormConfig();
        $availableFields = self::getAvailableFields($module);
        $remainFields    = array('id', 'article', 'uid', 'time_publish', 'time_update', 'time_submit');
        $validFields     = array_merge($remainFields, array_intersect($availableFields, $options['elements']));
        
        return $validFields;
    }

    public static function getDefaultColumns()
    {
        return array('id', 'subject', 'subtitle', 'category', 'image', 'uid', 'author', 'slug', 'source',
            'time_save');
    }

    public function createOne()
    {
        $data = array(
            'user'   => Pi::registry('user')->id,
            'status' => self::FIELD_STATUS_DRAFT,
        );
        $row = $this->createRow($data);
        $row->save();

        return $row->id ?: false;
    }

    public function getSearchRows($where = array(),  $limit = null, $offset = null, $columns = null, $order = null)
    {
        $result = $rows = array();

        if (null === $columns) {
            $columns = self::getDefaultColumns();
        }

        if (!in_array('id', $columns)) {
            $columns[] = 'id';
        }

        $order = (null === $order) ? 'time_save DESC' : $order;

        $select = $this->select()
            ->columns($columns);

        if ($where) {
            $select->where($where);
        }

        if ($limit) {
            $select->limit(intval($limit));
        }

        if ($offset) {
            $select->offset(intval($offset));
        }

        if ($order) {
            $select->order($order);
        }

        $rows = $this->selectWith($select)->toArray();

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public function getSearchRowsCount($where = array())
    {
        $result = 0;

        $select = $this->select()
            ->columns(array('total' => new Expression('count(id)')));

        if ($where) {
            $select->where($where);
        }

        $resultset = $this->selectWith($select);
        $result = intval($resultset->current()->total);

        return $result;
    }
}
