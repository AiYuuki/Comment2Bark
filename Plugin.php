<?php
/**
 * Bark推送评论通知
 * 
 * @package Comment2Bark
 * @author 神代夕綺
 * @version 1.0
 * @link https://kira.cool
 */
class Comment2Bark_Plugin implements Typecho_Plugin_Interface {
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('Comment2Bark_Plugin', 'bark_send');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array('Comment2Bark_Plugin', 'bark_send');
        Typecho_Plugin::factory('Widget_XmlRpc')->pingback = array('Comment2Bark_Plugin', 'bark_send');
        
        return _t('请配置此插件的 Bark Key, 以使您能顺利推送到Bark');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        $key = new Typecho_Widget_Helper_Form_Element_Text('bark_key', NULL, NULL, _t('Bark Key'), _t('下载 Bark App 后获取Bark key'));
        $form->addInput($key->addRule('required', _t('您必须填写一个正确的 Bark Key')));
        
        $notMyself = new Typecho_Widget_Helper_Form_Element_Radio('notMyself',
            array(
                '1' => '是',
                '0' => '否'
            ),'1', _t('当评论者为自己时不发送通知'), _t('启用后，若评论者为博主，则不会向 Bark 发送通知<br><br><br>
            此插件由原作者 <a href="https://yian.me">Y!an</a> 的 <a href="https://github.com/YianAndCode/Comment2Bark">Comment2Bark 1.0.0</a> 和 <a href="https://moe.best">神代綺凛</a> 的 <a href="https://github.com/Tsuk1ko/Comment2Wechat">Comment2Bark 2.0.0</a>插件修改而来<br>本插件项目地址：<a href="https://github.com/JDYuuki/Comment2Bark">https://github.com/JDYuuki/Comment2Bark</a>'));
        $form->addInput($notMyself);
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
     * 推送到Bark
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function bark_send($comment, $post) {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('Comment2Bark');
        $bark_key = $options->bark_key;
        $notMyself = $options->notMyself;
        
        if($comment['authorId'] == 1 && $notMyself == '1') {
            return  $comment;
        }

        $text = "您的博客收到了新的评论";
        $desp = $comment['author']."：".$comment['text'];

        $postdata = http_build_query(
            array(
                'title' => $text,
                'body' => $desp
            )
        );
            
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            ),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false
            )
        );

        $bark = "https://api.day.app/";

        $context  = stream_context_create($opts);
        $result = file_get_contents($bark.$bark_key, false, $context);
        
        return  $comment;
    }
}
