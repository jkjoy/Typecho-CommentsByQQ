<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 博客评论通过QQ机器人发送到QQ
 * 
 * @package CommentsByQQ 
 * @author 作者Pxwei 修改imsun
 * @version 1.0.0
 * @link http://www.imsun.pw
 */
class CommentsByQQ_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('CommentsByQQ_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array('CommentsByQQ_Plugin', 'render');
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
        /** 分类名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('qq', NULL, '', _t('接收消息的QQ号「需添加qq机器人153985848为好友。」：'));
        $form->addInput($name);
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
     * 发送QQ消息
     *
     * @access public
     * @param $comment 调用参数
     * @return void
     */
    public static function render($comment)
    {
	$options = Helper::options();
	 if($comment->ownerId==$comment->authorId)
	 return 0;
	 $ch = curl_init();
	 curl_setopt($ch,CURLOPT_URL,"http://qq.asbid.cn/send_private_msg?user_id=".$options->plugin('CommentsByQQ')->qq."&message=你有新的评论".$comment->permalink);
	 curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	 curl_setopt($ch,CURLOPT_HEADER,0);
	 curl_exec($ch);
	 curl_close($ch);
  }
}
