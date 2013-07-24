<?php
/**
 * Article module cache api
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
 * @author          Lijun Dong <lijun@eefocus.com>
 * @since           1.0
 * @package         Module\Article
 */

namespace Module\Article;

use Pi;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Module\Article\Model\Article;

class Cache
{
    const KEY_CATEGORY_LIST         = 'eef_article_category_list';
    const KEY_CHANNEL_LIST          = 'eef_article_channel_list';
    const KEY_ARTICLE_ALL_COUNT     = 'eef_article_ALL_count';
    const KEY_ARTICLE_NEWS_COUNT    = 'eef_article_news_count';
    const KEY_ARTICLE_PRODUCT_COUNT = 'eef_article_product_count';
    const KEY_ARTICLE_DESIGN_COUNT  = 'eef_article_design_count';

    const EXPIRATION = 180;

    protected static $module = 'article';

    public static function getSimple($key, $default = null)
    {
        $result = $default;

        $cache = Pi::service('cache');

        if ($cache) {
            $result = $cache->getItem($key);
        }

        return $result;
    }

    public static function setSimple($key, $val, $expiration = null)
    {
        $result = false;

        $options = array(
            'ttl' => is_int($expiration) ? $expiration : self::EXPIRATION,
        );

        $cache = Pi::service('cache');

        if ($cache) {
            $result = $cache->setItem($key, $val, $options);
        }

        return $result;
    }

    public static function getCategoryList()
    {
        $result = Pi::registry(self::KEY_CATEGORY_LIST) ?: false;

        if (false === $result) {
            $result = Pi::model('category', self::$module)->getList();

            Pi::registry(self::KEY_CATEGORY_LIST, $result);
        }

        return $result;
    }

    public static function getChannelList()
    {
        $result = Pi::registry(self::KEY_CHANNEL_LIST) ?: false;

        if (false === $result) {
            $result = Pi::service('api')->channel(
                array('channel', 'getList')
            );

            Pi::registry(self::KEY_CHANNEL_LIST, $result);
        }

        return $result;
    }
}
