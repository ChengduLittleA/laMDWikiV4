# laMDWiki | 那么的维基

Share | 分享

**[- [ChengduLittleA.com/lamdwiki](https://ChengduLittleA.com/lamdwiki) -]**

laMDWiki is a super light weight blog/wiki platform running on PHP!

- Twitter-like posts and threads
- Back-references for both images and posts
- No database needed
- Convenient image upload
- Adapt to English/Chinese user interface
- Image Shop Functionality

那么的维基是一个运行在PHP环境中的超级轻量博客/维基平台。

- 推特形式的帖子和主题
- 帖子和图片均具备反引用功能
- 无数据库
- 方便上传和查看图片
- 适应英语和汉语浏览器界面
- 图片商城功能

(This is actually the forth version I made, it used to be so cramped with functions and not very easy to use)

（这实际上是我做的第四个版本，之前的太臃肿了不是很好用。）

## Installation | 安装

Copy `index.php`, `translations.md`, `Parsedown.php`, `ParsedownExtra.php` to your server document root and you are good to go.

When entered the site, double click the © symbol on the bottom to log in, default user ID is `admin`, default password is `Admin`. Please change the ID and the password after logging in.

It is recommended that you use Apache web server, because the access control is done through `.htaccess`. You may need to manually convert it for other web servers.

将 `index.php`, `translations.md`, `Parsedown.php`, `ParsedownExtra.php` 复制到服务器根目录即可。

进入网站后，双击底部©符号登录设置，默认用户 `admin`，默认密码`Admin`。登录后请修改密码。

推荐在Apache上运行，因为那么的维基使用`.htaccess`文件提供访问控制，你可以手动将该文件的内容转换为适用于其他服务程序的配置。

## Using | 使用

Just post stuff with markdown syntax. Click the left side arrow to access post context, Post manipulation button is the `+` symbol on the top right of each post.

使用markdown语法发帖即可。点击帖子左边的箭头能查看帖子上下文，帖子右上角的`+`号按钮可以调出帖子操作面板。

### Post Referencing | 引用帖子

Post can be referenced using Markdown link, simply put the 14 digit post ID into the link field. A reference link with its own paragraph will be automatically expanded into a post preview.

使用Markdown链接可以引用帖子，只需在链接中填写帖子的14位数字。自成一段的引用帖子会自动展开为帖子预览。

### Special Codes | 特殊代码

| 代码 | 显示 |
|------|------|
| `<-- <- <-> -> -->` | <-- <- <-> -> --> |
| `<== <= <=> => ==>` | <== <= <=> => ==> |
| `+++++` | （分页符） |
| `[-高亮文字-]` | [-高亮文字-] |
| `![keep_inline original]()` | 图片保持行内，使用原图 |
| `{big_table}` | 下一个表格是大表格 |
| `{read_more}` | 帖子预览在这里截断 |
| `{PRICE my product price}` | 产品价格 |
| `{SHORT product short description}` | 产品短描述 |
| `{PURCHASE button name}` | 购买按钮 |
| - [PRODUCT whatever](14_digit_id) | 商品列表项目 |
| `{支付宝}` | {支付宝} |
| `{PayPal}` | {PayPal} |

### Product Showcasing | 商品展示

Mark the post as `P` type to set it as a product. Then use those product specific tags shown above to set basic info for product preview. The thumbnail of the product is the first image used in the passage. To reference/show this product on another post, simply use a link, or put the link into a Markdown list.

将帖子标记为`P`类型即可将帖子作为商品。在文章中设置上述商品专用标签可以提供商品预览信息。商品预览缩略图采用文中第一个出现的图片。要引用该商品，可以直接使用帖子链接，或者将链接放在Markdown列表里。

## Settings | 设置

Navigation bar, two footers and a pinned post can be configured. Copy the 14-digit post id (from your url or from post menu) and paste it inside to use that post in corresponding positions. Use markdown list for navigation bar for best layout.

导航栏，两个脚注和置顶帖子可以手动设置。复制帖子的14位数字标识（从浏览器链接或者从帖子菜单）并粘贴到设置框里就能在对应位置调取帖子。在导航栏中使用markdown列表以获得好看的排版。

Purchasing URL can be set when you open a image. If you'd like to set it as a internal product post, then enter the 14 digit post ID.

点击图像之后可以设置对应图像的购买链接。若要设置为商品帖，则填入14位帖子编号。

