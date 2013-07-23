<?php
/**
 * Article module file api
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
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Module\Article\Model\Article;
use Module\Article\Model\Asset;
use Module\Article\Upload;
use Module\Article\Cache;
use Pi\Mvc\Controller\ActionController;

/**
 * Public APIs for article module itself 
 */
class File
{
    protected static $module = 'article';

    /**
     * Adding content into file, if the file is not exists, create one
     * 
     * @param string  $filename  Absolute filename
     * @param string  $content   Content want to insert
     * @param bool    $truncate  Whether to truncate file
     * @return boolean 
     */
    public static function addContent($filename, $content = null, $truncate = true)
    {
        $path     = dirname($filename);
        $result   = self::mkdir($path);
        if (!$result) {
            return false;
        }
        
        if (!file_exists($filename)) {
            chmod($path, 0777);
        }
        $mode   = $truncate ? 'w' : 'a';
        $handle = fopen($filename, $mode);
        if (!$handle) {
            return false;
        }
        $result = fwrite($handle, $content);
        
        return $result;
    }
    
    /**
     * Creating directory if it is not exists
     * 
     * @param string  $dir  Absolute directory
     * @return bool 
     */
    public static function mkdir($dir)
    {
        $result = true;

        if (!file_exists($dir)) {
            $oldumask = umask(0);

            $result   = mkdir($dir, 0777, TRUE);

            umask($oldumask);
        }

        return $result;
    }
}
