<?php
/**
 * Mirai 主题配置模块
 * 
 * 包含：themeFields（自定义字段）、themeConfig（主题设置）
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 定义文章/页面自定义字段
 */
function themeFields($layout) {
    $payMode = new \Typecho\Widget\Helper\Form\Element\Radio(
        'pay_mode',
        [
            'none' => '不启用',
            'read' => '整篇付费阅读',
            'partial' => '部分内容付费阅读'
        ],
        'none',
        _t('付费模式'),
        _t('可为单篇文章设置付费模式。部分内容付费支持使用 [pay]...[/pay] 包裹付费段落。')
    );
    $payMode->setAttribute('class', 'mirai-radio-horizontal');
    $layout->addItem($payMode);

    $payPrice = new \Typecho\Widget\Helper\Form\Element\Text(
        'pay_price',
        null,
        '0',
        _t('付费价格'),
        _t('当前文章付费金额，0 表示不收费。')
    );
    $payPrice->input->setAttribute('type', 'number');
    $payPrice->input->setAttribute('step', '0.01');
    $payPrice->input->setAttribute('min', '0');
    $layout->addItem($payPrice);

    $payPurchaseMethod = new \Typecho\Widget\Helper\Form\Element\Radio(
        'pay_purchase_method',
        [
            'both' => '在线或余额购买',
            'online' => '仅在线购买',
            'balance' => '仅余额购买'
        ],
        'both',
        _t('购买方式'),
        _t('设置本篇文章允许的购买方式。仅余额购买会强制登录。')
    );
    $payPurchaseMethod->setAttribute('class', 'mirai-radio-horizontal');
    $layout->addItem($payPurchaseMethod);

    $payLoginRequired = new \Typecho\Widget\Helper\Form\Element\Radio(
        'pay_login_required',
        ['1' => '登录后购买', '0' => '允许游客购买'],
        '1',
        _t('购买限制'),
        _t('该文章是否强制登录后购买。')
    );
    $payLoginRequired->setAttribute('class', 'mirai-radio-horizontal');
    $layout->addItem($payLoginRequired);

    $payCommissionRate = new \Typecho\Widget\Helper\Form\Element\Text(
        'pay_commission_rate',
        null,
        '-1',
        _t('创作分成比例'),
        _t('0-100 之间；-1 表示沿用全局分成比例。')
    );
    $payCommissionRate->input->setAttribute('type', 'number');
    $payCommissionRate->input->setAttribute('step', '0.01');
    $payCommissionRate->input->setAttribute('min', '-1');
    $layout->addItem($payCommissionRate);

    $payVipDiscountMode = new \Typecho\Widget\Helper\Form\Element\Radio(
        'pay_vip_discount_mode',
        [
            'default' => '沿用全局设置',
            'custom' => '自定义会员价格',
            'free_for_vip' => '会员免费阅读',
            'no_discount' => '不参与会员折扣'
        ],
        'default',
        _t('会员折扣模式'),
        _t('选择"自定义会员价格"可单独设置各等级会员价格；"会员免费阅读"则所有会员免费；"不参与会员折扣"则会员无折扣。')
    );
    $payVipDiscountMode->setAttribute('class', 'mirai-radio-horizontal');
    $layout->addItem($payVipDiscountMode);

    $payVip1Price = new \Typecho\Widget\Helper\Form\Element\Text(
        'pay_vip_1_price',
        null,
        '',
        _t('一级会员价格'),
        _t('填写具体金额为该等级专属价格；填0表示免费；留空则回退到主题后台的全局折扣设置。')
    );
    $payVip1Price->input->setAttribute('type', 'number');
    $payVip1Price->input->setAttribute('step', '0.01');
    $payVip1Price->input->setAttribute('min', '0');
    $layout->addItem($payVip1Price);

    $payVip2Price = new \Typecho\Widget\Helper\Form\Element\Text(
        'pay_vip_2_price',
        null,
        '',
        _t('二级会员价格'),
        _t('填写具体金额为该等级专属价格；填0表示免费；留空则回退到主题后台的全局折扣设置。')
    );
    $payVip2Price->input->setAttribute('type', 'number');
    $payVip2Price->input->setAttribute('step', '0.01');
    $payVip2Price->input->setAttribute('min', '0');
    $layout->addItem($payVip2Price);

    $payVip3Price = new \Typecho\Widget\Helper\Form\Element\Text(
        'pay_vip_3_price',
        null,
        '',
        _t('三级会员价格'),
        _t('填写具体金额为该等级专属价格；填0表示免费；留空则回退到主题后台的全局折扣设置。')
    );
    $payVip3Price->input->setAttribute('type', 'number');
    $payVip3Price->input->setAttribute('step', '0.01');
    $payVip3Price->input->setAttribute('min', '0');
    $layout->addItem($payVip3Price);
}

/**
 * 主题配置模块
 */
function themeConfig($form) {
    if (!empty($_POST['mirai_backup_action'])) {
        Mirai_Backup::handleBackupAction();
        exit;
    }
    Mirai_adminHead();
    $options = Mirai_opt();
    $themeUrl = Mirai_getThemeUrl();

    require_once __DIR__ . '/config.php';
    
    $cls = function (string $tab): string {
        return 'typecho-option mirai-option mirai-tab-' . $tab;
    };

    $siteTitle = new \Typecho\Widget\Helper\Form\Element\Text(
        'siteTitle',
        null,
        null,
        _t('站点标题'),
        _t('站点的名称，显示在网页标题处，留空则使用系统设置')
    );
    $siteTitle->setAttribute('class', $cls('basic'));
    $form->addInput($siteTitle);

    $siteSubtitle = new \Typecho\Widget\Helper\Form\Element\Text(
        'siteSubtitle',
        null,
        null,
        _t('站点副标题'),
        _t('站点的副标题，显示在站点标题下方，留空则使用系统设置')
    );
    $siteSubtitle->setAttribute('class', $cls('basic'));
    $form->addInput($siteSubtitle);

    $siteKeywords = new \Typecho\Widget\Helper\Form\Element\Text(
        'siteKeywords',
        null,
        null,
        _t('站点关键词'),
        _t('站点的关键词，用于SEO，多个关键词用逗号分隔，留空则使用系统设置')
    );
    $siteKeywords->setAttribute('class', $cls('basic'));
    $form->addInput($siteKeywords);

    $siteDescription = new \Typecho\Widget\Helper\Form\Element\Text(
        'siteDescription',
        null,
        'Mirai未来主题是一款简约优雅、多功能的现代化内容管理主题',
        _t('站点描述'),
        _t('站点的描述，用于SEO，留空则使用系统设置')
    );
    $siteDescription->setAttribute('class', $cls('basic'));
    $form->addInput($siteDescription);

    $logoImage = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoImage',
        null,
        'usr/themes/Mirai/assets/images/logo.png',
        _t('浅色模式Logo 图片地址'),
        _t('Logo 图片的 URL 地址')
    );
    $logoImage->setAttribute('class', $cls('basic'));
    $form->addInput($logoImage);

    $darkLogoImage = new \Typecho\Widget\Helper\Form\Element\Text(
        'darkLogoImage',
        null,
        null,
        _t('深色模式Logo 图片地址'),
        _t('深色模式下 Logo 图片的 URL 地址')
    );
    $darkLogoImage->setAttribute('class', $cls('basic'));
    $form->addInput($darkLogoImage);

    $logoAlt = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoAlt',
        null,
        null,
        _t('Logo图片Alt替代文本'),
        _t('为图片Logo添加 Alt 属性，提升SEO优化，强烈推荐填写！')
    );
    $logoAlt->setAttribute('class', $cls('basic'));
    $form->addInput($logoAlt);

    $logoHeight = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoHeight',
        null,
        '40',
        _t('Logo图片高度'),
        _t('Logo图片的原始高度（像素），用于预留布局空间避免闪烁，宽度自动按比例计算。默认40。')
    );
    $logoHeight->setAttribute('class', $cls('basic'));
    $form->addInput($logoHeight->addRule('isInteger', _t('高度必须是整数')));

    $favicon = new \Typecho\Widget\Helper\Form\Element\Text(
        'favicon',
        null,
        'usr/themes/Mirai/assets/images/favicon.ico',
        _t('Favicon 地址'),
        _t('网站图标 URL')
    );
    $favicon->setAttribute('class', $cls('basic'));
    $form->addInput($favicon);

    $appleTouchIcon = new \Typecho\Widget\Helper\Form\Element\Text(
        'appleTouchIcon',
        null,
        null,
        _t('Apple Touch Icon'),
        _t('iOS 设备主屏幕图标 URL（建议 180x180 PNG），留空则使用 Favicon')
    );
    $appleTouchIcon->setAttribute('class', $cls('basic'));
    $form->addInput($appleTouchIcon);

    $lazyLoading = new \Typecho\Widget\Helper\Form\Element\Text('lazyLoading', null, 'usr/themes/Mirai/assets/images/lazy-loading.webp', _t('懒加载占位图'), _t('图片懒加载时的占位图 URL'));
    $lazyLoading->setAttribute('class', $cls('basic'));
    $form->addInput($lazyLoading);

    $logThumb = new \Typecho\Widget\Helper\Form\Element\Text('logThumb', null, 'usr/themes/Mirai/assets/images/thumb.svg', _t('网站缩略图'), _t('文章没有封面时显示的默认图片'));
    $logThumb->setAttribute('class', $cls('basic'));
    $form->addInput($logThumb);

    $seoDefaultImage = new \Typecho\Widget\Helper\Form\Element\Text(
        'seoDefaultImage',
        null,
        'usr/themes/Mirai/assets/images/og-image.webp',
        _t('SEO 默认图片'),
        _t('用于 Open Graph、Twitter Card、JSON-LD 等 SEO 标签的默认图片。留空则使用主题默认图片（assets/images/og-image.webp）。<br><strong>推荐尺寸：</strong>1200x630 像素（Open Graph 标准）<br><strong>适用场景：</strong>首页、分类页、标签页等非文章页面的社交媒体分享')
    );
    $seoDefaultImage->setAttribute('class', $cls('basic'));
    $form->addInput($seoDefaultImage);

    $enableUserCenter = new \Typecho\Widget\Helper\Form\Element\Select(
        'enableUserCenter',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('启用用户中心'),
        _t('关闭后用户中心将完全不可用，包括注册登录功能。')
    );
    $enableUserCenter->setAttribute('class', $cls('auth'));
    $enableUserCenter->input->setAttribute('data-toggle-targets', 'enableFrontendLogin,enableEmailVerify,verifyCodeInterval,loginModalImage,enableUserAgreement,enablePrivacyPolicy');
    $enableUserCenter->input->setAttribute('data-toggle-value', '1');
    $form->addInput($enableUserCenter);

    $enableFrontendLogin = new \Typecho\Widget\Helper\Form\Element\Select(
        'enableFrontendLogin',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('启用前台登录入口'),
        _t('关闭后前台不显示登录入口。注册功能请在 <a href="options-general.php">系统设置</a> 中配置"是否允许注册"。')
    );
    $enableFrontendLogin->setAttribute('class', $cls('auth'));
    $form->addInput($enableFrontendLogin);

    $enableEmailVerify = new \Typecho\Widget\Helper\Form\Element\Select(
        'enableEmailVerify',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('启用注册邮箱验证'),
        _t('是否在注册时强制验证邮箱。启用后，用户注册时需要输入邮箱验证码。<br><span style="color:#e74c3c"><strong>注意：</strong>请确保您已正确配置 SMTP 邮箱设置，否则用户将无法收到验证码。</span>')
    );
    $enableEmailVerify->setAttribute('class', $cls('auth'));
    $enableEmailVerify->input->setAttribute('data-toggle-targets', 'verifyCodeInterval');
    $enableEmailVerify->input->setAttribute('data-toggle-value', '1');
    $form->addInput($enableEmailVerify);

    $verifyCodeInterval = new \Typecho\Widget\Helper\Form\Element\Text(
        'verifyCodeInterval',
        null,
        '60',
        _t('验证码发送间隔(秒)'),
        _t('两次发送验证码之间的最小间隔时间，单位秒，默认60秒')
    );
    $verifyCodeInterval->setAttribute('class', $cls('auth'));
    $verifyCodeInterval->input->setAttribute('type', 'number');
    $verifyCodeInterval->input->setAttribute('min', '10');
    $verifyCodeInterval->input->setAttribute('max', '3600');
    $form->addInput($verifyCodeInterval);

    $loginModalImage = new \Typecho\Widget\Helper\Form\Element\Text(
        'loginModalImage',
        null,
        'usr/themes/Mirai/assets/images/banner.webp',
        _t('登录弹窗左侧图片'),
        _t('输入图片URL，显示在登录弹窗左侧。建议尺寸：280x420像素，留空则不显示。')
    );
    $loginModalImage->setAttribute('class', $cls('auth'));
    $form->addInput($loginModalImage);

    $enableUserAgreement = new \Typecho\Widget\Helper\Form\Element\Select(
        'enableUserAgreement',
        ['1' => '显示', '0' => '隐藏'],
        '0',
        _t('显示用户协议'),
        _t('控制注册弹窗底部是否显示用户协议链接')
    );
    $enableUserAgreement->setAttribute('class', $cls('auth'));
    $enableUserAgreement->input->setAttribute('data-toggle-targets', 'userAgreementUrl,userAgreementName');
    $enableUserAgreement->input->setAttribute('data-toggle-value', '1');
    $form->addInput($enableUserAgreement);

    $userAgreementUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'userAgreementUrl',
        null,
        '',
        _t('用户协议链接'),
        _t('用户协议页面链接地址，留空则点击时仅显示提示信息')
    );
    $userAgreementUrl->setAttribute('class', $cls('auth'));
    $form->addInput($userAgreementUrl);

    $userAgreementName = new \Typecho\Widget\Helper\Form\Element\Text(
        'userAgreementName',
        null,
        '用户协议',
        _t('用户协议显示名称'),
        _t('注册弹窗中显示的用户协议文本，如：用户协议、服务条款等')
    );
    $userAgreementName->setAttribute('class', $cls('auth'));
    $form->addInput($userAgreementName);

    $enablePrivacyPolicy = new \Typecho\Widget\Helper\Form\Element\Select(
        'enablePrivacyPolicy',
        ['1' => '显示', '0' => '隐藏'],
        '0',
        _t('显示隐私政策'),
        _t('控制注册弹窗底部是否显示隐私政策链接')
    );
    $enablePrivacyPolicy->setAttribute('class', $cls('auth'));
    $enablePrivacyPolicy->input->setAttribute('data-toggle-targets', 'privacyPolicyUrl,privacyPolicyName');
    $enablePrivacyPolicy->input->setAttribute('data-toggle-value', '1');
    $form->addInput($enablePrivacyPolicy);

    $privacyPolicyUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'privacyPolicyUrl',
        null,
        '',
        _t('隐私政策链接'),
        _t('隐私政策页面链接地址，留空则点击时仅显示提示信息')
    );
    $privacyPolicyUrl->setAttribute('class', $cls('auth'));
    $form->addInput($privacyPolicyUrl);

    $privacyPolicyName = new \Typecho\Widget\Helper\Form\Element\Text(
        'privacyPolicyName',
        null,
        '隐私政策',
        _t('隐私政策显示名称'),
        _t('注册弹窗中显示的隐私政策文本，如：隐私政策、隐私保护等')
    );
    $privacyPolicyName->setAttribute('class', $cls('auth'));
    $form->addInput($privacyPolicyName);

    $payEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'payEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用付费功能'),
        _t('全局开关：开启后可使用付费阅读、余额充值与订单功能。')
    );
    $payEnable->setAttribute('class', $cls('pay_read'));
    $payEnable->input->setAttribute('data-toggle-targets', 'payGuestMode,payCommissionRate,payNotice');
    $payEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($payEnable);

    $payNotice = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'payNotice',
        null,
        '',
        _t('付费说明'),
        _t('显示在购买区域下方的说明文字，支持换行。')
    );
    $payNotice->setAttribute('class', $cls('pay_read'));
    $form->addInput($payNotice);

    $payGuestMode = new \Typecho\Widget\Helper\Form\Element\Select(
        'payGuestMode',
        ['login' => '仅登录用户购买', 'guest' => '允许游客购买'],
        'login',
        _t('购买身份限制'),
        _t('控制是否允许未登录用户购买付费内容。')
    );
    $payGuestMode->setAttribute('class', $cls('pay_read'));
    $form->addInput($payGuestMode);

    $payRechargeLimit = new \Typecho\Widget\Helper\Form\Element\Text(
        'payRechargeLimit',
        null,
        '0.01-10000',
        _t('充值金额限制'),
        _t('设置用户充值金额的范围，格式：最小值-最大值，如 0.01-10000。也可只填最大值，如 10000（此时最小值默认为 0.01）')
    );
    $payRechargeLimit->setAttribute('class', $cls('pay_recharge'));
    $form->addInput($payRechargeLimit);

    $payCommissionRate = new \Typecho\Widget\Helper\Form\Element\Text(
        'payCommissionRate',
        null,
        '0',
        _t('全局创作分成比例'),
        _t('0-100，买家支付后按比例发放给作者余额。')
    );
    $payCommissionRate->setAttribute('class', $cls('pay_recharge'));
    $payCommissionRate->input->setAttribute('type', 'number');
    $payCommissionRate->input->setAttribute('step', '0.01');
    $payCommissionRate->input->setAttribute('min', '0');
    $payCommissionRate->input->setAttribute('max', '100');
    $form->addInput($payCommissionRate);

    $withdrawMinAmount = new \Typecho\Widget\Helper\Form\Element\Text(
        'withdrawMinAmount',
        null,
        '10',
        _t('最低提现金额'),
        _t('作者申请提现的最低金额限制，默认10元。')
    );
    $withdrawMinAmount->setAttribute('class', $cls('pay_recharge'));
    $withdrawMinAmount->input->setAttribute('type', 'number');
    $withdrawMinAmount->input->setAttribute('step', '0.01');
    $withdrawMinAmount->input->setAttribute('min', '0');
    $form->addInput($withdrawMinAmount);

    $payWechatGateway = new \Typecho\Widget\Helper\Form\Element\Select(
        'payWechatGateway',
        ['none' => '关闭', 'epay' => '易支付'],
        'epay',
        _t('微信支付通道'),
        _t('控制前台是否展示"微信支付"，并指定对应收款网关。')
    );
    $payWechatGateway->setAttribute('class', $cls('pay_gateway'));
    $form->addInput($payWechatGateway);

    $payAlipayGateway = new \Typecho\Widget\Helper\Form\Element\Select(
        'payAlipayGateway',
        ['none' => '关闭', 'epay' => '易支付', 'f2fpay' => '支付宝当面付'],
        'epay',
        _t('支付宝支付通道'),
        _t('控制前台是否展示"支付宝支付"，并指定对应收款网关。')
    );
    $payAlipayGateway->setAttribute('class', $cls('pay_gateway'));
    $form->addInput($payAlipayGateway);

    $payQqGateway = new \Typecho\Widget\Helper\Form\Element\Select(
        'payQqGateway',
        ['none' => '关闭', 'epay' => '易支付'],
        'none',
        _t('QQ支付通道'),
        _t('控制前台是否展示"QQ支付"，并指定对应收款网关。')
    );
    $payQqGateway->setAttribute('class', $cls('pay_gateway'));
    $form->addInput($payQqGateway);

    $f2fEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'f2fEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用支付宝当面付'),
        _t('开启后可使用支付宝当面付收款')
    );
    $f2fEnable->setAttribute('class', $cls('pay_config'));
    $f2fEnable->input->setAttribute('data-toggle-targets', 'f2fAppId,f2fPrivateKey,f2fPublicKey');
    $f2fEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($f2fEnable);

    $f2fAppId = new \Typecho\Widget\Helper\Form\Element\Text('f2fAppId', null, '', _t('支付宝 AppID'), _t('支付宝当面付应用的 AppID。'));
    $f2fAppId->setAttribute('class', $cls('pay_config'));
    $form->addInput($f2fAppId);

    $f2fPrivateKey = new \Typecho\Widget\Helper\Form\Element\Textarea('f2fPrivateKey', null, '', _t('支付宝应用私钥'), _t('支持完整或无头尾 PEM 内容。'));
    $f2fPrivateKey->setAttribute('class', $cls('pay_config'));
    $form->addInput($f2fPrivateKey);

    $f2fPublicKey = new \Typecho\Widget\Helper\Form\Element\Textarea('f2fPublicKey', null, '', _t('支付宝公钥'), _t('用于支付宝通知验签。'));
    $f2fPublicKey->setAttribute('class', $cls('pay_config'));
    $form->addInput($f2fPublicKey);

    $epayEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'epayEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用易支付'),
        _t('开启后可使用易支付收款')
    );
    $epayEnable->setAttribute('class', $cls('pay_config'));
    $epayEnable->input->setAttribute('data-toggle-targets', 'epayVersion,epayApi,epayPid,epayKey,epayPlatformPublicKey,epayMerchantPrivateKey');
    $epayEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($epayEnable);

    $epayVersion = new \Typecho\Widget\Helper\Form\Element\Select(
        'epayVersion',
        ['2' => 'V2接口', '1' => 'V1接口'],
        '2',
        _t('易支付接口类型'),
        _t('V1接口支持易支付、码支付等大部分第四方支付系统')
    );
    $epayVersion->setAttribute('class', $cls('pay_config'));
    $form->addInput($epayVersion);

    $epayApi = new \Typecho\Widget\Helper\Form\Element\Text('epayApi', null, '', _t('易支付接口地址'), _t('例如：https://pay.example.com'));
    $epayApi->setAttribute('class', $cls('pay_config'));
    $form->addInput($epayApi);

    $epayPid = new \Typecho\Widget\Helper\Form\Element\Text('epayPid', null, '', _t('易支付商户ID'), _t('易支付平台分配的 pid。'));
    $epayPid->setAttribute('class', $cls('pay_config'));
    $form->addInput($epayPid);

    $epayKey = new \Typecho\Widget\Helper\Form\Element\Text('epayKey', null, '', _t('易支付商户密钥(MD5)'), _t('MD5签名方式使用，易支付平台分配的 md5 key。'));
    $epayKey->setAttribute('class', $cls('pay_config epay-v1'));
    $form->addInput($epayKey);

    $epayPlatformPublicKey = new \Typecho\Widget\Helper\Form\Element\Textarea('epayPlatformPublicKey', null, '', _t('易支付平台公钥(RSA)'), _t('RSA签名方式验签使用。支持完整或无头尾 PEM 内容。'));
    $epayPlatformPublicKey->setAttribute('class', $cls('pay_config epay-v2'));
    $form->addInput($epayPlatformPublicKey);

    $epayMerchantPrivateKey = new \Typecho\Widget\Helper\Form\Element\Textarea('epayMerchantPrivateKey', null, '', _t('易支付商户私钥(RSA)'), _t('RSA签名方式签名使用。支持完整或无头尾 PEM 内容。'));
    $epayMerchantPrivateKey->setAttribute('class', $cls('pay_config epay-v2'));
    $form->addInput($epayMerchantPrivateKey);

    $payOrderExpireTime = new \Typecho\Widget\Helper\Form\Element\Text('payOrderExpireTime', null, '1800', _t('订单过期时间(秒)'), _t('所有支付方式统一的订单过期时间，单位为秒，默认1800秒(30分钟)。过期后订单自动关闭，无法支付。范围：60-86400秒。'));
    $payOrderExpireTime->setAttribute('class', $cls('pay_config'));
    $form->addInput($payOrderExpireTime);

    $dnsOptimizationEnable = new \Typecho\Widget\Helper\Form\Element\Select('dnsOptimizationEnable', ['1' => '启用', '0' => '禁用'], '1', _t('DNS 优化加速'), _t('是否启用 DNS 预解析和预连接域名优化'));
    $dnsOptimizationEnable->setAttribute('class', $cls('speed'));
    $dnsOptimizationEnable->input->setAttribute('data-toggle-targets', 'dnsOptimization,preconnectOptimization');
    $dnsOptimizationEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($dnsOptimizationEnable);

    $dnsOptimization = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'dnsOptimization',
        null,
        'zz.bdstatic.com',
        _t('DNS 预解析'),
        _t('每行一个域名，不需要带 http/https，这些域名将自动添加 dns-prefetch')
    );
    $dnsOptimization->setAttribute('class', $cls('speed'));
    $form->addInput($dnsOptimization);

    $preconnectOptimization = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'preconnectOptimization',
        null,
        null,
        _t('预连接域名 (preconnect)'),
        _t('每行一个域名，不需要带 http/https，这些域名将自动添加 preconnect 和 dns-prefetch')
    );
    $preconnectOptimization->setAttribute('class', $cls('speed'));
    $form->addInput($preconnectOptimization);

    $openGraphEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'openGraphEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用 Open Graph'),
        _t('是否启用 Open Graph 元数据')
    );
    $openGraphEnable->setAttribute('class', $cls('seo'));
    $form->addInput($openGraphEnable);

    $structuredDataEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'structuredDataEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用结构化数据'),
        _t('是否启用 Schema.org 结构化数据')
    );
    $structuredDataEnable->setAttribute('class', $cls('seo'));
    $form->addInput($structuredDataEnable);

    $rssEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'rssEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用 RSS/Atom 订阅'),
        _t('是否启用 RSS 和 Atom 订阅功能。禁用后，RSS和Atom订阅链接将不可访问，页面头部也不会显示订阅链接。')
    );
    $rssEnable->setAttribute('class', $cls('seo'));
    $form->addInput($rssEnable);

    $navSource = new \Typecho\Widget\Helper\Form\Element\Select(
        'navSource',
        ['category' => '分类目录', 'page' => '独立页面', 'both' => '两者显示'],
        'category',
        _t('导航栏内容来源'),
        _t('选择导航栏主要显示的内容类型')
    );
    $navSource->setAttribute('class', $cls('nav'));
    $form->addInput($navSource);

    $maxNavItems = new \Typecho\Widget\Helper\Form\Element\Text(
        'maxNavItems',
        null,
        '8',
        _t('导航栏最大显示数量'),
        _t('限制导航栏最多显示的项目数量，避免导航栏过长。建议 6-10 个。')
    );
    $maxNavItems->setAttribute('class', $cls('nav'));
    $form->addInput($maxNavItems);

    $pageSize = new \Typecho\Widget\Helper\Form\Element\Text(
        'pageSize',
        null,
        '5',
        _t('每页显示文章数量'),
        _t('设置文章列表每页显示的文章数量，包括首页、分类页、标签页等。')
    );
    $pageSize->setAttribute('class', $cls('basic'));
    $form->addInput($pageSize);

    $tagDisplayMode = new \Typecho\Widget\Helper\Form\Element\Select(
        'tagDisplayMode',
        ['all' => '全部输出', 'custom' => '自定义数量'],
        'custom',
        _t('文章标签显示模式'),
        _t('选择文章卡片中标签的显示方式。')
    );
    $tagDisplayMode->setAttribute('class', $cls('basic'));
    $tagDisplayMode->input->setAttribute('data-toggle', 'tagDisplayNum');
    $tagDisplayMode->input->setAttribute('data-toggle-value', 'custom');
    $form->addInput($tagDisplayMode);

    $tagDisplayNum = new \Typecho\Widget\Helper\Form\Element\Text(
        'tagDisplayNum',
        null,
        '3',
        _t('标签显示数量'),
        _t('当选择"自定义数量"时，设置文章卡片中最多显示的标签数量。')
    );
    $tagDisplayNum->setAttribute('class', $cls('basic'));
    $form->addInput($tagDisplayNum);


    $recommendEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'recommendEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用精选推荐'),
        _t('是否在首页顶部显示精选推荐模块')
    );
    $recommendEnable->setAttribute('class', $cls('home'));
    $recommendEnable->input->setAttribute('data-toggle-targets', 'recommendContent,recommendTopEnable,recommendTopIds');
    $recommendEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($recommendEnable);

    $recommendContent = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'recommendContent',
        null,
        null,
        _t('精选推荐内容'),
        _t('<strong>文章ID（纯数字）：</strong>每行一个，最多7个。顺序：左侧大图→右上→右下→底部1-4<br><strong>图片配置：</strong><code>位置|图片地址|跳转链接|alt描述</code>，仅支持顶部三个位置（left/right1/right2），底部不支持图片；alt 描述可选，留空则使用默认值<br><strong>示例：</strong><code>left|https://img.jpg|https://link.com|精选推荐横幅</code><br><span style="color:#e74c3c">图片优先于文章ID，同一位置填写图片则不显示文章</span>')
    );
    $recommendContent->setAttribute('class', $cls('home'));
    $form->addInput($recommendContent);

    $recommendFirstPageOnly = new \Typecho\Widget\Helper\Form\Element\Select(
        'recommendFirstPageOnly',
        ['1' => '仅第一页显示', '0' => '所有分页显示'],
        '1',
        _t('精选推荐显示范围'),
        _t('仅第一页显示可减少分页重复内容，利于SEO；所有分页显示可增加推荐文章内链曝光')
    );
    $recommendFirstPageOnly->setAttribute('class', $cls('home'));
    $form->addInput($recommendFirstPageOnly);

    $recommendTopEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'recommendTopEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用精选推荐置顶'),
        _t('是否启用精选推荐置顶功能。启用后，置顶文章将优先填充精选推荐位，手动配置的文章ID将作为后备。<br><span style="color:#e74c3c"><strong>⚠ 重要提醒：</strong>请勿在"精选推荐置顶文章ID"与上方文章ID中输入相同的文章ID，否则会导致文章重复显示！</span>')
    );
    $recommendTopEnable->setAttribute('class', $cls('home'));
    $recommendTopEnable->input->setAttribute('data-toggle', 'recommendTopIds');
    $recommendTopEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($recommendTopEnable);

    $recommendTopIds = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'recommendTopIds',
        null,
        null,
        _t('精选推荐置顶文章ID'),
        _t('请输入置顶文章的CID，每行1个，最多7个。这些文章将优先显示在首页精选推荐区域展示。<br><span style="color:#e74c3c"><strong>注意：</strong>请确保与上方文章ID不要重复填写。</span>')
    );
    $recommendTopIds->setAttribute('class', $cls('home'));
    $form->addInput($recommendTopIds);

    $listTopEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'listTopEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用文章列表置顶'),
        _t('是否启用文章列表置顶功能。启用后，置顶文章将显示在首页文章列表的顶部。')
    );
    $listTopEnable->setAttribute('class', $cls('home'));
    $listTopEnable->input->setAttribute('data-toggle', 'listTopIds');
    $listTopEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($listTopEnable);

    $listTopIds = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'listTopIds',
        null,
        null,
        _t('文章列表置顶文章ID'),
        _t('请输入置顶文章的CID，每行一个。这些文章将显示在首页文章列表的顶部，按输入顺序排列。<br><span style="color:#e74c3c"><strong>注意：</strong>为避免文章重复显示，请勿与首页精选推荐区域和精选推荐置顶的ID重复。</span>')
    );
    $listTopIds->setAttribute('class', $cls('home'));
    $form->addInput($listTopIds);

    $footerCategoryEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'footerCategoryEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用首页分类推荐'),
        _t('是否在首页底部显示分类推荐')
    );
    $footerCategoryEnable->setAttribute('class', $cls('home'));
    $footerCategoryEnable->input->setAttribute('data-toggle-targets', 'footerCategoryIds,categoryCovers,categoryDescs,footerCategoryNum,footerCategorySort,footerCategorySpecificPosts');
    $footerCategoryEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($footerCategoryEnable);

    $footerCategoryIds = new \Typecho\Widget\Helper\Form\Element\Text('footerCategoryIds', null, null, _t('推荐分类ID'), _t('填入分类MID，用逗号分隔'));
    $footerCategoryIds->setAttribute('class', $cls('home'));
    $form->addInput($footerCategoryIds);

    $categoryCovers = new \Typecho\Widget\Helper\Form\Element\Textarea('categoryCovers', null, null, _t('分类自定义封面'), _t('每行一个，格式：分类ID|图片地址。若未设置，将随机显示该分类下的一篇文章封面'));
    $categoryCovers->setAttribute('class', $cls('home'));
    $form->addInput($categoryCovers);

    $categoryDescs = new \Typecho\Widget\Helper\Form\Element\Textarea('categoryDescs', null, null, _t('分类自定义描述'), _t('每行一个，格式：分类ID|自定义描述。若未设置，将显示分类自带的描述'));
    $categoryDescs->setAttribute('class', $cls('home'));
    $form->addInput($categoryDescs);

    $footerCategoryNum = new \Typecho\Widget\Helper\Form\Element\Text('footerCategoryNum', null, '5', _t('每个分类显示文章数'), _t('设置每个分类推荐区域显示的文章数量，默认为5篇'));
    $footerCategoryNum->setAttribute('class', $cls('home'));
    $form->addInput($footerCategoryNum);

    $footerCategorySort = new \Typecho\Widget\Helper\Form\Element\Select(
        'footerCategorySort',
        ['created' => '最新发布', 'views' => '最多阅读', 'random' => '随机排序'],
        'created',
        _t('文章排序方式'),
        _t('选择分类推荐区域文章的排序方式')
    );
    $footerCategorySort->setAttribute('class', $cls('home'));
    $form->addInput($footerCategorySort);

    $footerCategorySpecificPosts = new \Typecho\Widget\Helper\Form\Element\Textarea('footerCategorySpecificPosts', null, null, _t('指定特定文章'), _t('每行一个，格式：分类ID|文章CID1,文章CID2,文章CID3。若设置，将优先显示指定的文章（按填写顺序），不足数量的再按排序方式补充'));
    $footerCategorySpecificPosts->setAttribute('class', $cls('home'));
    $form->addInput($footerCategorySpecificPosts);

    $themeMode = new \Typecho\Widget\Helper\Form\Element\Select(
        'themeMode',
        ['auto' => '跟随系统', 'light' => '浅色', 'dark' => '深色'],
        'auto',
        _t('主题模式'),
        _t('选择默认主题模式（用户手动切换后以本地存储为准）')
    );
    $themeMode->setAttribute('class', $cls('theme'));
    $form->addInput($themeMode);

    $themeColor = new \Typecho\Widget\Helper\Form\Element\Text(
        'themeColor',
        null,
        '#007fff',
        _t('主题颜色'),
        _t('支持HEX格式，默认 #007fff')
    );
    $themeColor->setAttribute('class', $cls('theme') . ' mirai-color-picker');
    $form->addInput($themeColor);

    $fontColor = new \Typecho\Widget\Helper\Form\Element\Text(
        'fontColor',
        null,
        '#34495e',
        _t('字体颜色'),
        _t('支持HEX格式，默认 #34495e')
    );
    $fontColor->setAttribute('class', $cls('theme') . ' mirai-color-picker');
    $form->addInput($fontColor);

    $borderRadius = new \Typecho\Widget\Helper\Form\Element\Text('borderRadius', null, '0.588rem', _t('圆角大小'), _t('设置主题全局圆角大小，影响卡片、按钮、输入框等组件，默认 0.588rem'));
    $borderRadius->setAttribute('class', $cls('theme'));
    $form->addInput($borderRadius);

    $navRadiusMultiplier = new \Typecho\Widget\Helper\Form\Element\Text(
        'navRadiusMultiplier',
        null,
        '2',
        _t('导航栏圆角倍数'),
        _t('导航栏的圆角大小 = 全局圆角大小 × 此倍数<br>影响：电脑端顶部导航、移动端顶部导航、移动端底部导航<br>支持小数，如 0.5、1.5、2.25 等，默认值为 2')
    );
    $navRadiusMultiplier->setAttribute('class', $cls('theme'));
    $form->addInput($navRadiusMultiplier);

    $asideWidth = new \Typecho\Widget\Helper\Form\Element\Text('asideWidth', null, '300px', _t('边栏宽度'), _t('设置桌面端边栏宽度，仅影响PC端显示，默认 300px'));
    $asideWidth->setAttribute('class', $cls('theme'));
    $form->addInput($asideWidth);

    $mainMaxWidth = new \Typecho\Widget\Helper\Form\Element\Text('mainMaxWidth', null, '1288px', _t('页面最大宽度'), _t('设置页面内容区域最大宽度，影响整体布局宽度，默认 1288px'));
    $mainMaxWidth->setAttribute('class', $cls('theme'));
    $form->addInput($mainMaxWidth);

    $gridLayout = new \Typecho\Widget\Helper\Form\Element\Select(
        'gridLayout',
        ['4' => '4列网格', '3' => '3列网格', '1' => '单列列表'],
        '3',
        _t('文章布局选项'),
        _t('选择文章列表的显示布局：3列/4列网格布局，或单列列表（单列左图右文）。')
    );
    $gridLayout->setAttribute('class', $cls('theme'));
    $form->addInput($gridLayout);

    $asideEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'asideEnable',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('启用边栏'),
        _t('全局开关：禁用后桌面端边栏将不显示（移动端菜单不受影响，移动端边栏组件仍可通过下方"移动端边栏显示组件"单独控制）')
    );
    $asideEnable->setAttribute('class', $cls('aside'));
    $asideEnable->input->setAttribute('data-toggle-targets', 'asidePosition,asideModuleOrder,asideShowAdmin,adminBio,asideShowRecent,asideRecentPostsStyle,asideRecentPostsNumber,asideShowHotPosts,asideHotPostsStyle,asideHotPostsNumber,asideShowTags,asideTagsNumber,asideTagsSort,asideShowRecentComments');
    $asideEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($asideEnable);

    $asidePosition = new \Typecho\Widget\Helper\Form\Element\Select(
        'asidePosition',
        ['right' => '右侧', 'left' => '左侧'],
        'right',
        _t('边栏位置'),
        _t('选择边栏的显示位置')
    );
    $asidePosition->setAttribute('class', $cls('aside'));
    $form->addInput($asidePosition);

    $asideModuleOrder = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'asideModuleOrder',
        null,
        "admin\nhot\nrecent\ntags\ncomments",
        _t('边栏模块排序'),
        _t('自定义边栏模块显示顺序，每行一个模块标识，可选值：admin(管理员)、hot(热门文章)、recent(最新文章)、tags(标签云)、comments(最新评论)')
    );
    $asideModuleOrder->setAttribute('class', $cls('aside'));
    $form->addInput($asideModuleOrder);

    $asideShowAdmin = new \Typecho\Widget\Helper\Form\Element\Select(
        'asideShowAdmin',
        ['1' => '显示', '0' => '隐藏'],
        '1',
        _t('边栏显示管理员信息'),
        _t('控制边栏"管理员信息"模块')
    );
    $asideShowAdmin->setAttribute('class', $cls('aside'));
    $asideShowAdmin->input->setAttribute('data-toggle-targets', 'adminBgImage,adminBio');
    $asideShowAdmin->input->setAttribute('data-toggle-value', '1');
    $form->addInput($asideShowAdmin);

    $adminBgImage = new \Typecho\Widget\Helper\Form\Element\Text(
        'adminBgImage',
        null,
        'usr/themes/Mirai/assets/images/sidebar.webp',
        _t('管理员卡片背景图'),
        _t('边栏管理员信息卡片的背景图片URL，留空则不显示背景图')
    );
    $adminBgImage->setAttribute('class', $cls('aside'));
    $form->addInput($adminBgImage);

    $adminBio = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'adminBio',
        null,
        null,
        _t('管理员简介'),
        _t('边栏显示的管理员简介，留空则使用管理员个人资料中的简介')
    );
    $adminBio->setAttribute('class', $cls('aside'));
    $form->addInput($adminBio);

    $asideShowRecent = new \Typecho\Widget\Helper\Form\Element\Select('asideShowRecent', ['1' => '显示', '0' => '隐藏'], '1', _t('边栏显示最新文章'), _t('控制边栏"最新文章"模块'));
    $asideShowRecent->setAttribute('class', $cls('aside'));
    $asideShowRecent->input->setAttribute('data-toggle-targets', 'asideRecentPostsStyle,asideRecentPostsNumber');
    $asideShowRecent->input->setAttribute('data-toggle-value', '1');
    $form->addInput($asideShowRecent);

    $asideRecentPostsStyle = new \Typecho\Widget\Helper\Form\Element\Select(
        'asideRecentPostsStyle',
        ['cover' => '封面图模式', 'text' => '文本模式'],
        'cover',
        _t('最新文章显示模式'),
        _t('选择边栏最新文章的显示样式：封面图模式显示文章封面图+标题叠加；纯文字模式只显示标题列表')
    );
    $asideRecentPostsStyle->setAttribute('class', $cls('aside'));
    $form->addInput($asideRecentPostsStyle);

    $asideRecentPostsNumber = new \Typecho\Widget\Helper\Form\Element\Text('asideRecentPostsNumber', null, '5', _t('最新文章数量'), _t('设置边栏最新文章组件显示的文章数量'));
    $asideRecentPostsNumber->setAttribute('class', $cls('aside'));
    $form->addInput($asideRecentPostsNumber);

    $asideShowHotPosts = new \Typecho\Widget\Helper\Form\Element\Select(
        'asideShowHotPosts',
        ['1' => '显示', '0' => '隐藏'],
        '1',
        _t('边栏显示热门文章'),
        _t('控制边栏"热门文章"模块')
    );
    $asideShowHotPosts->setAttribute('class', $cls('aside'));
    $asideShowHotPosts->input->setAttribute('data-toggle-targets', 'asideHotPostsStyle,asideHotPostsNumber');
    $asideShowHotPosts->input->setAttribute('data-toggle-value', '1');
    $form->addInput($asideShowHotPosts);

    $asideHotPostsStyle = new \Typecho\Widget\Helper\Form\Element\Select(
        'asideHotPostsStyle',
        ['cover' => '封面图模式', 'text' => '文本模式'],
        'cover',
        _t('热门文章显示模式'),
        _t('选择边栏热门文章的显示样式：封面图模式显示文章封面图+标题叠加；纯文字模式只显示标题列表')
    );
    $asideHotPostsStyle->setAttribute('class', $cls('aside'));
    $form->addInput($asideHotPostsStyle);

    $asideHotPostsNumber = new \Typecho\Widget\Helper\Form\Element\Text('asideHotPostsNumber', null, '5', _t('热门文章数量'), _t('设置边栏热门文章组件显示的文章数量'));
    $asideHotPostsNumber->setAttribute('class', $cls('aside'));
    $form->addInput($asideHotPostsNumber);

    $asideShowTags = new \Typecho\Widget\Helper\Form\Element\Select('asideShowTags', ['1' => '显示', '0' => '隐藏'], '1', _t('边栏显示标签云'), _t('控制边栏"标签云"模块'));
    $asideShowTags->setAttribute('class', $cls('aside'));
    $asideShowTags->input->setAttribute('data-toggle-targets', 'asideTagsNumber,asideTagsSort');
    $asideShowTags->input->setAttribute('data-toggle-value', '1');
    $form->addInput($asideShowTags);

    $asideTagsNumber = new \Typecho\Widget\Helper\Form\Element\Text('asideTagsNumber', null, '16', _t('标签云数量'), _t('边栏显示的标签数量'));
    $asideTagsNumber->setAttribute('class', $cls('aside'));
    $form->addInput($asideTagsNumber);

    $asideTagsSort = new \Typecho\Widget\Helper\Form\Element\Select('asideTagsSort', ['count' => '按热度', 'mid' => '按时间', 'rand' => '随机'], 'count', _t('标签排序方式'), _t('设置边栏标签云的排序方式'));
    $asideTagsSort->setAttribute('class', $cls('aside'));
    $form->addInput($asideTagsSort);

    $asideShowRecentComments = new \Typecho\Widget\Helper\Form\Element\Select('asideShowRecentComments', ['1' => '显示', '0' => '隐藏'], '1', _t('边栏显示最新评论'), _t('控制边栏"最新评论"模块，数量跟随系统设置（设置->评论->评论列表数目）'));
    $asideShowRecentComments->setAttribute('class', $cls('aside'));
    $form->addInput($asideShowRecentComments);

    $mobileSidebarShowWidgets = new \Typecho\Widget\Helper\Form\Element\Select('mobileSidebarShowWidgets', ['1' => '显示', '0' => '隐藏'], '0', _t('移动端边栏显示组件'), _t('是否在移动端边栏中显示边栏组件（管理员信息、热门文章、最新文章、标签云、最新评论）'));
    $mobileSidebarShowWidgets->setAttribute('class', $cls('aside'));
    $form->addInput($mobileSidebarShowWidgets);

    $displaySummary = new \Typecho\Widget\Helper\Form\Element\Select('displaySummary', ['1' => '显示', '0' => '隐藏'], '1', _t('显示文章摘要'), _t('是否在文章页显示摘要'));
    $displaySummary->setAttribute('class', $cls('article'));
    $form->addInput($displaySummary);

    $codeHighlight = new \Typecho\Widget\Helper\Form\Element\Select(
        'codeHighlight',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('代码高亮'),
        _t('是否启用代码高亮功能（按需加载）。关闭后整个网站不加载代码高亮，开启后，主题将按需加载，用于提升网页加载速度。')
    );
    $codeHighlight->setAttribute('class', $cls('article_extend'));
    $form->addInput($codeHighlight);

    $displayReward = new \Typecho\Widget\Helper\Form\Element\Select('displayReward', ['1' => '启用', '0' => '禁用'], '0', _t('启用打赏'), _t('是否开启文章打赏功能'));
    $displayReward->setAttribute('class', $cls('article_extend'));
    $displayReward->input->setAttribute('data-toggle-targets', 'rewardWechat,rewardAlipay');
    $displayReward->input->setAttribute('data-toggle-value', '1');
    $form->addInput($displayReward);

    $rewardWechat = new \Typecho\Widget\Helper\Form\Element\Text('rewardWechat', null, null, _t('微信收款码'), _t('微信收款二维码 URL'));
    $rewardWechat->setAttribute('class', $cls('article_extend'));
    $form->addInput($rewardWechat);

    $rewardAlipay = new \Typecho\Widget\Helper\Form\Element\Text('rewardAlipay', null, null, _t('支付宝收款码'), _t('支付宝收款二维码 URL'));
    $rewardAlipay->setAttribute('class', $cls('article_extend'));
    $form->addInput($rewardAlipay);

    $displayCopyright = new \Typecho\Widget\Helper\Form\Element\Select('displayCopyright', ['1' => '显示', '0' => '隐藏'], '1', _t('显示版权声明'), _t('是否在文章末尾显示版权声明'));
    $displayCopyright->setAttribute('class', $cls('article_extend'));
    $displayCopyright->input->setAttribute('data-toggle', 'copyrightCustomContent');
    $displayCopyright->input->setAttribute('data-toggle-value', '1');
    $form->addInput($displayCopyright);

    $copyrightCustomContent = new \Typecho\Widget\Helper\Form\Element\Textarea('copyrightCustomContent', null, "本文作者：{{post_author}}\n本文链接：{{post_url}}\n转载需注明文章出处，若本文内容有侵犯到您合法权益，请联系站长删除", _t('自定义版权内容'), _t('支持变量：{{site_name}}, {{site_url}}, {{post_author}}, {{post_title}}, {{post_url}}'));
    $copyrightCustomContent->setAttribute('class', $cls('article_extend'));
    $form->addInput($copyrightCustomContent);

    $relatedEnable = new \Typecho\Widget\Helper\Form\Element\Select('relatedEnable', ['1' => '启用', '0' => '禁用'], '0', _t('启用推荐阅读'), _t('在文章底部显示推荐阅读区域，开启后将在文章底部显示相关推荐文章，有助于提升SEO和用户停留时间'));
    $relatedEnable->setAttribute('class', $cls('article_extend'));
    $relatedEnable->input->setAttribute('data-toggle-targets', 'relatedTitle,relatedMatchType,relatedSortType,relatedNum,relatedFillRandom');
    $relatedEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($relatedEnable);

    $relatedTitle = new \Typecho\Widget\Helper\Form\Element\Text('relatedTitle', null, '推荐阅读', _t('推荐阅读标题'), _t('推荐阅读区域的标题文字'));
    $relatedTitle->setAttribute('class', $cls('article_extend'));
    $form->addInput($relatedTitle);

    $relatedMatchType = new \Typecho\Widget\Helper\Form\Element\Select(
        'relatedMatchType',
        [
            'tag' => '标签匹配',
            'sort' => '分类匹配',
            'tag_sort' => '标签+分类混合'
        ],
        'tag',
        _t('推荐匹配方式'),
        _t('选择推荐文章的匹配规则：标签匹配更精准，分类匹配范围更广')
    );
    $relatedMatchType->setAttribute('class', $cls('article_extend'));
    $form->addInput($relatedMatchType);

    $relatedSortType = new \Typecho\Widget\Helper\Form\Element\Select(
        'relatedSortType',
        [
            'date' => '按发布时间',
            'views' => '按浏览量',
            'comnum' => '按评论数',
            'rand' => '随机排序'
        ],
        'date',
        _t('推荐排序方式'),
        _t('设置推荐文章的排序规则')
    );
    $relatedSortType->setAttribute('class', $cls('article_extend'));
    $form->addInput($relatedSortType);

    $relatedNum = new \Typecho\Widget\Helper\Form\Element\Text('relatedNum', null, '6', _t('推荐文章数量'), _t('设置推荐阅读区域显示的文章数量（建议3-9篇）'));
    $relatedNum->setAttribute('class', $cls('article_extend'));
    $form->addInput($relatedNum);

    $relatedFillRandom = new \Typecho\Widget\Helper\Form\Element\Select(
        'relatedFillRandom',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('不足时随机补充'),
        _t('开启后，当按规则匹配的文章数量不足时，会随机选取其他文章补充到设定数量')
    );
    $relatedFillRandom->setAttribute('class', $cls('article_extend'));
    $form->addInput($relatedFillRandom);

    $baiduPushApiEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'baiduPushApiEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用百度推送'),
        _t('是否启用百度主动推送功能')
    );
    $baiduPushApiEnable->setAttribute('class', $cls('seo'));
    $baiduPushApiEnable->input->setAttribute('data-toggle', 'baiduPushApi');
    $baiduPushApiEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($baiduPushApiEnable);

    $baiduPushApi = new \Typecho\Widget\Helper\Form\Element\Text(
        'baiduPushApi',
        null,
        null,
        _t('百度推送 API'),
        _t('百度搜索资源平台推送接口地址，格式如：http://data.zz.baidu.com/urls?site=your-site.com&token=your-token')
    );
    $baiduPushApi->setAttribute('class', $cls('seo'));
    $form->addInput($baiduPushApi);

    $baiduPushEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'baiduPushEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用百度JS自动推送'),
        _t('是否启用百度JS自动推送功能，将在页面加载时自动推送URL到百度。')
    );
    $baiduPushEnable->setAttribute('class', $cls('seo'));
    $form->addInput($baiduPushEnable);

    $indexNowEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'indexNowEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用 IndexNow 推送'),
        _t('是否启用 IndexNow 实时推送功能。启用后，发布或编辑文章时会自动将页面URL推送给 IndexNow API。<br>支持 Bing、Yandex、Seznam.cz 等多家搜索引擎。')
    );
    $indexNowEnable->setAttribute('class', $cls('seo'));
    $indexNowEnable->input->setAttribute('data-toggle-targets', 'indexNowKey,indexNowKeyLocation');
    $indexNowEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($indexNowEnable);

    $indexNowKey = new \Typecho\Widget\Helper\Form\Element\Text(
        'indexNowKey',
        null,
        null,
        _t('IndexNow Key'),
        _t('您的 IndexNow API Key。')
    );
    $indexNowKey->setAttribute('class', $cls('seo'));
    $form->addInput($indexNowKey);

    $indexNowKeyLocation = new \Typecho\Widget\Helper\Form\Element\Text(
        'indexNowKeyLocation',
        null,
        null,
        _t('IndexNow Key 文件地址'),
        _t('可选，如果您的 Key 文件与网站根目录下的默认名称(如: <code>{key}.txt</code>)不同，请在此处指定完整的 URL 地址。')
    );
    $indexNowKeyLocation->setAttribute('class', $cls('seo'));
    $form->addInput($indexNowKeyLocation);

    $sitemapEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'sitemapEnable',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('启用 SiteMap'),
        _t('控制是否启用 MiraiCore 插件提供的 SiteMap。仅开启时访问 /sitemap.xml 才由插件接管')
    );
    $sitemapEnable->setAttribute('class', $cls('seo'));
    $form->addInput($sitemapEnable);

    $nofollowExternalLinks = new \Typecho\Widget\Helper\Form\Element\Select(
        'nofollowExternalLinks',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('外链添加 nofollow'),
        _t('是否为外部链接自动添加 nofollow 属性')
    );
    $nofollowExternalLinks->setAttribute('class', $cls('seo'));
    $form->addInput($nofollowExternalLinks);

    $instantPageEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'instantPageEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用 instant.page 预加载'),
        _t('是否启用 instant.page 预加载技术，提升页面加载速度')
    );
    $instantPageEnable->setAttribute('class', $cls('speed'));
    $form->addInput($instantPageEnable);

    $footerDesc = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'footerDesc',
        null,
        null,
        _t('页脚站点描述'),
        _t('显示在页脚 Logo 下方的站点描述文字，留空则使用主题设置的站点描述')
    );
    $footerDesc->setAttribute('class', $cls('footer'));
    $form->addInput($footerDesc);

    $footerLeftQr = new \Typecho\Widget\Helper\Form\Element\Text(
        'footerLeftQr',
        null,
        '/usr/themes/Mirai/assets/images/wechat.webp',
        _t('页脚左侧二维码'),
        _t('上传左侧二维码图片；可用于微信、公众号等二维码；留空则不显示')
    );
    $footerLeftQr->setAttribute('class', $cls('footer'));
    $form->addInput($footerLeftQr);

    $footerLeftQrText = new \Typecho\Widget\Helper\Form\Element\Text(
        'footerLeftQrText',
        null,
        '作者微信',
        _t('左侧二维码文案'),
        _t('显示在页脚左侧二维码下方')
    );
    $footerLeftQrText->setAttribute('class', $cls('footer'));
    $form->addInput($footerLeftQrText);

    $footerRightQr = new \Typecho\Widget\Helper\Form\Element\Text(
        'footerRightQr',
        null,
        '/usr/themes/Mirai/assets/images/qq.webp',
        _t('页脚右侧二维码'),
        _t('上传右侧二维码图片；可用于QQ群、公众号等二维码；留空则不显示')
    );
    $footerRightQr->setAttribute('class', $cls('footer'));
    $form->addInput($footerRightQr);

    $footerRightQrText = new \Typecho\Widget\Helper\Form\Element\Text(
        'footerRightQrText',
        null,
        'QQ交流群',
        _t('右侧二维码文案'),
        _t('显示在页脚右侧二维码下方')
    );
    $footerRightQrText->setAttribute('class', $cls('footer'));
    $form->addInput($footerRightQrText);

    $footerCopyright = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'footerCopyright',
        null,
        '本站由 <a href="https://www.sukuy.com/article/mirai-theme" target="_blank" rel="noopener">Mirai未来主题</a> 驱动',
        _t('页脚信息'),
        _t('显示在页脚底部的信息，支持HTML')
    );
    $footerCopyright->setAttribute('class', $cls('footer'));
    $form->addInput($footerCopyright);

    $customHead = new \Typecho\Widget\Helper\Form\Element\Textarea('customHead', null, null, _t('自定义头部代码'), _t('输出在 &lt;head&gt; 标签末尾，可填写 CSS 或 JS 代码'));
    $customHead->setAttribute('class', $cls('other'));
    $form->addInput($customHead);

    $customFooterCode = new \Typecho\Widget\Helper\Form\Element\Textarea('customFooterCode', null, null, _t('自定义底部代码'), _t('输出在 &lt;body&gt; 标签末尾，可填写统计代码或 JS 代码'));
    $customFooterCode->setAttribute('class', $cls('other'));
    $form->addInput($customFooterCode);

    $uploadFileNaming = new \Typecho\Widget\Helper\Form\Element\Select(
        'uploadFileNaming',
        ['default' => 'Typecho 默认', 'original' => '原文件名'],
        'default',
        _t('文件命名方式'),
        _t('Typecho 默认：使用Typecho内置的随机文件名生成方式（如：1234567890.jpg）') . '<br>' .
        _t('原文件名：保留上传文件的原始文件名（会进行安全处理和去重）')
    );
    $uploadFileNaming->setAttribute('class', $cls('editor'));
    $form->addInput($uploadFileNaming);

    $miraiEditorType = new \Typecho\Widget\Helper\Form\Element\Select(
        'miraiEditorType',
        ['default' => 'Typecho 默认编辑器', 'editormd' => '主题内置编辑器'],
        'editormd',
        _t('后台编辑器'),
        _t('选择后台文章/页面编辑器类型。主题内置编辑器提供更强大的Markdown编辑功能')
    );
    $miraiEditorType->setAttribute('class', $cls('editor'));
    $miraiEditorType->input->setAttribute('data-toggle-targets', 'miraiEditorHeight,miraiEditorTheme,miraiEditorPreviewTheme,miraiEditorEditorTheme');
    $miraiEditorType->input->setAttribute('data-toggle-value', 'editormd');
    $form->addInput($miraiEditorType);

    $miraiEditorHeight = new \Typecho\Widget\Helper\Form\Element\Text(
        'miraiEditorHeight',
        null,
        '640',
        _t('编辑器高度'),
        _t('Editor.md 编辑器的高度（像素），默认 640')
    );
    $miraiEditorHeight->setAttribute('class', $cls('editor'));
    $form->addInput($miraiEditorHeight);

    $miraiEditorTheme = new \Typecho\Widget\Helper\Form\Element\Select(
        'miraiEditorTheme',
        ['default' => '默认', 'dark' => '暗黑'],
        'default',
        _t('编辑器主题'),
        _t('Editor.md 整体主题风格')
    );
    $miraiEditorTheme->setAttribute('class', $cls('editor'));
    $form->addInput($miraiEditorTheme);

    $miraiEditorPreviewTheme = new \Typecho\Widget\Helper\Form\Element\Select(
        'miraiEditorPreviewTheme',
        ['default' => '默认', 'dark' => '暗黑'],
        'default',
        _t('预览主题'),
        _t('Editor.md 预览区域主题')
    );
    $miraiEditorPreviewTheme->setAttribute('class', $cls('editor'));
    $form->addInput($miraiEditorPreviewTheme);

    $miraiEditorEditorTheme = new \Typecho\Widget\Helper\Form\Element\Select(
        'miraiEditorEditorTheme',
        [
            'default' => '默认',
            'pastel-on-dark' => 'Pastel on Dark',
            'ambiance' => 'Ambiance',
            'monokai' => 'Monokai',
            'rubyblue' => 'Ruby Blue',
            'the-matrix' => 'The Matrix',
            'twilight' => 'Twilight'
        ],
        'default',
        _t('代码编辑主题'),
        _t('Editor.md 代码编辑区域的配色方案')
    );
    $miraiEditorEditorTheme->setAttribute('class', $cls('editor'));
    $form->addInput($miraiEditorEditorTheme);

    $commentsGlobalEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'commentsGlobalEnable',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('启用全局评论'),
        _t('控制全站评论功能的开启或关闭。禁用后所有文章和页面都将禁止评论')
    );
    $commentsGlobalEnable->setAttribute('class', $cls('comment'));
    $form->addInput($commentsGlobalEnable);

    $commentsGuestAllowed = new \Typecho\Widget\Helper\Form\Element\Select(
        'commentsGuestAllowed',
        ['1' => '允许', '0' => '禁止'],
        '1',
        _t('允许游客评论'),
        _t('开启后未登录用户可直接评论；关闭后须登录后才能评论')
    );
    $commentsGuestAllowed->setAttribute('class', $cls('comment'));
    $form->addInput($commentsGuestAllowed);

    $ipLocationEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'ipLocationEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('IP 归属地显示'),
        _t('是否在评论区显示 IP 归属地信息。默认使用太平洋接口查询归属地，可在 IP 接口菜单中配置其他接口')
    );
    $ipLocationEnable->setAttribute('class', $cls('comment'));
    $form->addInput($ipLocationEnable);

    $ipLocationApi = new \Typecho\Widget\Helper\Form\Element\Select(
        'ipLocationApi',
        ['pconline' => '太平洋接口', 'custom' => '自定义接口'],
        'pconline',
        _t('归属地查询接口'),
        _t('选择 IP 归属地查询接口')
    );
    $ipLocationApi->setAttribute('class', $cls('ip'));
    $ipLocationApi->input->setAttribute('data-toggle', 'ipLocationCustomApi');
    $ipLocationApi->input->setAttribute('data-toggle-value', 'custom');
    $form->addInput($ipLocationApi);

    $ipLocationCustomApi = new \Typecho\Widget\Helper\Form\Element\Text(
        'ipLocationCustomApi',
        null,
        null,
        _t('自定义 API 接口地址'),
        _t('请输入自定义 API 地址，支持 GET 请求。使用 ip_address 作为 IP 占位符，例如：http://api.example.com/ip?addr=ip_address。接口应返回包含 country、province、city 字段的 JSON 数据')
    );
    $ipLocationCustomApi->setAttribute('class', $cls('ip'));
    $form->addInput($ipLocationCustomApi);

    $ipLocationPolling = new \Typecho\Widget\Helper\Form\Element\Select(
        'ipLocationPolling',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('接口轮询备份'),
        _t('仅当选择"自定义接口"时生效：启用后，当自定义接口失效时会自动切换到太平洋接口作为备份')
    );
    $ipLocationPolling->setAttribute('class', $cls('ip'));
    $form->addInput($ipLocationPolling);

    $ipLocationFormat = new \Typecho\Widget\Helper\Form\Element\Select(
        'ipLocationFormat',
        [
            'country' => '仅国家',
            'province' => '仅省份',
            'city' => '仅城市',
            'province_city' => '省份+城市'
        ],
        'province_city',
        _t('归属地显示格式'),
        _t('选择 IP 归属地的显示格式')
    );
    $ipLocationFormat->setAttribute('class', $cls('ip'));
    $form->addInput($ipLocationFormat);

    $smtpHost = new \Typecho\Widget\Helper\Form\Element\Text('smtpHost', null, null, _t('SMTP 地址'), _t('SMTP 服务器地址，如 smtp.qq.com'));
    $smtpHost->setAttribute('class', $cls('smtp'));
    $form->addInput($smtpHost);

    $smtpPort = new \Typecho\Widget\Helper\Form\Element\Text('smtpPort', null, '465', _t('SMTP 端口'), _t('SMTP 服务器端口，一般为 465 或 587'));
    $smtpPort->setAttribute('class', $cls('smtp'));
    $form->addInput($smtpPort);

    $smtpUser = new \Typecho\Widget\Helper\Form\Element\Text('smtpUser', null, null, _t('SMTP 用户'), _t('SMTP 认证用户名，通常是邮箱地址'));
    $smtpUser->setAttribute('class', $cls('smtp'));
    $form->addInput($smtpUser);

    $smtpPass = new \Typecho\Widget\Helper\Form\Element\Password('smtpPass', null, null, _t('SMTP 密码'), _t('SMTP 认证密码或授权码'));
    $smtpPass->setAttribute('class', $cls('smtp'));
    $form->addInput($smtpPass);

    $smtpSecure = new \Typecho\Widget\Helper\Form\Element\Select('smtpSecure', ['ssl' => 'SSL', 'tls' => 'TLS', 'none' => '无'], 'ssl', _t('SMTP 加密'), _t('SMTP 加密方式'));
    $smtpSecure->setAttribute('class', $cls('smtp'));
    $form->addInput($smtpSecure);

    $smtpFromName = new \Typecho\Widget\Helper\Form\Element\Text('smtpFromName', null, null, _t('发件人名称'), _t('邮件中显示的发件人名称，留空则使用主题设置的站点标题'));
    $smtpFromName->setAttribute('class', $cls('smtp'));
    $form->addInput($smtpFromName);

    echo '<div class="typecho-option mirai-option mirai-tab-smtp">';
    echo '<label class="typecho-label">邮件发送测试</label>';
    echo '<div style="margin-bottom: 10px;">';
    echo '<input type="email" id="mirai-smtp-test-email" class="text" placeholder="输入接收测试邮件的邮箱" style="width: 250px; margin-right: 10px;">';
    echo '<button type="button" class="btn primary" id="mirai-smtp-test-btn">发送测试邮件</button>';
    echo '</div>';
    echo '<span id="mirai-smtp-test-result" style="display: block; margin-top: 5px;"></span>';
    echo '</div>';

    $commentNotifyBlogger = new \Typecho\Widget\Helper\Form\Element\Select(
        'commentNotifyBlogger',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('新评论通知博主'),
        _t('开启后，有新评论时将邮件通知博主')
    );
    $commentNotifyBlogger->setAttribute('class', $cls('notification'));
    $form->addInput($commentNotifyBlogger);

    $commentReplyNotify = new \Typecho\Widget\Helper\Form\Element\Select(
        'commentReplyNotify',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('评论回复通知'),
        _t('开启后，评论被回复时将邮件通知评论者')
    );
    $commentReplyNotify->setAttribute('class', $cls('notification'));
    $form->addInput($commentReplyNotify);

    $submissionNotify = new \Typecho\Widget\Helper\Form\Element\Select(
        'submissionNotify',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('投稿通知'),
        _t('开启后，有新投稿（非管理员发布文章）时将邮件通知管理员')
    );
    $submissionNotify->setAttribute('class', $cls('notification'));
    $form->addInput($submissionNotify);

    $vipExpireEmailNotify = new \Typecho\Widget\Helper\Form\Element\Select(
        'vipExpireEmailNotify',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('会员到期通知'),
        _t('开启后，会员即将到期时将通过邮件发送提醒通知。需配合"支付设置-VIP设置"中的"到期提醒天数"使用')
    );
    $vipExpireEmailNotify->setAttribute('class', $cls('notification'));
    $form->addInput($vipExpireEmailNotify);

    $enableAnimation = new \Typecho\Widget\Helper\Form\Element\Select('enableAnimation', ['1' => '启用', '0' => '禁用'], '0', _t('启用全局动画'), _t('是否启用全局动画效果，包括页面加载入场动画和卡片悬停交互动画'));
    $enableAnimation->setAttribute('class', $cls('theme'));
    $form->addInput($enableAnimation);

    $enableScrollbar = new \Typecho\Widget\Helper\Form\Element\Select(
        'enableScrollbar',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('显示滚动条'),
        _t('在页面中显示自定义滚动条，开启后将显示自定义样式的滚动条，关闭则隐藏滚动条')
    );
    $enableScrollbar->setAttribute('class', $cls('theme'));
    $form->addInput($enableScrollbar);

    $mobileBottomTabEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'mobileBottomTabEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用移动端底部导航'),
        _t('是否在移动端显示底部导航菜单')
    );
    $mobileBottomTabEnable->setAttribute('class', $cls('nav'));
    $mobileBottomTabEnable->input->setAttribute('data-toggle-targets', 'mobileBottomTabItems');
    $mobileBottomTabEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($mobileBottomTabEnable);

    $mobileBottomTabItems = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'mobileBottomTabItems',
        null,
        "首页||/||ri-home-5-line\n投稿||#write||ri-send-plane-line\n搜索||#search||ri-search-line\n友链||/links||ri-link\n我的||#login||ri-user-3-line",
        _t('移动端底部菜单项'),
        _t('每行一个菜单项，格式：名称||链接||图标（可选）。<br><strong>图标支持两种方式</strong>：<br>1. RemixIcon类名：如 <code>ri-home-line</code>、<code>ri-star-fill</code><br>2. SVG代码：直接粘贴SVG标签，如 <code>&lt;svg viewBox="0 0 24 24"&gt;...&lt;/svg&gt;</code><br>例如：<code>分类||/category||ri-folder-line</code><br>留空则使用默认图标<br><br>链接支持特殊值：<br>• #search 表示打开搜索弹窗<br>• #login 表示打开登录弹窗（未登录用户）<br>• #write 表示投稿（未登录触发登录弹窗，已登录跳转投稿页）<br><br>默认配置包含5个按钮：首页、投稿、搜索、友链、我的')
    );
    $mobileBottomTabItems->setAttribute('class', $cls('nav'));
    $form->addInput($mobileBottomTabItems);

    echo '<div class="typecho-option mirai-option mirai-tab-backup">';
    echo '<label class="typecho-label">主题设置备份恢复</label>';
    ob_start();
    Mirai_Backup::render();
    ob_end_flush();
    echo '</div>';

    $licenseCodeValue = '';
    try {
        $options = Typecho_Widget::widget('Widget_Options');
        if ($options && isset($options->licenseCode)) {
            $licenseCodeValue = $options->licenseCode;
        }
    } catch (Exception $e) {
    }
    $licenseCode = new \Typecho\Widget\Helper\Form\Element\Hidden(
        'licenseCode',
        null,
        $licenseCodeValue
    );
    $form->addInput($licenseCode);

    $linksShowRecommend = new \Typecho\Widget\Helper\Form\Element\Select(
        'linksShowRecommend',
        ['1' => '显示', '0' => '隐藏'],
        '1',
        _t('显示推荐站点'),
        _t('是否在友情链接页面显示推荐站点模块')
    );
    $linksShowRecommend->setAttribute('class', $cls('friendship'));
    $linksShowRecommend->input->setAttribute('data-toggle-targets', 'linksRecommendTitle,popularSitesConfig');
    $linksShowRecommend->input->setAttribute('data-toggle-value', '1');
    $form->addInput($linksShowRecommend);

    $linksRecommendTitle = new \Typecho\Widget\Helper\Form\Element\Text(
        'linksRecommendTitle',
        null,
        '推荐站点',
        _t('推荐站点标题'),
        _t('友情链接页面推荐站点模块的标题文本')
    );
    $linksRecommendTitle->setAttribute('class', $cls('friendship'));
    $form->addInput($linksRecommendTitle);

    $popularSitesConfig = new \Typecho\Widget\Helper\Form\Element\Text(
        'popularSitesConfig',
        null,
        null,
        _t('推荐站点配置'),
        _t('输入已配置的友情链接ID，用英文逗号分隔。<br>示例：<code>1,5,3,8,2</code>，最多10个，按输入顺序排序')
    );
    $popularSitesConfig->setAttribute('class', $cls('friendship'));
    $form->addInput($popularSitesConfig);

    $linksShowRecommendPosts = new \Typecho\Widget\Helper\Form\Element\Select(
        'linksShowRecommendPosts',
        ['1' => '显示', '0' => '隐藏'],
        '1',
        _t('显示推荐文章'),
        _t('是否在友情链接页面显示推荐文章模块')
    );
    $linksShowRecommendPosts->setAttribute('class', $cls('friendship'));
    $linksShowRecommendPosts->input->setAttribute('data-toggle-targets', 'recommendPostsConfig,linksRecommendPostsTitle');
    $linksShowRecommendPosts->input->setAttribute('data-toggle-value', '1');
    $form->addInput($linksShowRecommendPosts);

    $recommendPostsConfig = new \Typecho\Widget\Helper\Form\Element\Text(
        'recommendPostsConfig',
        null,
        null,
        _t('推荐文章配置'),
        _t('输入已发布的文章ID，用英文逗号分隔。<br>示例：<code>1,5,3,8,2</code><br>最多显示5篇，按输入顺序排序')
    );
    $recommendPostsConfig->setAttribute('class', $cls('friendship'));
    $form->addInput($recommendPostsConfig);

    $linksRecommendPostsTitle = new \Typecho\Widget\Helper\Form\Element\Text(
        'linksRecommendPostsTitle',
        null,
        '推荐文章',
        _t('推荐文章标题'),
        _t('友情链接页面推荐文章模块的标题文本')
    );
    $linksRecommendPostsTitle->setAttribute('class', $cls('friendship'));
    $form->addInput($linksRecommendPostsTitle);

    $homeLinksEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'homeLinksEnable',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('启用首页友情链接'),
        _t('是否在首页分类推荐下方显示友情链接区域')
    );
    $homeLinksEnable->setAttribute('class', $cls('friendship'));
    $homeLinksEnable->input->setAttribute('data-toggle-targets', 'homeLinksIds,homeLinksMoreUrl,homeLinksMoreText');
    $homeLinksEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($homeLinksEnable);

    $homeLinksIds = new \Typecho\Widget\Helper\Form\Element\Text('homeLinksIds', null, null, _t('首页显示友情链接ID'), _t('填入要在首页显示的友情链接ID，用逗号分隔'));
    $homeLinksIds->setAttribute('class', $cls('friendship'));
    $form->addInput($homeLinksIds);

    $homeLinksMoreUrl = new \Typecho\Widget\Helper\Form\Element\Text('homeLinksMoreUrl', null, '/links', _t('更多链接地址'), _t('友情链接区域"更多"链接指向的URL，通常填写友情链接页面的地址'));
    $homeLinksMoreUrl->setAttribute('class', $cls('friendship'));
    $form->addInput($homeLinksMoreUrl);

    $homeLinksMoreText = new \Typecho\Widget\Helper\Form\Element\Text('homeLinksMoreText', null, '更多友链', _t('更多链接文字'), _t('"更多"链接显示的文字'));
    $homeLinksMoreText->setAttribute('class', $cls('friendship'));
    $form->addInput($homeLinksMoreText);

    $linksShowCategoryNav = new \Typecho\Widget\Helper\Form\Element\Select(
        'linksShowCategoryNav',
        ['1' => '显示', '0' => '隐藏'],
        '1',
        _t('显示分类导航'),
        _t('是否在左侧显示分类导航菜单')
    );
    $linksShowCategoryNav->setAttribute('class', $cls('friendship'));
    $form->addInput($linksShowCategoryNav);

    $linksNofollow = new \Typecho\Widget\Helper\Form\Element\Select(
        'linksNofollow',
        ['1' => '启用', '0' => '禁用'],
        '0',
        _t('链接添加 nofollow'),
        _t('是否为友情链接添加 nofollow 属性')
    );
    $linksNofollow->setAttribute('class', $cls('friendship'));
    $form->addInput($linksNofollow);

    $linksTargetBlank = new \Typecho\Widget\Helper\Form\Element\Select(
        'linksTargetBlank',
        ['1' => '新窗口', '0' => '当前窗口'],
        '1',
        _t('链接打开方式'),
        _t('友情链接的打开方式')
    );
    $linksTargetBlank->setAttribute('class', $cls('friendship'));
    $form->addInput($linksTargetBlank);

    $linksApplyTips = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'linksApplyTips',
        null,
        null,
        _t('申请友链提示'),
        _t('在申请友链弹窗中显示的提示信息，支持换行。留空则不显示提示区域。')
    );
    $linksApplyTips->setAttribute('class', $cls('friendship'));
    $linksApplyTips->input->setAttribute('style', 'width: 100%; height: 120px;');
    $form->addInput($linksApplyTips);

    $linksSubmitLimit = new \Typecho\Widget\Helper\Form\Element\Text(
        'linksSubmitLimit',
        null,
        '300',
        _t('申请提交限制(秒)'),
        _t('同一IP提交申请的间隔时间(秒)，默认300秒(5分钟)，0表示不限制')
    );
    $linksSubmitLimit->setAttribute('class', $cls('friendship'));
    $linksSubmitLimit->input->setAttribute('type', 'number');
    $linksSubmitLimit->input->setAttribute('min', '0');
    $form->addInput($linksSubmitLimit);

    $vipEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'vipEnable',
        ['1' => '开启', '0' => '关闭'],
        '1',
        _t('允许用户购买/续费会员'),
        _t('关闭后前端用户中心将不显示会员开通按钮。')
    );
    $vipEnable->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipEnable);

    $vipPurchaseMethod = new \Typecho\Widget\Helper\Form\Element\Select(
        'vipPurchaseMethod',
        ['both' => '余额和在线支付', 'online' => '仅在线支付', 'balance' => '仅余额支付'],
        'both',
        _t('会员购买支付方式'),
        _t('设置用户购买会员时允许使用的支付方式。')
    );
    $vipPurchaseMethod->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipPurchaseMethod);

    $vipName_1 = new \Typecho\Widget\Helper\Form\Element\Text('vipName_1', null, '一级会员', _t('一级会员名称'), _t('例如：白银会员'));
    $vipName_1->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipName_1);

    $vipDesc_1 = new \Typecho\Widget\Helper\Form\Element\Textarea('vipDesc_1', null, '基础会员特权，享专属折扣', _t('一级会员描述'), _t('会员特权说明，支持换行或HTML'));
    $vipDesc_1->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipDesc_1);

    $vipPrice_1_30 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_1_30', null, '10', _t('一级会员包月价格'), _t('0或留空表示不支持包月购买'));
    $vipPrice_1_30->setAttribute('class', $cls('pay_vip') . ' vip-price-1 vip-price-month');
    $form->addInput($vipPrice_1_30);

    $vipPrice_1_90 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_1_90', null, '25', _t('一级会员季度价格'), _t('0或留空表示不支持季度购买'));
    $vipPrice_1_90->setAttribute('class', $cls('pay_vip') . ' vip-price-1 vip-price-quarter');
    $form->addInput($vipPrice_1_90);

    $vipPrice_1_180 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_1_180', null, '50', _t('一级会员半年价格'), _t('0或留空表示不支持半年购买'));
    $vipPrice_1_180->setAttribute('class', $cls('pay_vip') . ' vip-price-1 vip-price-halfyear');
    $form->addInput($vipPrice_1_180);

    $vipPrice_1_365 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_1_365', null, '100', _t('一级会员包年价格'), _t('0或留空表示不支持包年购买'));
    $vipPrice_1_365->setAttribute('class', $cls('pay_vip') . ' vip-price-1 vip-price-year');
    $form->addInput($vipPrice_1_365);

    $vipPrice_1_0 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_1_0', null, '299', _t('一级会员永久价格'), _t('0或留空表示不支持永久购买'));
    $vipPrice_1_0->setAttribute('class', $cls('pay_vip') . ' vip-price-1 vip-price-perm');
    $form->addInput($vipPrice_1_0);

    $vipName_2 = new \Typecho\Widget\Helper\Form\Element\Text('vipName_2', null, '二级会员', _t('二级会员名称'), _t('例如：黄金会员'));
    $vipName_2->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipName_2);

    $vipDesc_2 = new \Typecho\Widget\Helper\Form\Element\Textarea('vipDesc_2', null, '高级会员特权，享更多折扣', _t('二级会员描述'), _t('会员特权说明，支持换行或HTML'));
    $vipDesc_2->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipDesc_2);

    $vipPrice_2_30 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_2_30', null, '20', _t('二级会员包月价格'), _t('0或留空表示不支持包月购买'));
    $vipPrice_2_30->setAttribute('class', $cls('pay_vip') . ' vip-price-2 vip-price-month');
    $form->addInput($vipPrice_2_30);

    $vipPrice_2_90 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_2_90', null, '50', _t('二级会员季度价格'), _t('0或留空表示不支持季度购买'));
    $vipPrice_2_90->setAttribute('class', $cls('pay_vip') . ' vip-price-2 vip-price-quarter');
    $form->addInput($vipPrice_2_90);

    $vipPrice_2_180 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_2_180', null, '100', _t('二级会员半年价格'), _t('0或留空表示不支持半年购买'));
    $vipPrice_2_180->setAttribute('class', $cls('pay_vip') . ' vip-price-2 vip-price-halfyear');
    $form->addInput($vipPrice_2_180);

    $vipPrice_2_365 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_2_365', null, '200', _t('二级会员包年价格'), _t('0或留空表示不支持包年购买'));
    $vipPrice_2_365->setAttribute('class', $cls('pay_vip') . ' vip-price-2 vip-price-year');
    $form->addInput($vipPrice_2_365);

    $vipPrice_2_0 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_2_0', null, '499', _t('二级会员永久价格'), _t('0或留空表示不支持永久购买'));
    $vipPrice_2_0->setAttribute('class', $cls('pay_vip') . ' vip-price-2 vip-price-perm');
    $form->addInput($vipPrice_2_0);

    $vipName_3 = new \Typecho\Widget\Helper\Form\Element\Text('vipName_3', null, '三级会员', _t('三级会员名称'), _t('例如：钻石会员（仅在等级数设置为3时生效）'));
    $vipName_3->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipName_3);

    $vipDesc_3 = new \Typecho\Widget\Helper\Form\Element\Textarea('vipDesc_3', null, '最高级会员特权，全站免费畅读', _t('三级会员描述'), _t('会员特权说明，支持换行或HTML'));
    $vipDesc_3->setAttribute('class', $cls('pay_vip'));
    $form->addInput($vipDesc_3);

    $vipPrice_3_30 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_3_30', null, '30', _t('三级会员包月价格'), _t('0或留空表示不支持包月购买'));
    $vipPrice_3_30->setAttribute('class', $cls('pay_vip') . ' vip-price-3 vip-price-month');
    $form->addInput($vipPrice_3_30);

    $vipPrice_3_90 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_3_90', null, '75', _t('三级会员季度价格'), _t('0或留空表示不支持季度购买'));
    $vipPrice_3_90->setAttribute('class', $cls('pay_vip') . ' vip-price-3 vip-price-quarter');
    $form->addInput($vipPrice_3_90);

    $vipPrice_3_180 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_3_180', null, '150', _t('三级会员半年价格'), _t('0或留空表示不支持半年购买'));
    $vipPrice_3_180->setAttribute('class', $cls('pay_vip') . ' vip-price-3 vip-price-halfyear');
    $form->addInput($vipPrice_3_180);

    $vipPrice_3_365 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_3_365', null, '300', _t('三级会员包年价格'), _t('0或留空表示不支持包年购买'));
    $vipPrice_3_365->setAttribute('class', $cls('pay_vip') . ' vip-price-3 vip-price-year');
    $form->addInput($vipPrice_3_365);

    $vipPrice_3_0 = new \Typecho\Widget\Helper\Form\Element\Text('vipPrice_3_0', null, '699', _t('三级会员永久价格'), _t('0或留空表示不支持永久购买'));
    $vipPrice_3_0->setAttribute('class', $cls('pay_vip') . ' vip-price-3 vip-price-perm');
    $form->addInput($vipPrice_3_0);

    $vipExpireNotifyDays = new \Typecho\Widget\Helper\Form\Element\Text(
        'vipExpireNotifyDays',
        null,
        '7',
        _t('到期提醒天数'),
        _t('会员到期前多少天开始显示到期提醒，0表示不提醒')
    );
    $vipExpireNotifyDays->setAttribute('class', $cls('pay_vip'));
    $vipExpireNotifyDays->input->setAttribute('type', 'number');
    $vipExpireNotifyDays->input->setAttribute('min', '0');
    $vipExpireNotifyDays->input->setAttribute('max', '30');
    $form->addInput($vipExpireNotifyDays);

    $vipDiscountMode = new \Typecho\Widget\Helper\Form\Element\Radio(
        'vipDiscountMode',
        ['percent' => '按比例折扣', 'fixed' => '固定价格折扣'],
        'percent',
        _t('会员折扣方式'),
        _t('【全局默认】当文章选择"沿用全局设置"或自定义模式下对应等级留空时生效。按比例折扣：根据折扣比例计算会员价格；固定价格折扣：会员价格=原价-固定金额')
    );
    $vipDiscountMode->setAttribute('class', $cls('pay_discount'));
    $form->addInput($vipDiscountMode);

    $vipDiscount_1 = new \Typecho\Widget\Helper\Form\Element\Text(
        'vipDiscount_1',
        null,
        '50',
        _t('一级会员折扣力度'),
        _t('【全局默认】一级会员的折扣力度，当文章未单独设置一级会员价格时生效。按比例折扣时填写百分比（如50表示5折）；固定价格折扣时填写减免金额（如10表示减10元）')
    );
    $vipDiscount_1->setAttribute('class', $cls('pay_discount'));
    $vipDiscount_1->input->setAttribute('type', 'number');
    $vipDiscount_1->input->setAttribute('step', '0.01');
    $vipDiscount_1->input->setAttribute('min', '0');
    $form->addInput($vipDiscount_1);

    $vipDiscount_2 = new \Typecho\Widget\Helper\Form\Element\Text(
        'vipDiscount_2',
        null,
        '50',
        _t('二级会员折扣力度'),
        _t('【全局默认】二级会员的折扣力度，当文章未单独设置二级会员价格时生效。按比例折扣时填写百分比（如50表示5折）；固定价格折扣时填写减免金额（如10表示减10元）')
    );
    $vipDiscount_2->setAttribute('class', $cls('pay_discount'));
    $vipDiscount_2->input->setAttribute('type', 'number');
    $vipDiscount_2->input->setAttribute('step', '0.01');
    $vipDiscount_2->input->setAttribute('min', '0');
    $form->addInput($vipDiscount_2);

    $vipDiscount_3 = new \Typecho\Widget\Helper\Form\Element\Radio(
        'vipDiscount_3',
        ['free' => '免费阅读', 'discount' => '享受折扣'],
        'free',
        _t('三级会员权益'),
        _t('选择"免费阅读"则三级会员全站免费；选择"享受折扣"则按折扣力度计算价格')
    );
    $vipDiscount_3->setAttribute('class', $cls('pay_discount'));
    $form->addInput($vipDiscount_3);

    $vipDiscount_3_value = new \Typecho\Widget\Helper\Form\Element\Text(
        'vipDiscount_3_value',
        null,
        '0',
        _t('三级会员折扣力度'),
        _t('仅当三级会员权益选择"享受折扣"时生效。百分比模式填0等同于免费，固定减免模式填0表示不减价')
    );
    $vipDiscount_3_value->setAttribute('class', $cls('pay_discount'));
    $vipDiscount_3_value->input->setAttribute('type', 'number');
    $vipDiscount_3_value->input->setAttribute('step', '0.01');
    $vipDiscount_3_value->input->setAttribute('min', '0');
    $form->addInput($vipDiscount_3_value);

    $vipRenewDiscount = new \Typecho\Widget\Helper\Form\Element\Text(
        'vipRenewDiscount',
        null,
        '0.9',
        _t('续费折扣率'),
        _t('现有会员续费时的折扣率，如0.9表示9折，0.85表示85折，1表示无折扣')
    );
    $vipRenewDiscount->setAttribute('class', $cls('pay_discount'));
    $vipRenewDiscount->input->setAttribute('type', 'number');
    $vipRenewDiscount->input->setAttribute('step', '0.01');
    $vipRenewDiscount->input->setAttribute('min', '0.1');
    $vipRenewDiscount->input->setAttribute('max', '1');
    $form->addInput($vipRenewDiscount);

    $icpNum = new \Typecho\Widget\Helper\Form\Element\Text(
        'icpNum',
        null,
        null,
        _t('ICP备案号'),
        _t('填写工信部备案号，如：京ICP备12345678号-1，留空则不显示')
    );
    $icpNum->setAttribute('class', $cls('basic'));
    $form->addInput($icpNum);

    $searchPopularEnable = new \Typecho\Widget\Helper\Form\Element\Select(
        'searchPopularEnable',
        ['1' => '启用', '0' => '禁用'],
        '1',
        _t('启用热门关键词'),
        _t('开启后在搜索面板中显示热门关键词推荐。需要在下方配置热门词内容才生效。')
    );
    $searchPopularEnable->setAttribute('class', $cls('search'));
    $searchPopularEnable->input->setAttribute('data-toggle-targets', 'searchPopularKeywords,searchPopularTitle');
    $searchPopularEnable->input->setAttribute('data-toggle-value', '1');
    $form->addInput($searchPopularEnable);

    $searchPopularTitle = new \Typecho\Widget\Helper\Form\Element\Text(
        'searchPopularTitle',
        null,
        '热门搜索',
        _t('热门关键词标题'),
        _t('搜索面板中热门关键词区域的标题文字')
    );
    $searchPopularTitle->setAttribute('class', $cls('search'));
    $form->addInput($searchPopularTitle);

    $searchPopularKeywords = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'searchPopularKeywords',
        null,
        "未来主题\ntypecho\nseo\n博客",
        _t('热门关键词'),
        _t('每行一个关键词，最多支持20行。用户点击后直接跳转到对应搜索结果页。<br>示例格式（每行一个词）：<code>Typecho</code><br><code>Mirai主题</code><br><code>教程</code>')
    );
    $searchPopularKeywords->setAttribute('class', $cls('search'));
    $form->addInput($searchPopularKeywords);

    $searchPlaceholder = new \Typecho\Widget\Helper\Form\Element\Text(
        'searchPlaceholder',
        null,
        '请输入关键字...',
        _t('搜索框占位文字'),
        _t('搜索输入框的 placeholder 提示文字')
    );
    $searchPlaceholder->setAttribute('class', $cls('search'));
    $form->addInput($searchPlaceholder);
}
