<?php
/**
 * Copyright © czrzchao.com
 * User: czrzchao
 * Date: 2017/11/14 22:46
 * Desc: 模板类git
 */

class Template
{
    // 配置数组    
    private        $_array_config = [
        'root'         => '',               // 文件根目录
        'suffix'       => '.html',          // 模板文件后缀
        'template_dir' => 'templates',      // 模板所在文件夹
        'compile_dir'  => 'templates_c',    // 编译后存放的文件夹
        'cache_dir'    => 'cache',          // 静态html存放地址
        'cache_htm'    => false,            // 是否编译为静态html文件
        'suffix_cache' => '.htm',           // 设置编译文件的后缀
        'cache_time'   => 7200,             // 自动更新间隔
        'php_turn'     => true,             // 是否支持原生php代码
        'debug'        => 'false',
    ];
    private        $_value        = [];
    private        $_compileTool;      // 编译器
    static private $_instance     = null;
    public         $file;        // 模板文件名
    public         $debug         = [];        // 调试信息

    public function __construct($array_config = [])
    {
        $this->_array_config['root'] = str_replace('\\', '/', realpath(dirname(__FILE__))) . '/';
        $this->debug['begin']        = microtime(true);
        $this->_array_config         = $array_config + $this->_array_config;
        $this->getPath();
        if (!is_dir($this->_array_config['compile_dir'])) {
            mkdir($this->_array_config['compile_dir']);
        }
        if (!is_dir($this->_array_config['cache_dir'])) {
            mkdir($this->_array_config['cache_dir']);
        }
        require('CompileClass.php');
    }

    // 将配置中的路径替换为绝对路径
    public function getPath()
    {
        $this->_array_config['template_dir'] = $this->_array_config['root'] . $this->_array_config['template_dir'] . '/';
        $this->_array_config['compile_dir']  = $this->_array_config['root'] . $this->_array_config['compile_dir'] . '/';
        $this->_array_config['cache_dir']    = $this->_array_config['root'] . $this->_array_config['cache_dir'] . '/';
    }

    // 获取模板实例
    public static function getInstance()
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // 单步设置配置文件
    public function setConfig($key, $value = null)
    {
        if (is_array($key)) {
            $this->_array_config = $key + $this->_array_config;
        } else {
            $this->_array_config[$key] = $value;
        }
    }

    // 获取当前模板的配置信息
    public function getConfig($key = null)
    {
        if (isset($key)) {
            return $this->_array_config[$key];
        } else {
            return $this->_array_config;
        }
    }

    // 注入单个变量
    public function assign($key, $value)
    {
        $this->_value[$key] = $value;
    }

    // 注入数组变量
    public function assignArray($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                $this->_value[$k] = $v;
            }
        }
    }

    public function path()
    {
        return $this->_array_config['template_dir'] . $this->file . $this->_array_config['suffix'];
    }

    // 是否开启缓存
    public function needCache()
    {
        return $this->_array_config['cache_htm'];
    }

    // 如果需要重新编译文件
    public function reCache()
    {
        $flag       = false;
        $cache_file = $this->_array_config['cache_dir'] . md5($this->file) . $this->_array_config['suffix_cache'];

        if ($this->needCache() === true) {
            $time_flag = (time() - @filemtime($cache_file)) < $this->_array_config['cache_time'] ? true : false;
            if (is_file($cache_file) and filesize($cache_file) > 1 and $time_flag) {
                $flag = true;
            } else {
                $flag = false;
            }
        }

        return $flag;
    }

    // 显示模板
    public function show($file)
    {
        $this->file = $file;
        if (!is_file($this->path())) {
            exit("找不到对应的模板文件");
        }

        $compile_file = $this->_array_config['compile_dir'] . md5($file) . '.php';
        $cache_file   = $this->_array_config['cache_dir'] . md5($file) . $this->_array_config['suffix_cache'];

        // 如果需要重新编译文件
        if ($this->reCache() === false) {
            $this->debug['cached'] = 'false';
            $this->_compileTool    = new CompileClass($this->path(), $compile_file, $this->_array_config);

            if ($this->needCache() === true) {
                // 输出到缓冲区
                ob_start();
            }
            // 将赋值的变量导入当前符号表
            extract($this->_value, EXTR_OVERWRITE);

            if (!is_file($compile_file) or filemtime($compile_file) < filemtime($this->path())) {
                $this->_compileTool->vars = $this->_value;
                $this->_compileTool->compile();
                include($compile_file);
            } else {
                include($compile_file);
            }

            // 如果需要编译成静态文件
            if ($this->needCache() === true) {
                $message = ob_get_contents();
                file_put_contents($cache_file, $message);
            }
        } else {
            readfile($cache_file);
            $this->debug['cached'] = 'true';
        }
        $this->debug['spend'] = microtime(true) - $this->debug['begin'];
        $this->debug['count'] = count($this->_value);
        $this->debugInfo();
    }

    // 打印debug信息    
    public function debugInfo()
    {
        if ($this->_array_config['debug'] === true) {
            echo '----------DEBUG INFO----------', PHP_EOL;
            echo '程序运行日期:', date('Y-m-d h:i:s'), PHP_EOL;
            echo '模板解析耗时:', $this->debug['spend'], '秒', PHP_EOL;
            echo '模板包含标签数目:', $this->debug['count'], PHP_EOL;
            echo '是否使用静态缓存:', $this->debug['cached'], PHP_EOL;
            echo '模板引擎实例化参数:';
            var_dump($this->getConfig());
        }
    }

    // 清除静态的缓存文件
    public function clean($file = null)
    {
        if ($file === null) {
            // 匹配对应规则文件
            $file = glob($this->_array_config['cache_dir'] . '*' . $this->_array_config['suffix_cache']);
        } else {
            $file = $this->_array_config['cache_dir'] . md5($file) . $this->_array_config['suffix_cache'];
        }
        foreach ((array)$file as $v) {
            // 删除文件
            unlink($v);
        }
    }
}