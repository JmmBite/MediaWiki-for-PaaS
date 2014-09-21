# MediaWiki 1.23.x for SAE

## 一、安装前配置

### 1、启动`Sina App Engine`服务

| 服务名称 | 备注 |
| --- | --- |
| MySQL |  |
| Storage | 新建domain：`mediawiki` |
| Memcache | 容量：`10M` |

### 2、修改`config.yaml`和`LocalSettings.php`文件

* 打开`config.yaml`，分别将`<<appname>>`和`<<version>>`修改为您的App Name和版本；

* 将`LocalSettings-Sample.php`重命名为`LocalSettings.php`，配置相关参数；

* 配置 Email，在`LocalSettings.php`中找到`$wgSMTP`设置如下：
<table>
<tr>
<td>Gmail</td>
<td>
```php
$wgSMTP = array(
  'smtp'     => 'smtp.gmail.com',
  'port'     => '587',//465(ssl) 或 587(tls) 、25(ssl)
  'tls'      => 'true',//用SSL连接, tls = ssl_v3
  'username' => '...@gmail.com',
  'password' => '...'
);
```
</td>
</tr>
<tr>
<td>QQ Mail</td>
<td>
```php
$wgSMTP = array(
  'smtp'     => 'smtp.qq.com',
  'port'     => '25',//465或587、25
  'tls'      => 'false',//用SSL连接, tls = ssl_v3
  'username' => '...@qq.com',
  'password' => '...'
);
```
</td>
</tr>
</table>

## 二、上传代码 & 安装

上传代码后，安装地址：http://`<<appName>>`.sinaapp.com/mw-config/
> Secret Key : `SAE_SECRETKEY`<br>
> Upgrade key : `SAE_ACCESSKEY`


## 三、精简语言文件

在 MediaWiki 安装程序中，languages 文件占了一半多的空间大小

主要来自于文件夹`languages\i18n\`中的`.json`文件

可以删除不需要的`.json`文件，仅仅保留`languages\i18n\`中的以下文件即可

| languages/i18n/* | languages/Names.php |
| --- | --- |
| en.json       | 'en' => 'English', |
| | |
| gan.json      | 'gan' => '贛語', |
| gan-hans.json | 'gan-hans' => "赣语（简体）", |
| gan-hant.json | 'gan-hant' => "贛語（繁體）", |
| hak.json      | 'hak' => '客家語/Hak-kâ-ngî', |
| lzh.json      | 'lzh' => '文言', |
| wuu.json      | 'wuu' => '吴语', |
| yue.json      | 'yue' => '粵語', |
| zh.json       | 'zh' => '中文', |
|               | 'zh-classical' => '文言', |
|               | 'zh-cn' => "中文（中国大陆）", |
| zh-hans.json  | 'zh-hans' => "中文（简体）", |
| zh-hant.json  | 'zh-hant' => "中文（繁體）", |
| zh-hk.json    | 'zh-hk' => "中文（香港）", |
|               | 'zh-min-nan' => 'Bân-lâm-gú', #閩南語 |
|               | 'zh-mo' => "中文（澳門）", |
|               | 'zh-my' => "中文（马来西亚）", |
| zh-sg.json    | 'zh-sg' => "中文（新加坡）", |
| zh-tw.json    | 'zh-tw' => "中文（台灣）", |
|               | 'zh-yue' => '粵語', |
| | |
| ja.json       | 'ja' => '日本語',  # Japanese |
| | |
| ko.json       | 'ko' => '한국어',  # Korean |
| ko-kp.json    | 'ko-kp' => '한국어 (조선)',  # Korean (DPRK) |
