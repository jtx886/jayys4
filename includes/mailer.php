<?php
// SMTP邮件发送 - 使用Socket实现（兼容无扩展环境）

require_once __DIR__ . '/../config/config.php';

class Mailer {

    public static function send($to, $subject, $body) {
        // 优先尝试PHPMailer
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return self::sendWithPHPMailer($to, $subject, $body);
        }
        // 备选：使用内置mail()
        if (function_exists('mail')) {
            return self::sendWithMail($to, $subject, $body);
        }
        // 最后：socket SMTP
        return self::sendWithSocket($to, $subject, $body);
    }

    private static function sendWithPHPMailer($to, $subject, $body) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function sendWithMail($to, $subject, $body) {
        $boundary = md5(uniqid());
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: =?UTF-8?B?' . base64_encode(SMTP_FROM_NAME) . '?= <' . SMTP_FROM . '>',
            'Reply-To: ' . SMTP_FROM,
            'X-Mailer: PHP/' . phpversion()
        ];
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private static function sendWithSocket($to, $subject, $body) {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $user = SMTP_USER;
        $pass = SMTP_PASS;
        $from = SMTP_FROM;
        $fromName = SMTP_FROM_NAME;

        $headers = [
            'Date: ' . date('r'),
            'To: <' . $to . '>',
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit'
        ];
        $data = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";

        // SSL连接
        $fp = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 30);
        if (!$fp) return false;

        $read = function() use ($fp) {
            $response = '';
            while ($str = fgets($fp, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $response;
        };

        $send = function($cmd) use ($fp, $read) {
            fputs($fp, $cmd . "\r\n");
            return $read();
        };

        if (substr($read(), 0, 3) != '220') { fclose($fp); return false; }
        if (substr($send('EHLO ' . $_SERVER['HTTP_HOST']), 0, 3) != '250') { fclose($fp); return false; }
        if (substr($send('AUTH LOGIN'), 0, 3) != '334') { fclose($fp); return false; }
        if (substr($send(base64_encode($user)), 0, 3) != '334') { fclose($fp); return false; }
        if (substr($send(base64_encode($pass)), 0, 3) != '235') { fclose($fp); return false; }
        if (substr($send('MAIL FROM:<' . $from . '>'), 0, 3) != '250') { fclose($fp); return false; }
        if (substr($send('RCPT TO:<' . $to . '>'), 0, 3) != '250') { fclose($fp); return false; }
        if (substr($send('DATA'), 0, 3) != '354') { fclose($fp); return false; }
        fputs($fp, $data);
        $resp = $read();
        $send('QUIT');
        fclose($fp);
        return substr($resp, 0, 3) == '250';
    }

    // 验证码邮件模板
    public static function verificationEmail($code) {
        return '
        <div style="max-width:600px;margin:0 auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",sans-serif;">
            <div style="text-align:center;padding:20px 0 30px;border-bottom:1px solid #f0f0f0;">
                <div style="width:64px;height:64px;margin:0 auto;background:linear-gradient(135deg,#01B4E4,#032541);border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:bold;">J</div>
                <h2 style="margin:16px 0 4px;color:#032541;font-size:24px;">Jay影视</h2>
                <p style="color:#888;margin:0;">邮箱验证码</p>
            </div>
            <div style="padding:30px 0;text-align:center;">
                <p style="color:#333;font-size:16px;line-height:1.6;margin:0 0 24px;">您好，感谢您注册Jay影视！请使用以下验证码完成验证，验证码有效期为10分钟。</p>
                <div style="display:inline-block;padding:16px 40px;background:linear-gradient(135deg,#01B4E4,#032541);border-radius:10px;color:#fff;font-size:32px;font-weight:bold;letter-spacing:8px;">' . $code . '</div>
            </div>
            <div style="padding:20px 0;border-top:1px solid #f0f0f0;color:#999;font-size:13px;text-align:center;">
                <p>如非本人操作，请忽略此邮件</p>
                <p>© ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
            </div>
        </div>';
    }

    // 封禁通知邮件
    public static function banNotification($reason, $banTime, $unbanTime) {
        return '
        <div style="max-width:600px;margin:0 auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",sans-serif;">
            <div style="text-align:center;padding:20px 0 30px;border-bottom:1px solid #f0f0f0;">
                <div style="width:64px;height:64px;margin:0 auto;background:linear-gradient(135deg,#e74c3c,#c0392b);border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;">⚠</div>
                <h2 style="margin:16px 0 4px;color:#c0392b;font-size:24px;">账号封禁通知</h2>
                <p style="color:#888;margin:0;">来自 ' . SITE_NAME . '</p>
            </div>
            <div style="padding:30px 0;">
                <p style="color:#333;font-size:15px;line-height:1.8;margin:0 0 16px;">您好，很抱歉地通知您，您的账号因违反平台规定已被封禁处理。</p>
                <div style="background:#fef9f9;border:1px solid #fdd;border-radius:8px;padding:20px;margin:16px 0;">
                    <p style="margin:0 0 12px;"><strong style="color:#c0392b;">封禁原因：</strong><span style="color:#333;">' . e($reason) . '</span></p>
                    <p style="margin:0 0 12px;"><strong style="color:#c0392b;">封禁时间：</strong><span style="color:#333;">' . $banTime . '</span></p>
                    <p style="margin:0;"><strong style="color:#c0392b;">解除时间：</strong><span style="color:#333;">' . $unbanTime . '</span></p>
                </div>
                <p style="color:#666;font-size:14px;line-height:1.6;margin:0;">如有疑问，请通过站内反馈联系管理员。</p>
            </div>
            <div style="padding:20px 0;border-top:1px solid #f0f0f0;color:#999;font-size:13px;text-align:center;">
                <p>© ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
            </div>
        </div>';
    }

    // 管理员通知邮件
    public static function adminNotification($content) {
        return '
        <div style="max-width:600px;margin:0 auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",sans-serif;">
            <div style="text-align:center;padding:20px 0 30px;border-bottom:1px solid #f0f0f0;">
                <div style="width:64px;height:64px;margin:0 auto;background:linear-gradient(135deg,#01B4E4,#032541);border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:bold;">J</div>
                <h2 style="margin:16px 0 4px;color:#032541;font-size:24px;">' . SITE_NAME . ' 通知</h2>
                <p style="color:#888;margin:0;">管理员消息</p>
            </div>
            <div style="padding:30px 0;">
                <div style="color:#333;font-size:15px;line-height:1.8;">' . nl2br(e($content)) . '</div>
            </div>
            <div style="padding:20px 0;border-top:1px solid #f0f0f0;color:#999;font-size:13px;text-align:center;">
                <p>© ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
            </div>
        </div>';
    }

    // 反馈回复邮件
    public static function feedbackReply($feedbackTitle, $replyContent) {
        return '
        <div style="max-width:600px;margin:0 auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",sans-serif;">
            <div style="text-align:center;padding:20px 0 30px;border-bottom:1px solid #f0f0f0;">
                <div style="width:64px;height:64px;margin:0 auto;background:linear-gradient(135deg,#2ecc71,#27ae60);border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;">✓</div>
                <h2 style="margin:16px 0 4px;color:#032541;font-size:24px;">反馈已回复</h2>
                <p style="color:#888;margin:0;">来自 ' . SITE_NAME . '</p>
            </div>
            <div style="padding:30px 0;">
                <p style="color:#333;font-size:15px;line-height:1.8;margin:0 0 16px;">您好，您提交的反馈管理员已回复，内容如下：</p>
                <div style="background:#f7f9fc;border-left:4px solid #01B4E4;border-radius:4px;padding:16px;margin:16px 0;">
                    <p style="margin:0 0 8px;color:#888;font-size:13px;">原反馈：' . e($feedbackTitle) . '</p>
                </div>
                <div style="background:#f0faf4;border-left:4px solid #2ecc71;border-radius:4px;padding:16px;margin:16px 0;">
                    <p style="margin:0;color:#333;line-height:1.6;">' . nl2br(e($replyContent)) . '</p>
                </div>
            </div>
            <div style="padding:20px 0;border-top:1px solid #f0f0f0;color:#999;font-size:13px;text-align:center;">
                <p>© ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
            </div>
        </div>';
    }
}
