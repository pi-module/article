<?php
/**
 * Article module related class
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

class Related extends Model
{
    public static function getDefaultColumns()
    {
        return array('id', 'related');
    }

    public function saveRelated($article, $data)
    {
        // Delete old related articles
        $this->delete(array('article' => $article));

        // Insert new related articles
        $order = 0;
        foreach ($data as $relatedId) {
            $row = $this->createRow(array(
                'article' => $article,
                'related' => $relatedId,
                'order'   => $order++,
            ));
            $row->save();
        }

        return;
    }

    public function getRelated($article)
    {
        $result = array();

        $select = $this->select()
            ->columns(self::getDefaultColumns())
            ->where(array('article' => $article))
            ->order('order ASC');
        $resultset = $this->selectWith($select)->toArray();

        foreach ($resultset as $row) {
            $result[] = $row['related'];
        }

        return $result;
    }
}
