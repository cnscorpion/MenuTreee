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
                position: fixed;
                top: 100px;
                right: 30px;
                width: 280px;
                max-height: calc(100vh - 180px);
                overflow-y: auto;
                background: #ffffff;
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                font-size: 14px;
                z-index: 1000;
                transition: all 0.3s ease;
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .menu-tree::-webkit-scrollbar {
                width: 4px;
            }

            .menu-tree::-webkit-scrollbar-track {
                background: #f5f5f5;
                border-radius: 2px;
            }

            .menu-tree::-webkit-scrollbar-thumb {
                background: #ddd;
                border-radius: 2px;
            }

            .menu-tree::-webkit-scrollbar-thumb:hover {
                background: #ccc;
            }

            .menu-tree h3 {
                margin: 0 0 20px 0;
                padding-bottom: 12px;
                border-bottom: 2px solid #f0f0f0;
                font-size: 18px;
                color: #2c3e50;
                font-weight: 600;
                letter-spacing: 0.5px;
            }

            .menu-tree ul {
                list-style: none;
                padding-left: 0;
                margin: 0;
                display: block; /* 确保一级目录显示 */
            }

            .menu-tree ul ul {
                padding-left: 18px;
                position: relative;
                display: none; /* 只有子目录默认隐藏 */
            }

            .menu-tree ul ul.show {
                display: block; /* 显示子菜单 */
            }

            .menu-tree ul ul::before {
                content: "";
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #f0f0f0;
                border-radius: 1px;
            }

            .menu-tree li {
                margin: 8px 0;
                line-height: 1.6;
                position: relative;
            }

            .menu-tree li.has-children > a::after {
                content: "▸";
                position: absolute;
                right: 10px;
                transition: transform 0.3s ease;
            }

            .menu-tree li.has-children.expanded > a::after {
                transform: rotate(90deg);
            }

            .menu-tree li::before {
                content: "";
                position: absolute;
                left: -18px;
                top: 50%;
                width: 12px;
                height: 2px;
                background: #f0f0f0;
                display: none;
            }

            .menu-tree ul ul li::before {
                display: block;
            }

            .menu-tree a {
                color: #5a6c7d;
                text-decoration: none;
                transition: all 0.3s;
                display: block;
                padding: 6px 10px;
                border-radius: 4px;
                font-weight: 500;
                position: relative;
            }

            .menu-tree a:hover {
                color: #3498db;
                background: rgba(52, 152, 219, 0.05);
                padding-left: 15px;
            }

            @media screen and (max-width: 1400px) {
                .menu-tree {
                    width: 250px;
                }
            }

            @media screen and (max-width: 1200px) {
                .menu-tree {
                    position: relative;
                    top: 0;
                    right: 0;
                    width: 100%;
                    max-height: none;
                    margin: 0 0 30px 0;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                    padding: 20px;
                }

                .menu-tree h3 {
                    margin-bottom: 15px;
                }
            }
            </style>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                // 确保目录树容器存在
                const menuTree = document.querySelector(".menu-tree");
                if (!menuTree) return;

                // 为所有有子菜单的项添加标记和点击事件
                const menuItems = document.querySelectorAll(".menu-tree li");
                menuItems.forEach(item => {
                    const subMenu = item.querySelector("ul");
                    if (subMenu) {
                        item.classList.add("has-children");
                        // 将点击事件添加到整个li元素
                        item.addEventListener("click", function(e) {
                            // 阻止事件冒泡，防止触发父级菜单的点击事件
                            e.stopPropagation();
                            // 只有当点击的是当前li或其直接子a标签时才触发
                            if (e.target === this || e.target === this.querySelector("a")) {
                                e.preventDefault();
                                this.classList.toggle("expanded");
                                subMenu.classList.toggle("show");
                            }
                        });
                    }
                });

                // 点击叶子节点时滚动到对应位置
                const menuLinks = document.querySelectorAll(".menu-tree li:not(.has-children) > a");
                menuLinks.forEach(link => {
                    link.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const targetId = this.getAttribute("href").substring(1);
                        const targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            targetElement.scrollIntoView({ behavior: "smooth" });
                        }
                    });
                });
            });
            </script>';
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