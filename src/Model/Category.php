<?php
/**
 * Article module category class
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
use Pi\Application\Model\Nest as Nest;

class Category extends Nest
{
    public static function getAvailableFields()
    {
        return array('id', 'parent', 'name', 'slug', 'title', 'description', 'image');
    }

    public static function getDefaultColumns()
    {
        return array('id', 'slug', 'title', 'image', 'depth');
    }

    /**
     * Get nodes by ids
     *
     * @param $ids Node ids
     * @param null $columns Columns, null for default
     * @return array
     */
    public function getRows($ids, $columns = null)
    {
        $result = $rows = array();

        if (null === $columns) {
            $columns = self::getDefaultColumns();
        }

        if (!in_array('id', $columns)) {
            $columns[] = 'id';
        }

        if ($ids) {
            $result = array_flip($ids);

            $select = $this->select()
                ->columns($columns)
                ->where(array('id' => $ids));

            $rows = $this->selectWith($select)->toArray();

            foreach ($rows as $row) {
                $result[$row['id']] = $row;
            }

            $result = array_filter($result, function($var) {
                return is_array($var);
            });
        }

        return $result;
    }

    /**
     * Get category list
     *
     * @param null $columns Columns, null for default
     * @param bool $withRoot Include root node or not
     * @return array Associative array
     */
    public function getList($columns = null, $withRoot = false)
    {
        $result = $rows = array();

        if (null === $columns) {
            $columns = self::getDefaultColumns();
        }

        if (!in_array('id', $columns)) {
            $columns[] = 'id';
        }

        $select = $this->select()
            ->columns($columns)
            ->order('left ASC');
        if (!$withRoot) {
            $select->where(array('depth > 0'));
        }
        $rows = $this->selectWith($select)->toArray();

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        unset($rows);

        return $result;
    }

    /**
     * Get nodes in level N
     *
     * @param $depth Level depth
     * @param null $columns Columns, null for default
     * @return array Associative array
     */
    public function getLevel($depth, $columns = null)
    {
        $result = $rows = array();

        if (null === $columns) {
            $columns = self::getDefaultColumns();
        }

        if (!in_array('id', $columns)) {
            $columns[] = 'id';
        }

        $select = $this->select()
            ->columns($columns)
            ->where(array(
                'depth' => intval($depth),
            ))
            ->order('left ASC');
        $rows = $this->selectWith($select)->toArray();

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        unset($rows);

        return $result;
    }

    /**
     * Get direct parent node info
     *
     * @param $objective Node id
     * @param null $cols Columns, null for all
     * @return bool|array Parent node info
     */
    public function getParentNode($objective, $cols = null)
    {
        $row = $this->normalizeNode($objective);
        if (!$row) {
            return false;
        }
        $select = $this->select()
            ->where(array($this->quoteColumn('left') . ' < ?' => $row->left))
            ->where(array($this->quoteColumn('right') . ' > ?' => $row->right));
        if (!empty($cols)) {
            $select->columns($cols);
        }
        $select->order($this->column['left'] . ' DESC')->limit(1);
        if (!$rowset = $this->selectWith($select)) {
            return false;
        }

        foreach ($rowset as $row) {
            $result = $row->toArray();
        }

        return $result;
    }

    /**
     * Does a node have children
     *
     * @param $objective Node id
     * @return bool
     */
    public function hasChildren($objective)
    {
        $row = $this->normalizeNode($objective);
        if (!$row) {
            return false;
        }
//        $select = $this->select()
//            ->where(array($this->quoteColumn('left') . ' > ?' => $row->left))
//            ->where(array($this->quoteColumn('right') . ' < ?' => $row->right));
//        $select->order($this->column['left'] . ' ASC');
//        if (($rowset = $this->selectWith($select)) && $rowset->count()) {
//            return true;
//        }
//
//        return false;
        return $row->right - $row->left > 1;
    }

    /**
     * Get ids of all children
     *
     * @param $objective Node id
     * @param null $cols Columns, null for all
     * @param bool $includeSelf Include self in result or not
     * @return array Node ids
     */
    public function getDescendantIds($objective, $cols = null, $includeSelf = true)
    {
        $result = array();

        $children = $this->getChildren($objective, $cols);
        if ($children) {
            foreach ($children as $category) {
                if (!$includeSelf && $objective == $category->id) {
                    continue;
                }
                $result[] = intval($category->id);
            }
        }

        return $result;
    }

    /**
     * Get ids of all sons
     *
     * @param $objective Node id
     * @param null $cols Columns, null for all
     * @param bool $includeSelf Include self in result or not
     * @return array Node ids
     */
    public function getChildrenIds($objective, $cols = null, $includeSelf = false)
    {
        $result = array();

        if (false === array_search('depth', $cols)) {
            $cols[] = 'depth';
        }

        $self = $this->normalizeNode($objective);
        $children = $this->getChildren($objective, $cols);
        foreach ($children as $category) {
            if (!$includeSelf && $objective == $category->id) {
                continue;
            }
            if ($category->depth == $self->depth + 1) {
                $result[] = intval($category->id);
            }
        }

        return $result;
    }

    /**
     * Get nodes as options of Select element
     *
     * @param bool $withRoot Include root node in result or not
     * @return array Options
     */
    public function getSelectOptions($withRoot = false)
    {
        $result = array();

        $allNodes = $this->enumerate(null, null, true);
        if ($allNodes) {
            foreach ($allNodes as $id => $node) {
                $result[$id] = sprintf('%s%s', str_repeat('-', $node['depth']), $node['title']);
            }

            if (!$withRoot) {
                unset($result[1]);
            }
        } else {
            $result['0'] = '';
        }

        return $result;
    }

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
}