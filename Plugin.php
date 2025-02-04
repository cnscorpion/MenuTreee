<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 定义一个全局错误处理函数
function debug_print($message) {
    // 注释掉调试信息的显示
    /*
    echo "<pre style='background:#fff;color:#333;padding:10px;margin:10px;border:1px solid #ddd;'>";
    echo "Debug: " . htmlspecialchars(print_r($message, true));
    echo "</pre>";
    */
}

// 设置错误处理器
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    debug_print("Error [$errno] $errstr on line $errline in file $errfile");
});

// 设置异常处理器
set_exception_handler(function($e) {
    debug_print("Exception: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
});

/**
 * 为文章自动生成目录树
 * 
 * @package MenuTree
 * @author MPL
 * @version 1.0.0
 * @link https://github.com/cnscorpion/MenuTree
 */
class MenuTree_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        try {
            debug_print('开始激活插件...');
            
            Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('MenuTree_Plugin', 'contentEx');
            Typecho_Plugin::factory('Widget_Archive')->header = array('MenuTree_Plugin', 'header');
            
            debug_print('钩子注册完成');
            return _t('插件启用成功');
        } catch (Exception $e) {
            debug_print('激活过程出现错误：' . $e->getMessage());
            debug_print('错误追踪：' . $e->getTraceAsString());
            throw new Typecho_Plugin_Exception(_t('插件启用失败: %s', $e->getMessage()));
        }
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
     * @param Typecho_Widget_Helper_Form $form 配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 输出头部CSS
     */
    public static function header()
    {
        try {
            echo '<style>
            .menu-tree {
                width: 100%;
                background: var(--background);
                padding: var(--padding-15);
                border-radius: var(--radius-8);
                font-size: 14px;
                border: 1px solid var(--classC);
                box-sizing: border-box;
                margin-bottom: 15px;
            }

            /* 侧边栏基础样式 */
            .joe_aside {
                position: relative;
            }

            /* 作者信息样式 */
            .joe_aside__item.author {
                position: relative;
                background: var(--background);
                margin-bottom: 15px;
            }

            /* 创建一个包裹容器用于固定定位 */
            .joe_aside__item:not(.author) {
                position: sticky;
                top: 20px;
            }

            .menu-tree h3 {
                padding: 0;
                margin: 0 0 15px 0;
                color: var(--main);
                font-size: 16px;
                font-weight: 500;
                line-height: 1;
                text-align: left;
                position: relative;
                display: flex;
                align-items: center;
                border-bottom: 1px solid var(--classC);
                padding-bottom: 10px;
            }

            .menu-tree h3:before {
                content: "";
                width: 4px;
                height: 16px;
                background: var(--theme);
                margin-right: 8px;
                border-radius: 2px;
            }

            .menu-tree ul {
                list-style: none;
                padding-left: 0;
                margin: 0;
                max-height: calc(100vh - 250px);
                overflow-y: auto;
                scrollbar-width: thin;
                scrollbar-color: var(--classC) var(--classD);
            }

            .menu-tree ul ul {
                padding-left: 15px;
                position: relative;
                display: block;
                margin: 3px 0;
            }

            .menu-tree ul ul::before {
                content: "";
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 2px;
                background: var(--classC);
                opacity: 0.5;
            }

            .menu-tree li {
                margin: 3px 0;
                line-height: 1.6;
                position: relative;
            }

            .menu-tree li::before {
                display: none;
            }

            .menu-tree a {
                color: var(--routine);
                text-decoration: none;
                transition: all 0.2s;
                display: block;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: normal;
                position: relative;
                font-size: 13px;
                line-height: 1.4;
            }

            .menu-tree a:hover {
                color: var(--theme);
                background: var(--background);
                padding-left: 12px;
            }

            /* 添加滚动条样式 */
            .menu-tree ul::-webkit-scrollbar {
                width: 4px;
            }

            .menu-tree ul::-webkit-scrollbar-thumb {
                background: var(--classC);
                border-radius: 2px;
            }

            .menu-tree ul::-webkit-scrollbar-track {
                background: var(--classD);
            }

            @media screen and (max-width: 768px) {
                .joe_aside__item:not(.author) {
                    position: relative;
                    top: 0;
                }
                .menu-tree ul {
                    max-height: 300px;
                }
            }
            </style>';
        } catch (Exception $e) {
            debug_print('CSS输出错误：' . $e->getMessage());
        }
    }

    /**
     * 内容处理
     */
    public static function contentEx($content, $widget, $lastResult)
    {
        try {
            if ($widget instanceof Widget_Archive && $widget->is('single')) {
                $matches = array();
                preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches);
                
                if (!empty($matches[0])) {
                    debug_print('找到标题数量: ' . count($matches[0]));
                    
                    // 初始化目录树
                    $tree = '<div class="menu-tree"><h3>目录</h3><ul>';
                    $structure = array();
                    $minLevel = min(array_map('intval', $matches[1]));
                    $lastLevel = $minLevel;
                    $counters = array_fill(0, 6, 0);
                    
                    // 第一遍循环：构建结构数组
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $level = (int)$matches[1][$i];
                        $title = trim(strip_tags($matches[2][$i]));
                        $id = 'title-' . $i;
                        
                        // 计算编号
                        $number = '';
                        for ($j = $minLevel; $j <= $level; $j++) {
                            if ($j == $level) {
                                $counters[$j-1]++;
                                $number .= $counters[$j-1];
                            } else {
                                $number .= $counters[$j-1] . '.';
                            }
                        }
                        // 重置更深层级的计数器
                        for ($j = $level + 1; $j < 6; $j++) {
                            $counters[$j-1] = 0;
                        }
                        
                        $structure[] = array(
                            'level' => $level,
                            'title' => $title,
                            'id' => $id,
                            'number' => $number
                        );
                        
                        // 替换原文中的标题，使用 htmlspecialchars_decode 确保正确显示
                        $content = str_replace(
                            $matches[0][$i],
                            '<h' . $level . ' id="' . $id . '">' . $number . '. ' . htmlspecialchars_decode($matches[2][$i]) . '</h' . $level . '>',
                            $content
                        );
                    }
                    
                    // 第二遍循环：构建HTML
                    foreach ($structure as $item) {
                        $level = $item['level'];
                        
                        // 处理层级变化
                        if ($level > $lastLevel) {
                            // 进入更深层级，开始新的子列表
                            $tree .= '<ul>';
                        } else if ($level < $lastLevel) {
                            // 返回上层，关闭当前层级
                            $tree .= str_repeat('</li></ul>', $lastLevel - $level);
                            $tree .= '</li>';
                        } else {
                            // 同级，关闭上一个项
                            if ($lastLevel != $minLevel) {
                                $tree .= '</li>';
                            }
                        }
                        
                        // 添加新项，使用 htmlspecialchars_decode 确保正确显示
                        $tree .= '<li><a href="#' . $item['id'] . '">' . 
                                $item['number'] . '. ' . htmlspecialchars_decode($item['title']) . '</a>';
                        
                        $lastLevel = $level;
                    }
                    
                    // 关闭所有剩余的标签
                    if ($lastLevel >= $minLevel) {
                        $tree .= str_repeat('</li></ul>', $lastLevel - $minLevel);
                        $tree .= '</li></ul></div>';
                    } else {
                        $tree .= '</ul></div>';
                    }
                    
                    debug_print('生成的目录树HTML: ' . $tree);
                    return $tree . $content;
                }
            }
            return $content;
        } catch (Exception $e) {
            debug_print('内容处理错误：' . $e->getMessage());
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