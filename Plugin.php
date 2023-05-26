<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 博客评论通过QQ机器人发送到QQ 使用默认API需添加QQ 2280858259 为好友 才能正常接收通知 #修改 by imsun
 *
 * @package CommentsByQQ
 * @version 1.0.2
 * @link https://blog.asbid.cn/
 */
class CommentsByQQ_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常.
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('CommentsByQQ_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array('CommentsByQQ_Plugin', 'render');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常.
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
      
    }

    /**
     * 获取插件配置面板.
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $default_url = 'http://bot.asbid.cn';
        if (defined('__TYPECHO_COMMENT_BY_QQ_API_URL__')) {
            $default_url = __TYPECHO_COMMENT_BY_QQ_API_URL__;
        }

        /** 分类名称 */
        $qqboturl = new Typecho_Widget_Helper_Form_Element_Text('qqboturl', NULL, '', _t('API 地址：'), _t('在此处填写使用QQ机器人的相关 API Key 地址。使用默认API需添加QQ 2280858259 为好友 才能正常接收通知。缺省值为「' . htmlspecialchars($default_url) . '」。'));
        $name = new Typecho_Widget_Helper_Form_Element_Text('qq', NULL, '', _t('接收消息的 QQ 号：'), _t('用于接收机器人推送通知的 QQ 账号数字 ID'));

        $form->addInput($name);
        $form->addInput($qqboturl);
    }

    /**
     * 个人用户的配置面板.
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {

    }

    /**
     * 发送QQ消息.
  
       你有新的评论：「{评论作者}」在文章《{文章标题}》中发表了评论！
       评论内容：{评论正文}
       永久链接地址:
  
       {永久链接地址}
  
     *
     * @access public
     * @param $comment 调用参数
     * @return void
     */
    public static function render($comment)
    {
        $options = Helper::options();
        if ($comment->status != "approved") {
            return;
        }

        $cq_url = $options->plugin('CommentsByQQ')->qqboturl;
        if (empty($cq_url)) { // 解决空地址问题
            return;
        }
      
		if ($comment->authorId === $comment->ownerId) { // 如果是管理员自己发的评论则不发送通知
			return;
		}
      
        $msg = '你有新的评论：「' . $comment->author . '」在文章《' . $comment->title . '》中发表了评论！';
        $msg .= "\n评论内容:\n {$comment->text}\n永久链接地址：{$comment->permalink}";

        $_message_data_ = array(
            'user_id' => (int) trim($options->plugin('CommentsByQQ')->qq),
            'message' => str_replace(array("\r\n", "\r", "\n"), "\r\n",htmlspecialchars_decode(strip_tags($msg))) // Tomloi 2021-6-23：使用指定方式更改消息格式 
         );
         
         $ch = curl_init();
         curl_setopt_array($ch, array(
             CURLOPT_URL => "{$cq_url}/send_msg?" . http_build_query($_message_data_, '', '&'),
             CURLOPT_CONNECTTIMEOUT => 10,
             CURLOPT_TIMEOUT => 30,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_HEADER => false,
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_SSL_VERIFYHOST => 0));
         curl_exec($ch);
         curl_close($ch);
    }
}
