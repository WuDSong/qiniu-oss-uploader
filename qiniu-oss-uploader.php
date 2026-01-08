<?php
/*
Plugin Name: 七牛云OSS图片上传插件
Plugin URI: 
Description: WordPress上传图片自动同步到七牛云OSS，支持配置是否本地保存原图
Version: 1.3
Author: wudongsong
Author URI: 
License: MIT
*/

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 引入七牛SDK自动加载文件
require_once plugin_dir_path(__FILE__) . 'includes/php-sdk-7.14.0/php-sdk-7.14.0/autoload.php';

// 引入设置页面逻辑
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

/**
 * 核心上传逻辑：拦截WordPress文件上传，同步到七牛云
 * @param array $upload 上传结果数组
 * @param string $context 上传上下文
 * @return array
 */
function qiniu_oss_upload_file($upload, $context) {
    // 仅处理图片类型
    $allowed_mimes = array(
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    );
    if (!in_array($upload['type'], $allowed_mimes)) {
        return $upload;
    }

    // 获取七牛配置
    $access_key = get_option('qiniu_oss_access_key');
    $secret_key = get_option('qiniu_oss_secret_key');
    $bucket = get_option('qiniu_oss_bucket');
    $zone = get_option('qiniu_oss_zone');
    $domain = get_option('qiniu_oss_domain');
    $save_local = get_option('qiniu_oss_save_local', 'yes');
    // 获取重命名配置
    $enable_rename = get_option('qiniu_oss_enable_rename', 'yes');
    $rename_type = get_option('qiniu_oss_rename_type', 'uuid');
    // 获取存储路径配置
    $storage_path_type = get_option('qiniu_oss_storage_path_type', 'default');
    $custom_path = get_option('qiniu_oss_custom_path', '/');

    // 配置验证
    if (empty($access_key) || empty($secret_key) || empty($bucket) || empty($zone) || empty($domain)) {
        return $upload;
    }

    try {
        // 1. 初始化七牛鉴权和配置
        $auth = new \Qiniu\Auth($access_key, $secret_key);
        $token = $auth->uploadToken($bucket);
        
        // 配置七牛区域
        $zone_obj = null;
        switch ($zone) {
            case 'z0': $zone_obj = \Qiniu\Zone::zonez0(); break; // 华东-浙江
            case 'z1': $zone_obj = \Qiniu\Zone::zonez1(); break; // 华北-河北
            case 'z2': $zone_obj = \Qiniu\Zone::zonez2(); break; // 华南-广东
            case 'na0': $zone_obj = \Qiniu\Zone::zoneNa0(); break; // 北美-洛杉矶
            case 'as0': $zone_obj = \Qiniu\Zone::zoneAs0(); break; // 亚太-新加坡
            case 'cn-east-2': $zone_obj = \Qiniu\Zone::zoneCnEast2(); break; // 华东-浙江2
            default:
                // 对于其他区域，使用自动查询
                break;
        }
        $config = new \Qiniu\Config($zone_obj);

        // 2. 上传文件到七牛
        $uploadMgr = new \Qiniu\Storage\UploadManager($config);
        $file_path = $upload['file'];
        $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $original_name = basename($file_path);
        
        // 生成文件名
        $file_name = '';
        if ($enable_rename === 'yes') {
            switch ($rename_type) {
                case 'timestamp':
                    $file_name = time() . '.' . $file_ext;
                    break;
                case 'timestamp_rand':
                    $file_name = time() . '_' . mt_rand(1000, 9999) . '.' . $file_ext;
                    break;
                case 'original':
                    $file_name = $original_name;
                    break;
                case 'uuid':
                default:
                    $file_name = wp_generate_uuid4() . '.' . $file_ext;
                    break;
            }
        } else {
            $file_name = $original_name;
        }
        
        // 处理存储路径
        $storage_path = '';
        if ($storage_path_type === 'default') {
            // 默认路径：/wordpress/年/月/
            $year = date('Y');
            $month = date('m');
            $storage_path = 'wordpress/' . $year . '/' . $month . '/';
        } else {
            // 自定义路径
            $custom_path = trim($custom_path, '/');
            if (!empty($custom_path)) {
                $storage_path = $custom_path . '/';
            }
        }
        
        // 组合完整的存储路径和文件名
        $full_file_name = $storage_path . $file_name;
        
        list($ret, $err) = $uploadMgr->putFile($token, $full_file_name, $file_path);

        if ($err !== null) {
            throw new Exception($err->message());
        }

        // 3. 处理七牛返回结果
        $qiniu_url = rtrim($domain, '/') . '/' . $ret['key'];
        
        // 4. 替换WordPress上传结果的URL为七牛URL
        $upload['url'] = $qiniu_url;
        $upload['path'] = $qiniu_url; // 覆盖本地路径（可选）

        // 5. 如果选择不保存本地，删除服务器原图
        if ($save_local !== 'yes' && file_exists($file_path)) {
            unlink($file_path);
        }

    } catch (Exception $e) {
        // 上传失败则保留本地上传结果
        error_log('七牛云上传失败：' . $e->getMessage());
    }

    return $upload;
}
add_filter('wp_handle_upload', 'qiniu_oss_upload_file', 10, 2);

/**
 * 修复媒体库中图片的URL显示
 * @param string $url 媒体文件URL
 * @param int $attachment_id 附件ID
 * @return string
 */
function qiniu_oss_fix_media_url($url, $attachment_id) {
    $attachment = get_post($attachment_id);
    if ($attachment->post_mime_type && str_starts_with($attachment->post_mime_type, 'image/')) {
        $qiniu_domain = get_option('qiniu_oss_domain');
        if (!empty($qiniu_domain) && str_contains($url, ABSPATH)) {
            // 替换本地URL为七牛URL（如果上传成功）
            $meta = wp_get_attachment_metadata($attachment_id);
            if (!empty($meta['qiniu_url'])) {
                return $meta['qiniu_url'];
            }
        }
    }
    return $url;
}
add_filter('wp_get_attachment_url', 'qiniu_oss_fix_media_url', 10, 2);

/**
 * 保存附件元数据时记录七牛URL
 * @param array $metadata 附件元数据
 * @param int $attachment_id 附件ID
 * @return array
 */
function qiniu_oss_save_attachment_meta($metadata, $attachment_id) {
    $file = get_attached_file($attachment_id);
    $qiniu_domain = get_option('qiniu_oss_domain');
    if (!empty($qiniu_domain) && str_starts_with($file, $qiniu_domain)) {
        $metadata['qiniu_url'] = $file;
    }
    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'qiniu_oss_save_attachment_meta', 10, 2);