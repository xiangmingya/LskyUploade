<?php
namespace TypechoPlugin\LskyUploader;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Layout; // 正确引用 Layout 类
use Typecho\Common;
use Widget\Options;
use Widget\Upload;
use CURLFile;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * LskyUploader - Typecho 插件，用于将文件上传至兰空图床
 *
 * @package LskyUploader
 * @author  湘铭呀
 * @version 1.0.0
 * @link https://feng.xiangming.site
 */
class Plugin implements PluginInterface
{
    const UPLOAD_DIR  = '/usr/uploads';
    const PLUGIN_NAME = 'LskyUploader';

    public static function activate()
    {
        \Typecho\Plugin::factory('Widget_Upload')->uploadHandle     = __CLASS__.'::uploadHandle';
        \Typecho\Plugin::factory('Widget_Upload')->modifyHandle     = __CLASS__.'::modifyHandle';
        \Typecho\Plugin::factory('Widget_Upload')->deleteHandle     = __CLASS__.'::deleteHandle';
        \Typecho\Plugin::factory('Widget_Upload')->attachmentHandle = __CLASS__.'::attachmentHandle';
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
        // 添加自定义描述
        $description = new Layout();
        $description->html(
            '<div class="description">' .
            '<h3>插件介绍：</h3>' .
            '<p>本插件基于isYangs开发的LskyProUpload插件进行修改。   ' .
            '<a href="https://xiangming.site/929.html" target="_blank">插件使用方法</a>   ' .
            '<a href="https://github.com/isYangs/LskyPro-Plugins" target="_blank">原插件地址</a>   ' .
            '<a href="https://www.lsky.pro/" target="_blank">兰空官网</a></p>' .
            '</div>'
        );
        $form->addItem($description);

        $api = new Text(
            'api',
            NULL,
            '',
            'Api：',
            '只需填写域名包含 http 或 https 无需<code style="padding: 2px 4px; font-size: 90%; color: #c7254e; background-color: #f9f2f4; border-radius: 4px;"> / </code>结尾<br>' .
            '<code style="padding: 2px 4px; font-size: 90%; color: #c7254e; background-color: #f9f2f4; border-radius: 4px;">示例地址：https://lsky.pro</code>'
        );
        $form->addInput($api);

        $token = new Text(
            'token',
            NULL,
            '',
            'Token：',
            '请按示例严格填写：<code style="padding: 2px 4px; font-size: 90%; color: #c7254e; background-color: #f9f2f4; border-radius: 4px;">1|UYsgSjmtTkPjS8qPaLl98dJwdVtU492vQbDFI6pg</code>'
        );
        $form->addInput($token);

        $strategy_id = new Text(
            'strategy_id',
            NULL,
            '',
            'Strategy_id：',
            '如果为空，则为默认存储id'
        );
        $form->addInput($strategy_id);

        $album_id = new Text(
            'album_id',
            NULL,
            '',
            'Album_id：',
            '如果为空，则不指定相册。填写相册ID可将图片上传至指定相册中'
        );
        $form->addInput($album_id);
    }

    public static function personalConfig(Form $form)
    {
    }

    public static function uploadHandle($file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $ext = self::_getSafeName($file['name']);

        if (!Upload::checkFileType($ext) || Common::isAppEngine()) {
            return false;
        }

        if (self::_isImage($ext)) {
            return self::_uploadImg($file, $ext);
        }

        return self::_uploadOtherFile($file, $ext);
    }

    public static function deleteHandle(array $content): bool
    {
        $ext = $content['attachment']->type;

        if (self::_isImage($ext)) {
            return self::_deleteImg($content);
        }

        return unlink($content['attachment']->path);
    }

    public static function modifyHandle($content, $file)
    {
        if (empty($file['name'])) {
            return false;
        }
        $ext = self::_getSafeName($file['name']);
        if ($content['attachment']->type != $ext || Common::isAppEngine()) {
            return false;
        }

        if (!self::_getUploadFile($file)) {
            return false;
        }

        if (self::_isImage($ext)) {
            self::_deleteImg($content);
            return self::_uploadImg($file, $ext);
        }

        return self::_uploadOtherFile($file, $ext);
    }

    public static function attachmentHandle(array $content): string
    {
        return $content['attachment']->path ?? '';
    }

    private static function _getUploadDir($ext = ''): string
    {
        if (self::_isImage($ext)) {
            $url = parse_url(Options::alloc()->siteUrl);
            $DIR = str_replace('.', '_', $url['host']);
            return '/' . $DIR . self::UPLOAD_DIR;
        } elseif (defined('__TYPECHO_UPLOAD_DIR__')) {
            return __TYPECHO_UPLOAD_DIR__;
        } else {
            $path = Common::url(self::UPLOAD_DIR, __TYPECHO_ROOT_DIR__);
            return $path;
        }
    }

    private static function _getUploadFile($file): string
    {
        return $file['tmp_name'] ?? ($file['bytes'] ?? ($file['bits'] ?? ''));
    }

    private static function _getSafeName(&$name): string
    {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);

        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    private static function _makeUploadDir($path): bool
    {
        $path    = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last    = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last    = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last)) {
            return false;
        }

        $stat  = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::_makeUploadDir($path);
    }

    private static function _isImage($ext): bool
    {
        $img_ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'tiff', 'bmp', 'ico', 'psd', 'webp', 'JPG', 'BMP', 'GIF', 'PNG', 'JPEG', 'ICO', 'PSD', 'TIFF', 'WEBP');
        return in_array($ext, $img_ext_arr);
    }

    private static function _uploadOtherFile($file, $ext)
    {
        $dir = self::_getUploadDir($ext) . '/' . date('Y') . '/' . date('m');
        if (!self::_makeUploadDir($dir)) {
            return false;
        }

        $path = sprintf('%s/%u.%s', $dir, crc32(uniqid()), $ext);
        if (!isset($file['tmp_name']) || !@move_uploaded_file($file['tmp_name'], $path)) {
            return false;
        }

        return [
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'] ?? filesize($path),
            'type' => $ext,
            'mime' => @Common::mimeContentType($path)
        ];
    }

    private static function _uploadImg($file, $ext)
    {
        try {
            $options = Options::alloc()->plugin(self::PLUGIN_NAME);
            $api     = $options->api . '/api/v1/upload';
            $token   = 'Bearer ' . $options->token;
            $strategyId = $options->strategy_id;
            $albumId = $options->album_id;

            $tmp = self::_getUploadFile($file);
            if (empty($tmp)) {
                throw new \Exception('无法获取上传文件');
            }

            // 使用唯一的临时文件名
            $tempDir = sys_get_temp_dir();
            $tempName = uniqid('lsky_upload_', true) . '_' . $file['name'];
            $tempPath = $tempDir . '/' . $tempName;

            if (!rename($tmp, $tempPath)) {
                throw new \Exception('临时文件处理失败');
            }

            if (!is_readable($tempPath)) {
                throw new \Exception('临时文件不可读');
            }

            $params = ['file' => new CURLFile($tempPath)];
            if ($strategyId) {
                $params['strategy_id'] = $strategyId;
            }
            if ($albumId) {
                $params['album_id'] = $albumId;
            }

            $res = self::_curlPost($api, $params, $token);

            // 确保日志目录存在
            $logFile = __DIR__ . '/logs/upload.log';
            if (!is_dir(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }

            // 记录API请求响应
            $logContent = date('Y-m-d H:i:s') . " Response: " . $res . PHP_EOL;
            file_put_contents($logFile, $logContent, FILE_APPEND);

            if (!$res) {
                throw new \Exception('API请求返回为空');
            }

            $json = json_decode($res, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON解析错误: ' . json_last_error_msg());
            }

            if ($json['status'] === false) {
                throw new \Exception($json['message'] ?? '未知错误');
            }

            $data = $json['data'];
            return [
                'img_key' => $data['key'],
                'img_id' => $data['md5'],
                'name'   => $file['name'],
                'path'   => $data['links']['url'],
                'size'   => $data['size'] * 1024,
                'type'   => $data['extension'],
                'mime'   => $data['mimetype'],
                'description'  => $data['mimetype'],
            ];
        } catch (\Exception $e) {
            // 记录详细错误信息
            $logFile = __DIR__ . '/logs/upload.log';
            $errorLog = date('Y-m-d H:i:s') . " Error: " . $e->getMessage() .
                "\nFile: " . $e->getFile() .
                "\nLine: " . $e->getLine() .
                "\nTrace: " . $e->getTraceAsString() . PHP_EOL;
            file_put_contents($logFile, $errorLog, FILE_APPEND);
            return false;
        } finally {
            // 清理临时文件
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private static function _deleteImg(array $content): bool
    {
        $options = Options::alloc()->plugin(self::PLUGIN_NAME);
        $api     = $options->api . '/api/v1/images';
        $token   = 'Bearer ' . $options->token;

        $id = $content['attachment']->img_key;

        if (empty($id)) {
            return false;
        }

        $res  = self::_curlDelete($api . '/' . $id, ['key' => $id], $token);
        $json = json_decode($res, true);

        if (!is_array($json)) {
            return false;
        }

        return true;
    }

    private static function _curlDelete($api, $post, $token)
    {
        $headers = array(
            "Content-Type: multipart/form-data",
            "Accept: application/json",
            "Authorization: " . $token,
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    private static function _curlPost($api, $post, $token, $retries = 3)
    {
        $headers = array(
            "Content-Type: multipart/form-data",
            "Accept: application/json",
            "Authorization: " . $token,
            "Connection: keep-alive"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 120);

        $attempt = 0;
        do {
            $res = curl_exec($ch);
            $attempt++;

            if (!$res && $attempt < $retries) {
                sleep(1); // 等待1秒后重试
                continue;
            }
            break;
        } while (true);

        // 记录curl错误信息
        $logFile = __DIR__ . '/logs/upload.log';
        if (!$res) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " Curl Error: " . $error . " (" . $errno . ")" . PHP_EOL, FILE_APPEND);
        }

        // 记录HTTP状态码
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " HTTP Status Code: " . $httpCode . PHP_EOL, FILE_APPEND);

        curl_close($ch);
        return $res;
    }
}