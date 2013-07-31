<?php
/**
 * Article module article class
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

class Article extends Model
{
    const FIELD_STATUS_PUBLISHED = 11;
    const FIELD_STATUS_DELETED   = 12;

    const FIELD_RELATED_TYPE_OFF    = 0;
    const FIELD_RELATED_TYPE_AUTO   = 1;
    const FIELD_RELATED_TYPE_CUSTOM = 2;

    const FIELD_SEO_SITE_DEFAULT        = 0;
    const FIELD_SEO_TITLE_ARTICLE       = 1;
    const FIELD_SEO_TITLE_CATEGORY      = 2;
    const FIELD_SEO_KEYWORDS_TAG        = 1;
    const FIELD_SEO_KEYWORDS_CATEGORY   = 2;
    const FIELD_SEO_DESCRIPTION_SUMMARY = 1;

    const PAGE_BREAK_PATTERN = '|(<p class="pagebreak page-title">.*?</p>)|is';

    public static function getDefaultColumns()
    {
        return array('id', 'subject', 'image', 'uid', 'author', 'time_publish', 'category', 'active');
    }

    /**
     * Get articles by ids
     *
     * @param $ids Article ids
     * @param null $columns Columns, null for default
     * @return array
     */
    public function getRows($ids, $columns = null)
    {
        $result = $rows = array();

        if (null === $columns) {
            $columns = self::getDefaultColumns();
        }

        if ($ids) {
            $result = array_flip($ids);

            $rows = $this->select(array('id' => $ids));

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
     * Returning rows by search condition.
     * 
     * @param array        $where
     * @param int|null     $limit
     * @param int|null     $offset
     * @param array|null   $columns
     * @param string|null  $order
     * @return array 
     */
    public function getSearchRows($where = array(),  $limit = null, $offset = null, $columns = null, $order = null)
    {
        $result = $rows = array();

        if (null === $columns) {
            $columns = self::getDefaultColumns();
        }

        if (!in_array('id', $columns)) {
            $columns[] = 'id';
        }

        $order = (null === $order) ? 'time_publish DESC' : $order;

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

        $resultset  = $this->selectWith($select);
        $result     = intval($resultset->current()->total);

        return $result;
    }

    /**
     * Setting status of active field.
     * 
     * @param array  $ids
     * @param int    $active
     * @return bool 
     */
    public function setActiveStatus($ids, $active)
    {
        return $this->update(
            array('active' => $active),
            array('id' => $ids)
        );
    }

    public function setRecommendedStatus($ids, $recomended)
    {
        return $this->update(
            array('recommended' => $recomended),
            array('id' => $ids)
        );
    }

    public function visit($article)
    {
        return $this->update(
            array('visits' => new Expression('visits + 1')),
            array('id' => $article)
        );
    }

    public function checkSubjectExists($subject, $id = null)
    {
        $result = false;

        if ($subject) {
            $select = $this->select()
                ->columns(array('total' => new Expression('count(id)')))
                ->where(array(
                    'subject' => $subject,
                    'status'  => self::FIELD_STATUS_PUBLISHED,
                ));
            if ($id) {
                $select->where(array('id <> ?' => $id));
            }

            $result = $this->selectWith($select)->current()->total > 0;
        }

        return $result;
    }

    public function checkSlugExists($slug, $id = null)
    {
        $result = false;

        if ($slug) {
            $select = $this->select()
                ->columns(array('total' => new Expression('count(id)')))
                ->where(array(
                    'slug' => $slug,
                    'status'  => self::FIELD_STATUS_PUBLISHED,
                ));
            if ($id) {
                $select->where(array('id <> ?' => $id));
            }

            $result = $this->selectWith($select)->current()->total > 0;
        }

        return $result;
    }
}
