<?php
namespace MenuTreePlugin;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Widget\Archive;
use Typecho\Plugin;
use Typecho\Common;
use Typecho\Db;

// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 定义一个全局错误处理函数
function debug_print($message) {
    echo "<pre style='background:#fff;color:#333;padding:10px;margin:10px;border:1px solid #ddd;'>";
    echo "Debug: " . htmlspecialchars(print_r($message, true));
    echo "</pre>";
}

// 设置错误处理器
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    debug_print("Error [$errno] $errstr on line $errline in file $errfile");
});

// 设置异常处理器
set_exception_handler(function($e) {
    debug_print("Exception: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
});

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
        try {
            debug_print('开始激活插件...');
            debug_print('当前类名: ' . __CLASS__);
            debug_print('当前文件: ' . __FILE__);
            
            // 注册钩子
            $result1 = \Typecho\Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
            debug_print('header钩子注册结果: ' . print_r($result1, true));
            
            $result2 = \Typecho\Plugin::factory('Widget_Archive')->contentEx = array(__CLASS__, 'contentEx');
            debug_print('contentEx钩子注册结果: ' . print_r($result2, true));
            
            debug_print('插件激活完成');
            return _t('插件启用成功');
        } catch (\Throwable $e) {
            debug_print('激活过程出现错误：' . $e->getMessage());
            debug_print('错误追踪：' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
        try {
            debug_print('开始禁用插件...');
            return _t('插件禁用成功');
        } catch (\Throwable $e) {
            debug_print('禁用过程出现错误：' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        try {
            debug_print('加载配置面板...');
        } catch (\Throwable $e) {
            debug_print('配置面板加载错误：' . $e->getMessage());
        }
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
        try {
            debug_print('加载个人配置面板...');
        } catch (\Throwable $e) {
            debug_print('个人配置面板加载错误：' . $e->getMessage());
        }
    }

    /**
     * 输出头部CSS
     */
    public static function header()
    {
        try {
            debug_print('开始输出CSS...');
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
            debug_print('CSS输出完成');
        } catch (\Throwable $e) {
            debug_print('CSS输出错误：' . $e->getMessage());
        }
    }

    /**
     * 内容处理
     */
    public static function contentEx($content, $archive)
    {
        try {
            debug_print('开始处理内容...');
            debug_print('内容类型: ' . gettype($content));
            debug_print('Archive类型: ' . get_class($archive));
            
            if ($archive->is('single')) {
                $matches = array();
                preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches);
                
                if (!empty($matches[0])) {
                    debug_print('找到' . count($matches[0]) . '个标题');
                    
                    $tree = '<div class="menu-tree"><h3>目录</h3><ul>';
                    $lastLevel = 0;
                    $counters = array_fill(0, 6, 0);
                    
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $level = (int)$matches[1][$i];
                        $title = strip_tags($matches[2][$i]);
                        $id = 'title-' . $i;
                        
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
                        
                        $content = str_replace($matches[0][$i], 
                            '<h' . $level . ' id="' . $id . '">' . $number . '. ' . $matches[2][$i] . '</h' . $level . '>', 
                            $content);
                        
                        if ($level > $lastLevel) {
                            $tree .= '<ul>';
                        } else if ($level < $lastLevel) {
                            $tree .= str_repeat('</ul>', $lastLevel - $level);
                        }
                        
                        $tree .= '<li><a href="#' . $id . '">' . $number . '. ' . $title . '</a></li>';
                        $lastLevel = $level;
                    }
                    
                    $tree .= str_repeat('</ul>', $lastLevel) . '</ul></div>';
                    debug_print('目录生成完成');
                    return $tree . $content;
                }
            }
            return $content;
        } catch (\Throwable $e) {
            debug_print('内容处理错误：' . $e->getMessage());
            debug_print('错误追踪：' . $e->getTraceAsString());
            return $content;
        }
    }

    /**
     * 写入日志
     *
     * @param string $message
     */
    private static function writeLog($message)
    {
        error_log('[MenuTree] ' . $message, 0);
    }
} 