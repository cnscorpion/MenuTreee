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
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('MenuTree_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Archive')->header = array('MenuTree_Plugin', 'header');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $position = new Typecho_Widget_Helper_Form_Element_Select(
            'position',
            array(
                'left' => '左侧',
                'right' => '右侧'
            ),
            'right',
            '目录显示位置',
            '选择目录显示在文章的左侧还是右侧'
        );
        $form->addInput($position);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return string
     */
    public static function render($content, $widget, $lastResult)
    {
        if ($widget instanceof Widget_Archive && $widget->is('single')) {
            $content = $lastResult;
            $tree = self::buildMenuTree($content);
            return '<div class="menu-tree-container">' . $tree . '</div>' . $content;
        }
        return $content;
    }
    
    /**
     * 在header中输出所需的css
     *
     * @access public
     * @return void
     */
    public static function header()
    {
        $cssUrl = Helper::options()->pluginUrl . '/MenuTree/assets/menu-tree.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }
    
    /**
     * 生成目录树
     *
     * @access private
     * @param string $content
     * @return string
     */
    private static function buildMenuTree($content)
    {
        // 使用正则表达式匹配所有标题
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches);
        
        if (empty($matches[0])) {
            return '';
        }
        
        $tree = '<div class="menu-tree"><h3>目录</h3><ul>';
        $lastLevel = 0;
        $counters = array(0, 0, 0, 0, 0, 0); // 为6个级别的标题准备计数器
        
        for ($i = 0; $i < count($matches[0]); $i++) {
            $level = $matches[1][$i];
            $title = strip_tags($matches[2][$i]);
            $id = 'menu-' . $i;
            
            // 更新计数器
            $counters[$level-1]++;
            for ($j = $level; $j < 6; $j++) {
                $counters[$j] = 0;
            }
            
            // 生成编号
            $number = '';
            for ($j = 0; $j < $level; $j++) {
                if ($counters[$j] > 0) {
                    $number .= $counters[$j] . '.';
                }
            }
            
            // 为原标题添加id和编号
            $content = str_replace($matches[0][$i], 
                '<h' . $level . ' id="' . $id . '">' . $number . ' ' . $matches[2][$i] . '</h' . $level . '>', 
                $content);
            
            if ($level > $lastLevel) {
                $tree .= '<ul>';
            } else if ($level < $lastLevel) {
                $tree .= str_repeat('</ul>', $lastLevel - $level);
            }
            
            $tree .= '<li><a href="#' . $id . '">' . $number . ' ' . $title . '</a></li>';
            $lastLevel = $level;
        }
        
        $tree .= str_repeat('</ul>', $lastLevel) . '</ul></div>';
        return $tree;
    }
} 