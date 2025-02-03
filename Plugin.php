<?php
namespace MenuTreePlugin;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Widget\Archive;
use Typecho\Plugin;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 为文章自动生成目录树
 * 
 * @package MenuTree
 * @author MPL
 * @version 1.0.0
 * @link https://github.com/cnscorpion/MenuTree
 */
class MenuTree implements PluginInterface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        Plugin::factory('Widget_Archive')->header = __CLASS__ . '::header';
        Plugin::factory('Widget_Archive')->contentEx = __CLASS__ . '::contentEx';
        return _t('插件启用成功');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
        return _t('插件禁用成功');
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 输出头部CSS
     */
    public static function header()
    {
        echo '<style>
        .menu-tree {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 250px;
            max-height: calc(100vh - 160px);
            overflow-y: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-size: 14px;
            z-index: 1000;
        }
        .menu-tree h3 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }
        .menu-tree ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        .menu-tree ul ul {
            padding-left: 20px;
        }
        .menu-tree li {
            margin: 8px 0;
            line-height: 1.5;
        }
        .menu-tree a {
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
            display: block;
            padding: 3px 0;
        }
        .menu-tree a:hover {
            color: #1a73e8;
            padding-left: 5px;
        }
        @media screen and (max-width: 1200px) {
            .menu-tree {
                position: relative;
                top: 0;
                right: 0;
                width: 100%;
                max-height: none;
                margin-bottom: 20px;
                box-shadow: none;
                border: 1px solid #eee;
            }
        }
        </style>';
    }

    /**
     * 内容处理
     */
    public static function contentEx($content, $archive)
    {
        if ($archive->is('single')) {
            $matches = array();
            preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches);
            
            if (!empty($matches[0])) {
                $tree = '<div class="menu-tree"><h3>目录</h3><ul>';
                $lastLevel = 0;
                $counters = array_fill(0, 6, 0);
                
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $level = (int)$matches[1][$i];
                    $title = strip_tags($matches[2][$i]);
                    $id = 'title-' . $i;
                    
                    // 更新计数器
                    if ($level === 1) {
                        $counters[0]++;
                        $number = $counters[0];
                        for ($j = 1; $j < 6; $j++) {
                            $counters[$j] = 0;
                        }
                    } else {
                        $parentLevel = $level - 1;
                        if ($parentLevel >= 0) {
                            $counters[$level-1]++;
                            $number = '';
                            for ($j = 0; $j < $level; $j++) {
                                if ($j === $level - 1) {
                                    $number .= $counters[$j];
                                } else {
                                    $number .= $counters[$j] . '.';
                                }
                            }
                            for ($j = $level; $j < 6; $j++) {
                                $counters[$j] = 0;
                            }
                        }
                    }
                    
                    // 添加编号到标题
                    $content = str_replace($matches[0][$i], 
                        '<h' . $level . ' id="' . $id . '">' . $number . '. ' . $matches[2][$i] . '</h' . $level . '>', 
                        $content);
                    
                    // 处理目录层级
                    if ($level > $lastLevel) {
                        $tree .= '<ul>';
                    } else if ($level < $lastLevel) {
                        $tree .= str_repeat('</ul>', $lastLevel - $level);
                    }
                    
                    $tree .= '<li><a href="#' . $id . '">' . $number . '. ' . $title . '</a></li>';
                    $lastLevel = $level;
                }
                
                $tree .= str_repeat('</ul>', $lastLevel) . '</ul></div>';
                return $tree . $content;
            }
        }
        return $content;
    }
} 