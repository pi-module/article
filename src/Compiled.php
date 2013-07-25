<?php
/**
 * Article module compiled api
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

namespace Module\Article;

use Pi;

/**
 * Public APIs for article module itself 
 */
class Compiled
{
    protected static $module = 'article';

    public static function compiled($srcType, $content, $destType)
    {
        return $content;
    }
    
    /**
     * Getting compiled article content, if it is not exists, reading content
     * from article table and compiling it.
     * 
     * @param int     $article  Article ID
     * @param string  $type     Type that content will be complied to
     * @return boolean 
     */
    public static function getContent($article, $type)
    {
        $module = Pi::service('module')->current();
        $where  = array(
            'article'  => $article,
            'type'     => empty($type) ? 'html' : $type,
        );
        
        // Reading article content from compiled table
        $modelCompiled = Pi::model('compiled', $module);
        $rowCompiled   = $modelCompiled->select($where)->toArray();
        $compiled      = array_shift($rowCompiled);
        if (!empty($compiled)) {
            return $compiled['content'];
        }
        
        // Reading article content from article table
        $modelArticle  = Pi::model('article', $module);
        $rowArticle    = $modelArticle->find($article);
        if (!$rowArticle->id) {
            return false;
        }
        
        // Compiling article content and saving into compiled table
        $compiledContent = self::compiled($rowArticle->markup, $rowArticle->content, $type);
        $data            = array(
            'article'    => $rowArticle->id,
            'type'       => $type,
            'content'    => $compiledContent,
        );
        $rowCompiled     = $modelCompiled->createRow($data);
        $rowCompiled->save();
        if (!$rowCompiled->id) {
            return false;
        }
        
        return $compiledContent;
    }
}
