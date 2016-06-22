<?php

/**
 * @filename CompileClass.php 
 * @encoding UTF-8 
 * @author CzRzChao 
 * @createtime 2016-6-7  22:23:01
 * @updatetime 2016-6-7  22:23:01
 * @version 1.0
 * @Description 模板编译工具类
 * 
 */

class CompileClass {
    private $template;      // 待编译文件
    private $content;       // 需要替换的文本
    private $compile_file;       // 编译后的文件
    private $left = '{';       // 左定界符
    private $right = '}';      // 右定界符
    private $include_file = array();        // 引入的文件
    private $config;        // 模板的配置文件
    private $T_P = array();     // 需要替换的表达式
    private $T_R = array();     // 替换后的字符串
    
    
    public function __construct($template, $compile_file, $config) {
        $this->template = $template;
        $this->compile_file = $compile_file;
        $this->content = file_get_contents($template);
        $this->config = $config;
        
        if($this->config['php_turn'] === false) {
            $this->T_P[] = "/<\?(=|php|)(.+?)\?>/is";
            $this->T_R[] = "&lt;?\\1\\2?&gt;";
        }
        
        // 需要替换的正则表达式
        $this->T_P[] = "/$this->left\s*\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\xf7-\xff]*)\s*$this->right/";
        $this->T_P[] = "/$this->left\s*(loop|foreach)\s*\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\xf7-\xff]*)\s*$this->right/";
        $this->T_P[] = "/$this->left\s*(loop|foreach)\s*\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\xf7-\xff]*)\s+"
                . "as\s+\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\xf7-\xff]*)$this->right/";
        $this->T_P[] = "/$this->left\s*\/(loop|foreach|if)\s*$this->right/";
        $this->T_P[] = "/$this->left\s*if(.*?)\s*$this->right/";
        $this->T_P[] = "/$this->left\s*(else if|elseif)(.*?)\s*$this->right/";
        $this->T_P[] = "/$this->left\s*else\s*$this->right/";
        $this->T_P[] = "/$this->left\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\xf7-\xff]*)\s*$this->right/";

        // 替换后的字符串         
        $this->T_R[] = "<?php echo \$\\1; ?>";
        $this->T_R[] = "<?php foreach((array)\$\\2 as \$K=>\$V) { ?>";
        $this->T_R[] = "<?php foreach((array)\$\\2 as &\$\\3) { ?>";
        $this->T_R[] = "<?php } ?>";
        $this->T_R[] = "<?php if(\\1) { ?>";
        $this->T_R[] = "<?php } elseif(\\2) { ?>";
        $this->T_R[] = "<?php } else { ?>";
        $this->T_R[] = "<?php echo \$\\1; ?>";
    }
    
    public function compile() {
        $this->c_include();
        $this->c_var();
        $this->c_staticFile();
        file_put_contents($this->compile_file, $this->content);
    }
    
    // 处理include
    public function c_include() {
        $include_pattern = "/$this->left\s*include\s+file=\"([\w\.]*)\"\s*$this->right/";
        preg_match_all($include_pattern, $this->content, $results, PREG_SET_ORDER);
        foreach($results as &$result) {
            $include_file = $this->config['template_dir']. $result[1];
            $this->content = preg_replace($include_pattern, file_get_contents($include_file), $this->content, 1);
        }
    }
    
    // 处理各种赋值和基本语句
    public function c_var() {
        $this->content = preg_replace($this->T_P, $this->T_R, $this->content);
    }
    
    // 对静态的JavaScript进行解析    
    public function c_staticFile() {
        $this->content = preg_replace("/$this->left\s*\!(.*?)\!$this->right/", 
                '<script src="\\1'. '?t='.time().'"></script>', $this->content);
    }
    
    public function __set($name, $value) {
        $this->$name = $value;
    }
    
    public function __get($name) {
        if(isset($this->$name)) {
            return $this->$name;
        }
        else {
            return false;
        }
    }
}