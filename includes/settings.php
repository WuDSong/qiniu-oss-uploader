<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 添加后台设置菜单
 */
function qiniu_oss_add_settings_menu() {
    add_options_page(
        '七牛云OSS设置',
        '七牛云OSS',
        'manage_options',
        'qiniu-oss-settings',
        'qiniu_oss_render_settings_page'
    );
}
add_action('admin_menu', 'qiniu_oss_add_settings_menu');

/**
 * 注册设置项
 */
function qiniu_oss_register_settings() {
    // 七牛基础配置
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_access_key');
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_secret_key');
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_bucket');
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_zone');
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_domain');
    // 本地保存选项
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_save_local');
    // 文件重命名选项
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_enable_rename');
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_rename_type');
    // 存储路径选项
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_storage_path_type');
    register_setting('qiniu_oss_settings_group', 'qiniu_oss_custom_path');
}
add_action('admin_init', 'qiniu_oss_register_settings');

/**
 * 渲染设置页面
 */
function qiniu_oss_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>七牛云OSS图片上传设置</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('qiniu_oss_settings_group');
            do_settings_sections('qiniu_oss_settings_group');
            ?>

            <table class="form-table">
                <!-- 七牛AK/SK -->
                <tr valign="top">
                    <th scope="row">Access Key</th>
                    <td>
                        <input type="text" name="qiniu_oss_access_key" 
                               value="<?php echo esc_attr(get_option('qiniu_oss_access_key')); ?>" 
                               class="regular-text" required>
                        <p class="description">七牛云控制台 -> 个人中心 -> 密钥管理 获取</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Secret Key</th>
                    <td>
                        <input type="text" name="qiniu_oss_secret_key" 
                               value="<?php echo esc_attr(get_option('qiniu_oss_secret_key')); ?>" 
                               class="regular-text" required>
                    </td>
                </tr>

                <!-- Bucket配置 -->
                <tr valign="top">
                    <th scope="row">存储空间名称(Bucket)</th>
                    <td>
                        <input type="text" name="qiniu_oss_bucket" 
                               value="<?php echo esc_attr(get_option('qiniu_oss_bucket')); ?>" 
                               class="regular-text" required>
                    </td>
                </tr>

                <!-- 区域选择 -->
                <tr valign="top">
                    <th scope="row">存储区域</th>
                    <td>
                        <select name="qiniu_oss_zone" required>
                            <option value="z0" <?php selected(get_option('qiniu_oss_zone'), 'z0'); ?>>华东-浙江 z0</option>
                            <option value="z1" <?php selected(get_option('qiniu_oss_zone'), 'z1'); ?>>华北-河北 z1</option>
                            <option value="z2" <?php selected(get_option('qiniu_oss_zone'), 'z2'); ?>>华南-广东 z2</option>
                            <option value="na0" <?php selected(get_option('qiniu_oss_zone'), 'na0'); ?>>北美-洛杉矶 na0</option>
                            <option value="as0" <?php selected(get_option('qiniu_oss_zone'), 'as0'); ?>>亚太-新加坡（原东南亚） as0</option>
                            <option value="cn-east-2" <?php selected(get_option('qiniu_oss_zone'), 'cn-east-2'); ?>>华东-浙江2 cn-east-2</option>
                            <option value="ap-southeast-2" <?php selected(get_option('qiniu_oss_zone'), 'ap-southeast-2'); ?>>亚太-河内 ap-southeast-2</option>
                            <option value="ap-southeast-3" <?php selected(get_option('qiniu_oss_zone'), 'ap-southeast-3'); ?>>亚太-胡志明 ap-southeast-3</option>
                        </select>
                    </td>
                </tr>

                <!-- CDN域名 -->
                <tr valign="top">
                    <th scope="row">CDN域名</th>
                    <td>
                        <input type="text" name="qiniu_oss_domain" 
                               value="<?php echo esc_attr(get_option('qiniu_oss_domain')); ?>" 
                               class="regular-text" required>
                        <p class="description">七牛云存储空间绑定的域名（如：https://cdn.example.com）</p>
                    </td>
                </tr>

                <!-- 本地保存原图 -->
                <tr valign="top">
                    <th scope="row">本地保存原图</th>
                    <td>
                        <label>
                            <input type="radio" name="qiniu_oss_save_local" value="yes" 
                                   <?php checked(get_option('qiniu_oss_save_local'), 'yes'); ?>> 是
                        </label>
                        <label style="margin-left: 20px;">
                            <input type="radio" name="qiniu_oss_save_local" value="no" 
                                   <?php checked(get_option('qiniu_oss_save_local'), 'no'); ?>> 否
                        </label>
                        <p class="description">选择"否"则上传后删除服务器本地原图，仅保留七牛云副本</p>
                    </td>
                </tr>

                <!-- 文件重命名 -->
                <tr valign="top">
                    <th scope="row">启用文件重命名</th>
                    <td>
                        <label>
                            <input type="radio" name="qiniu_oss_enable_rename" value="yes" 
                                   <?php checked(get_option('qiniu_oss_enable_rename', 'yes'), 'yes'); ?>> 是
                        </label>
                        <label style="margin-left: 20px;">
                            <input type="radio" name="qiniu_oss_enable_rename" value="no" 
                                   <?php checked(get_option('qiniu_oss_enable_rename', 'yes'), 'no'); ?>> 否
                        </label>
                        <p class="description">选择"是"则上传时对文件进行重命名，避免文件名冲突</p>
                    </td>
                </tr>

                <!-- 重命名方式 -->
                <tr valign="top">
                    <th scope="row">重命名方式</th>
                    <td>
                        <select name="qiniu_oss_rename_type" required>
                            <option value="uuid" <?php selected(get_option('qiniu_oss_rename_type', 'uuid'), 'uuid'); ?>>UUID命名</option>
                            <option value="timestamp" <?php selected(get_option('qiniu_oss_rename_type', 'uuid'), 'timestamp'); ?>>时间戳命名</option>
                            <option value="timestamp_rand" <?php selected(get_option('qiniu_oss_rename_type', 'uuid'), 'timestamp_rand'); ?>>时间戳+随机数</option>
                            <option value="original" <?php selected(get_option('qiniu_oss_rename_type', 'uuid'), 'original'); ?>>保留原文件名</option>
                        </select>
                        <p class="description">选择文件重命名的方式，UUID命名可确保唯一性</p>
                    </td>
                </tr>

                <!-- 存储路径设置 -->
                <tr valign="top">
                    <th scope="row">存储路径类型</th>
                    <td>
                        <label>
                            <input type="radio" name="qiniu_oss_storage_path_type" value="default" 
                                   <?php checked(get_option('qiniu_oss_storage_path_type', 'default'), 'default'); ?>> 默认路径（/wordpress/年/月/）
                        </label>
                        <label style="margin-left: 20px;">
                            <input type="radio" name="qiniu_oss_storage_path_type" value="custom" 
                                   <?php checked(get_option('qiniu_oss_storage_path_type', 'default'), 'custom'); ?>> 自定义路径
                        </label>
                        <p class="description">选择文件在对象存储中的保存路径类型</p>
                    </td>
                </tr>

                <!-- 自定义存储路径 -->
                <tr valign="top">
                    <th scope="row">自定义存储路径</th>
                    <td>
                        <input type="text" name="qiniu_oss_custom_path" 
                               value="<?php echo esc_attr(get_option('qiniu_oss_custom_path', '/')); ?>" 
                               class="regular-text">
                        <p class="description">输入自定义存储路径，格式示例：<code>/wds/</code> 或 <code>/upload/</code>。注意：必须以斜杠开头和结尾，否则可能导致路径错误。</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}