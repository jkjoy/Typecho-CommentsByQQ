<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 博客评论通过QQ机器人通知QQ
 * @package CommentsByQQ
 * @author 老孙
 * @version 1.0.7
 * @link https://www.imsun.org/
 */
class CommentsByQQ_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array(__CLASS__, 'render');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array(__CLASS__, 'render');
        return _t('插件已激活');
    }

    /**
     * 禁用插件方法
     */
    public static function deactivate()
    {
        return _t('插件已禁用');
    }

    /**
     * 插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $default_url = defined('__TYPECHO_COMMENT_BY_QQ_API_URL__') 
            ? __TYPECHO_COMMENT_BY_QQ_API_URL__ 
            : 'https://bot.asbid.cn';

        $qq = new Typecho_Widget_Helper_Form_Element_Text(
            'qq',
            NULL,
            '',
            _t('接收通知的QQ号'),
            _t('需要接收通知的QQ号码')
        );
        $form->addInput($qq->addRule('required', _t('必须填写QQ号')));

        $api_url = new Typecho_Widget_Helper_Form_Element_Text(
            'qqboturl',
            NULL,
            $default_url,
            _t('机器人API地址'),
            _t('默认：') . $default_url
        );
        $form->addInput($api_url->addRule('required', _t('必须填写API地址')));
    }

    /**
     * 个人配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 无需个人配置
    }

    /**
     * 发送QQ通知（修复乱码的核心方法）
     */
    public static function render($comment)
    {
        $options = Helper::options();
        
        // 1. 检查评论状态
        if ($comment->status != "approved") {
            error_log('[CommentsByQQ] 评论未通过审核: ' . $comment->status);
            return;
        }

        // 2. 获取配置
        $api_url = $options->plugin('CommentsByQQ')->qqboturl;
        $qq_num = $options->plugin('CommentsByQQ')->qq;

        if (empty($api_url) || empty($qq_num)) {
            error_log('[CommentsByQQ] 配置不完整: API_URL='.$api_url.' QQ='.$qq_num);
            return;
        }

        // 3. 跳过博主评论
        if ($comment->authorId === $comment->ownerId) {
            error_log('[CommentsByQQ] 跳过博主评论');
            return;
        }

        // 4. 构建消息（不再使用urlencode）
        $message = sprintf(
            "【新评论通知】\n"
            . "📝 评论者：%s\n"
            . "📖 文章标题：《%s》\n"
            . "💬 评论内容：%s\n"
            . "🔗 文章链接：%s",
            $comment->author,
            $comment->title,
            strip_tags($comment->text),
            $comment->permalink
        );

        // 5. 准备请求数据（使用JSON格式）
        $payload = [
            'user_id' => (int)$qq_num,
            'message' => $message
        ];

        // 6. 发送请求（使用POST+JSON）
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($api_url, '/') . '/send_msg',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=UTF-8',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log('[CommentsByQQ] CURL错误: ' . curl_error($ch));
        } else {
            error_log(sprintf(
                '[CommentsByQQ] 响应 [HTTP %d]: %s',
                $httpCode,
                substr($response, 0, 200)
            ));
        }
        curl_close($ch);
    }
}