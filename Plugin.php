<?php
/**
 * 评论telegram推送
 *
 * @package CommentToTelegram
 * @author xcsoft
 * @version 1.0
 * @link https://blog.xsot.cn
 *
 */

class CommentToTelegram_Plugin implements Typecho_Plugin_Interface
{
  public static function activate() {
    Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('CommentToTelegram_Plugin', 'parseComment');
    return _t('CommentToTelegram启动成功,请设置您的token以及id');
  }
  /* 禁用插件方法 */
  public static function deactivate() {}

  /* 插件配置方法 */
  public static function config(Typecho_Widget_Helper_Form $form) {
    echo '<h2>CommentToTelegram</h2>';
    echo '<p>欢迎使用CommentToTelegram,本插件用于telegram推送博客评论</p>';
    echo '<p>插件原理即在评论时调用telegram bot API来实现内容推送</p>';
    echo '<hr />';
    echo '<h2>使用方法</h2>';
    echo '<p>1.访问<a href="http://t.me/BotFather">@BotFather</a>输入/newbot依据提示创建一个属于自己的bot,记录下最后bot的token</p>';
    echo '<p>2.访问<a href="http://t.me/getidsbot">@getidsbot</a>输入/about记录自己的id</p>';
    echo '<p>依据提示填入下表</p>';
    echo '<h2>注意</h2>';
    echo '<p>使用此插件请确认您的服务器可以正常访问api.telegram.org</p>';
    echo '<p>Designed by <a href="https://xsot.cn" target="_blank">xcosft</a> | <a href="https://github.com/soxft/CommentToTelegram" target="_blank">Github</a>求star哈哈哈';
    echo '<hr />';

    $tg_token = new Typecho_Widget_Helper_Form_Element_Text('tg_token', NULL,'','token','填写机器人的token');
    $form->addInput($tg_token);

    $tg_id = new Typecho_Widget_Helper_Form_Element_Text('tg_id', NULL,'','填写你的id','可以添加https://t.me/getidsbot发送/about 获取你的id');
    $form->addInput($tg_id);
  }

  /* 个人用户的配置方法 */
  public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

  /* 插件实现方法 */
  public static function parseComment($comment) {
    $options = Typecho_Widget::widget('Widget_Options');
    $content = $options->plugin('CommentToTelegram');
    $token = $content->tg_token;
    $id = $content->tg_id;

    $com = array(
      'siteTitle' => $options->title,
      'author' => $comment->author,
      'mail' => $comment->mail,
      'ip' => $comment->ip,
      'title' => $comment->title,
      'text' => $comment->text,
      'status' => $comment->status,
      'manage' => $options->siteUrl . 'admin/manage-comments.php'
    );
    $status = array(
      "approved" => '通过',
      "waiting" => '待审',
      "spam" => '垃圾'
    );
    $status = $status[$com['status']];
    $time = date("Y-m-d H:i:s");
    $text = $com['author'] . "在您的文章《" . $com['title'] . "》上有新的评论:\n\n「" . $com['text'] . "」\n\n评论邮箱: " . $com['mail'] . "   评论ip：" . $com['ip'] . "\n评论时间：" . $time . "  评论状态：" . $status;

    $reply = array(
      "inline_keyboard" => array(
        array(
          array(
            'text' => "管理链接",
            'url' => $com['manage']
          )
        )
      )
    );

    $data = array(
      "chat_id" => $id,
      "text" => $text,
      "reply_markup" => $reply,
      "disable_web_page_preview" => true
    );
    CommentToTelegram_Plugin::sendMessage($token,$data);
  }
  private static function sendMessage($token,$data)
  {
    $data_string = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot$token/sendMessage");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json; charset=utf-8',
      'Content-Length: ' . strlen($data_string))
    );
    ob_start();
    curl_exec($ch);
    $return_content = ob_get_contents();
    ob_end_clean();
    $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return json_decode($return_content,true);
  }
}