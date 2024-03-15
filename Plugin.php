<?php
/**
 * 博客评论通过QQ机器人发送到QQ 使用默认API需添加QQ 2280858259 为好友 才能正常接收通知
 *
 * @package CommentsByQQ
 * @author 老孙
 * @version 1.0.4
 * @link https://blog.asbid.cn/
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class CommentsByQQ_Plugin implements Typecho_Plugin_Interface
{

    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('CommentsByQQ_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array('CommentsByQQ_Plugin', 'render');
    }

    public static function deactivate()
    {
      
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $default_url = 'https://bot.asbid.cn';
        if (defined('__TYPECHO_COMMENT_BY_QQ_API_URL__')) {
            $default_url = __TYPECHO_COMMENT_BY_QQ_API_URL__;
        }

        /** 分类名称 */
        $qqboturl = new Typecho_Widget_Helper_Form_Element_Text('qqboturl', NULL, '', _t('API 地址：'), _t('在此处填写使用QQ机器人的相关 API Key 地址。使用默认API需添加QQ 2280858259 为好友 才能正常接收通知。缺省值为「' . htmlspecialchars($default_url) . '」。'));
        $name = new Typecho_Widget_Helper_Form_Element_Text('qq', NULL, '', _t('接收消息的 QQ 号：'), _t('用于接收机器人推送通知的 QQ 账号数字 ID'));

        $form->addInput($name);
        $form->addInput($qqboturl);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {

    }

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
      
        $msg = '你有新的评论：\n「' . $comment->author . '」在文章《' . $comment->title . '》中发表了评论！';
        $msg .= "\n评论内容:\n {$comment->text}\n永久链接地址：{$comment->permalink}";

        $_message_data_ = array(
            'user_id' => (int) trim($options->plugin('CommentsByQQ')->qq),
            'message' => str_replace(array("\r\n", "\r", "\n"), "\r\n",htmlspecialchars_decode(strip_tags($msg))) 
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
