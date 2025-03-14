#### LskyUploader 插件使用教程

##### 插件简介
LskyUploader 是一个 Typecho 插件，用于将图片和其他文件上传至兰空图床（Lsky Pro）。它基于 isYangs 的 LskyProUpload 插件开发，经过优化和改进，提供稳定的文件上传功能。

##### 安装步骤
1. **下载插件**
   - 从 [GitHub](https://github.com/isYangs/LskyPro-Plugins) 或作者博客下载插件文件
   - 确保插件文件夹命名为 `LskyUploader`

2. **上传到服务器**
   - 将 `LskyUploader` 文件夹上传至 Typecho 的 `usr/plugins/` 目录

3. **启用插件**
   - 登录 Typecho 后台
   - 进入“插件管理”
   - 找到 “LskyUploader” 并点击“启用”

##### 配置说明
启用插件后，需要进行以下配置：
1. **进入设置**
   - 在插件管理页面，点击“LskyUploader”的“设置”链接

2. **填写配置项**
   - **Api**
     - 输入您的兰空图床域名
     - 格式：`https://your-lsky-domain.com`
     - 注意：不要在末尾添加斜杠 `/`
   - **Token**
     - 输入兰空图床的 API Token
     - 获取方式：在兰空后台“个人中心 - API令牌”中生成
     - 格式示例：`1|UYsgSjmtTkPjS8qPaLl98dJwdVtU492vQbDFI6pg`
   - **Strategy_id**（可选）
     - 存储策略 ID，若留空则使用默认策略
     - 获取方式：在兰空后台“存储策略”中查看
   - **Album_id**（可选）
     - 相册 ID，若留空则不指定相册
     - 获取方式：在兰空后台“相册管理”中查看

3. **保存设置**
   - 点击“保存设置”按钮完成配置

##### 使用方法
1. **上传文件**
   - 在 Typecho 后台撰写文章时，点击编辑器中的“添加媒体”
   - 选择图片或其他文件上传
   - 插件会自动将文件上传至兰空图床

2. **查看上传结果**
   - 上传成功后，文件会返回一个 URL 链接
   - 图片会直接显示在文章编辑器中

##### 注意事项
- **支持的文件类型**
  - 图片格式：gif、jpg、jpeg、png、tiff、bmp、ico、psd、webp
  - 其他文件：根据 Typecho 默认支持类型
- **日志查看**
  - 上传过程中的错误日志保存在插件目录下的 `logs/upload.log` 文件中
  - 可用于排查问题
- **网络要求**
  - 确保服务器能正常访问兰空图床的 API 接口

##### 常见问题
1. **上传失败怎么办？**
   - 检查 API 地址是否正确
   - 确认 Token 是否有效
   - 查看 `logs/upload.log` 中的错误信息

2. **图片无法显示？**
   - 确认兰空图床域名是否可访问
   - 检查存储策略的访问权限设置

##### 获取帮助
- 作者博客：[https://xiangming.site/](https://xiangming.site/)
- 原插件地址：[https://github.com/isYangs/LskyPro-Plugins](https://github.com/isYangs/LskyPro-Plugins)
- 兰空官网：[https://www.lsky.pro/](https://www.lsky.pro/)

---

以上解决方案和教程应该能满足你的需求。如果还有其他问题，欢迎随时提问！
