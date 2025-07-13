<?php
require_once 'database.php';

// 获取网站设置
$site_settings = getSiteSettings();
$site_name = $site_settings['site_name'] ?? '作品集网站';
$site_description = $site_settings['site_description'] ?? '展示个人作品的专业网站';
$icp_record = $site_settings['icp_record'] ?? '';

// 获取模块信息
$home_module = getModuleInfo('home');
$about_module = getModuleInfo('about');
$support_module = getModuleInfo('support');

// 获取分类和作品
$categories = getActiveCategories();
$works = getWorks(null, 12); // 获取最新12个作品

// 获取友情链接
$friend_links = getActiveFriendLinks();

// 获取当前分类
$current_category = isset($_GET['category']) ? $_GET['category'] : '';
if ($current_category) {
    $works = getWorks($current_category);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($site_name); ?></title>
    <meta name="description" content="<?php echo e($site_description); ?>">
    <meta name="keywords" content="<?php echo e($site_settings['keywords'] ?? ''); ?>">
    <link rel="stylesheet" href="layui/css/layui.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            color: #333;
            background-color: #ffffff;
            font-weight: 300;
        }
        
        /* 头部样式 - 极简设计 */
        .header {
            background: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
        }
        
        .logo {
            font-size: 20px;
            font-weight: 400;
            color: #333;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .nav-menu {
            display: flex;
            gap: 50px;
        }
        
        .nav-dropdown {
            position: relative;
        }
        
        .nav-dropdown-toggle {
            color: #333;
            text-decoration: none;
            font-weight: 300;
            font-size: 16px;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .nav-dropdown-toggle:hover {
            color: #666;
        }
        
        .nav-dropdown-toggle i {
            font-size: 12px;
            transition: transform 0.3s ease;
        }
        
        .nav-dropdown.active .nav-dropdown-toggle i {
            transform: rotate(180deg);
        }
        
        .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            min-width: 120px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 8px 0;
            margin-top: 10px;
        }
        
        .nav-dropdown.active .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .nav-dropdown-menu li {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-dropdown-menu a {
            display: block;
            padding: 10px 16px;
            color: #333;
            text-decoration: none;
            font-weight: 300;
            font-size: 14px;
            transition: all 0.3s ease;
            border-radius: 4px;
            margin: 0 8px;
        }
        
        .nav-dropdown-menu a:hover {
            background: #f8f8f8;
            color: #333;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }
        
        /* 主内容区域 */
        .main-content {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
        }
        
        /* 英雄区域 - 简化版 */
        .hero-section {
            padding: 120px 40px 80px;
            text-align: center;
            background: #ffffff;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-title {
            font-size: 36px;
            font-weight: 300;
            margin-bottom: 20px;
            color: #333;
            letter-spacing: 1px;
        }
        
        .hero-subtitle {
            font-size: 18px;
            margin-bottom: 40px;
            color: #666;
            font-weight: 300;
        }
        
        .hero-description {
            font-size: 16px;
            margin-bottom: 0;
            color: #999;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* 作品展示区域 - 网格布局 */
        .works-section {
            padding: 60px 40px;
            background: #ffffff;
        }
        
        .works-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 40px;
            color: #333;
        }
        
        .category-filter {
            margin-bottom: 60px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .category-filter a {
            color: #999;
            text-decoration: none;
            font-size: 14px;
            font-weight: 300;
            transition: color 0.3s ease;
            letter-spacing: 1px;
        }
        
        .category-filter a:hover,
        .category-filter a.active {
            color: #333;
        }
        
        .works-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
        }
        
        .work-item {
            background: #ffffff;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }
        
        .work-item:hover {
            border-color: #f0f0f0;
        }
        
        .work-image {
            width: 100%;
            height: 250px;
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }
        
        .work-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .work-image video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .work-media-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 400;
        }
        
        .work-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            color: white;
            font-size: 16px;
        }
        
        .work-item:hover .work-overlay {
            opacity: 1;
        }
        
        .work-content {
            padding: 20px 0;
        }
        
        .work-title {
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 8px;
            color: #333;
        }
        
        .work-tags {
            font-size: 14px;
            color: #999;
            letter-spacing: 0.5px;
            font-weight: 300;
        }
        
        .work-description {
            display: none; /* 隐藏描述，保持简洁 */
        }
        
        .work-meta {
            display: none; /* 隐藏meta信息 */
        }
        
        /* 关于我们区域 - 简化 */
        .about-section {
            padding: 100px 40px;
            background: #f8f8f8;
        }
        
        .about-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: start;
        }
        
        .about-text {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            font-weight: 300;
        }
        
        .about-text h2 {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 30px;
            color: #333;
        }
        
        .about-image {
            width: 100%;
            height: 400px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 14px;
        }
        
        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* 支持我们区域 */
        .support-section {
            padding: 100px 40px;
            background: #ffffff;
        }
        
        .support-content {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
        
        .support-title {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 40px;
            color: #333;
        }
        
        .support-text {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 40px;
            font-weight: 300;
        }

        /* 友情链接区域 */
        .links-section {
            padding: 60px 40px;
            background: #f8f8f8;
        }
        
        .links-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .links-title {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 40px;
            color: #333;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }
        
        .link-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .link-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .friend-link {
            display: block;
            padding: 20px;
            text-decoration: none;
            color: inherit;
        }
        
        .link-logo {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            margin: 0 auto 12px;
            display: block;
        }
        
        .link-logo-placeholder {
            width: 48px;
            height: 48px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: #ccc;
            font-size: 20px;
        }
        
        .link-info {
            text-align: center;
        }
        
        .link-name {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            display: block;
            margin-bottom: 4px;
        }
        
        .link-description {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
            display: block;
            opacity: 0.8;
        }
        
        .friend-link:hover .link-name {
            color: #1E9FFF;
        }
        
        .friend-link:hover .link-description {
            color: #1E9FFF;
            opacity: 1;
        }
        
        /* 底部区域 - 极简 */
        .footer {
            background: #f8f8f8;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #f0f0f0;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .footer-text {
            font-size: 14px;
            color: #999;
            font-weight: 300;
        }
        

        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 20px;
            }
            
            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border-top: 1px solid #f0f0f0;
                flex-direction: column;
                gap: 0;
                padding: 0;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }
            
            .nav-dropdown {
                position: static;
                width: 100%;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .nav-dropdown-toggle {
                padding: 15px 20px;
                width: 100%;
                justify-content: space-between;
                font-size: 16px;
                font-weight: 400;
            }
            
            .nav-dropdown-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                border: none;
                background: #f8f8f8;
                border-radius: 0;
                margin-top: 0;
                padding: 0;
                display: none;
            }
            
            .nav-dropdown.active .nav-dropdown-menu {
                display: block;
            }
            
            .nav-dropdown-menu a {
                padding: 12px 40px;
                margin: 0;
                font-size: 14px;
                color: #666;
                border-bottom: 1px solid #e8e8e8;
            }
            
            .nav-dropdown-menu a:hover {
                background: #f0f0f0;
                color: #333;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .hero-section {
                padding: 80px 20px 60px;
            }
            
            .hero-title {
                font-size: 28px;
            }
            
            .works-section {
                padding: 40px 20px;
            }
            
            .works-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .about-content {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 0 20px;
            }
            
            .support-section {
                padding: 60px 20px;
            }
            
            .links-section {
                padding: 40px 20px;
            }
            
            .links-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .friend-link {
                padding: 15px;
            }
            
            .link-logo, .link-logo-placeholder {
                width: 40px;
                height: 40px;
                margin-bottom: 8px;
            }
            
            .link-name {
                font-size: 13px;
            }
            
            .footer-content {
                padding: 0 20px;
            }
            
            .category-filter {
                flex-direction: column;
                gap: 10px;
            }
            
            /* 移动端视频优化 */
            .work-image video {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .work-media-indicator {
                font-size: 10px;
                padding: 3px 8px;
            }
        }
        
        /* 动画效果 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <header class="header">
        <div class="header-content">
            <a href="#" class="logo"><?php echo e($site_name); ?></a>
            <nav class="nav-menu">
                <div class="nav-dropdown">
                    <a href="javascript:;" class="nav-dropdown-toggle">
                        <span>导航</span>
                        <i class="layui-icon layui-icon-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="#works">作品</a></li>
                        <?php if ($about_module): ?>
                        <li><a href="#about">关于</a></li>
                        <?php endif; ?>
                        <?php if ($support_module): ?>
                        <li><a href="#support">支持</a></li>
                        <?php endif; ?>
                        <?php if (!empty($friend_links)): ?>
                        <li><a href="#links">友情链接</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="layui-icon layui-icon-more-vertical"></i>
            </button>
        </div>
    </header>

    <!-- 主内容 -->
    <main class="main-content">
        <!-- 英雄区域 -->
        <section id="home" class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title"><?php echo e($site_name); ?></h1>
                <p class="hero-subtitle"><?php echo e($site_description); ?></p>
                <?php if ($home_module): ?>
                    <p class="hero-description"><?php echo e($home_module['content']); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- 作品展示区域 -->
        <section id="works" class="works-section">
            <div class="works-container">
                <h2 class="section-title">精选作品</h2>
                
                <!-- 分类筛选 -->
                <div class="category-filter">
                    <a href="?category=" class="<?php echo empty($current_category) ? 'active' : ''; ?>">全部</a>
                    <?php foreach ($categories as $category): ?>
                        <a href="?category=<?php echo $category['id']; ?>" 
                           class="<?php echo $current_category == $category['id'] ? 'active' : ''; ?>">
                            <?php echo e($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- 作品网格 -->
                <div class="works-grid" id="worksGrid">
                    <?php if (empty($works)): ?>
                        <div style="text-align: center; padding: 80px 0; color: #ccc; grid-column: 1 / -1;">
                            <p>暂无作品</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($works as $work): ?>
                            <div class="work-item fade-in-up" data-work-id="<?php echo $work['id']; ?>">
                                <div class="work-image">
                                    <?php
                                    // 判断媒体类型
                                    $has_image = !empty($work['image']);
                                    $has_video = !empty($work['video']);
                                    
                                    if ($has_image && $has_video): ?>
                                        <!-- 同时有图片和视频，显示图片并添加指示器 -->
                                        <img src="uploads/<?php echo e($work['image']); ?>" alt="<?php echo e($work['title']); ?>">
                                        <div class="work-media-indicator">图片+视频</div>
                                        <div class="work-overlay">点击查看详情</div>
                                    <?php elseif ($has_video): ?>
                                        <!-- 只有视频 -->
                                        <video controls>
                                            <source src="uploads/<?php echo e($work['video']); ?>" type="video/mp4">
                                            您的浏览器不支持视频播放
                                        </video>
                                        <div class="work-media-indicator">视频</div>
                                    <?php elseif ($has_image): ?>
                                        <!-- 只有图片 -->
                                        <img src="uploads/<?php echo e($work['image']); ?>" alt="<?php echo e($work['title']); ?>">
                                        <div class="work-media-indicator">图片</div>
                                    <?php else: ?>
                                        <!-- 没有媒体文件 -->
                                        <span>暂无图片</span>
                                    <?php endif; ?>
                                </div>
                                <div class="work-content">
                                    <h3 class="work-title"><?php echo e($work['title']); ?></h3>
                                    <div class="work-tags"><?php echo e($work['category_name'] ?? '未分类'); ?></div>
                                    <?php if (!empty($work['description'])): ?>
                                        <div class="work-description" style="display: block; font-size: 14px; color: #999; margin-top: 8px; line-height: 1.4;">
                                            <?php echo e(mb_substr($work['description'], 0, 60, 'UTF-8')); ?>
                                            <?php if (mb_strlen($work['description'], 'UTF-8') > 60): ?>...<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 关于我们区域 -->
        <?php if ($about_module): ?>
        <section id="about" class="about-section">
            <div class="about-content">
                <div class="about-text">
                    <h2>关于我们</h2>
                    <?php echo nl2br(e($about_module['content'])); ?>
                </div>
                <?php if ($about_module['image']): ?>
                <div class="about-image">
                    <img src="uploads/<?php echo e($about_module['image']); ?>" alt="<?php echo e($about_module['title']); ?>">
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- 支持我们区域 -->
        <?php if ($support_module): ?>
        <section id="support" class="support-section">
            <div class="support-content">
                <h2 class="support-title">支持我们</h2>
                <p class="support-text">
                    <?php echo nl2br(e($support_module['content'])); ?>
                </p>
                
                <?php if ($support_module['image']): ?>
                    <div style="margin-top: 40px; text-align: center;">
                        <img src="uploads/<?php echo e($support_module['image']); ?>" alt="支持我们" style="max-width: 300px; border-radius: 8px;">
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- 友情链接区域 -->
        <?php if (!empty($friend_links)): ?>
        <section id="links" class="links-section">
            <div class="links-content">
                <h2 class="links-title">友情链接</h2>
                <div class="links-grid">
                    <?php foreach ($friend_links as $link): ?>
                        <div class="link-item">
                            <a href="<?php echo e($link['url'] ?? ''); ?>" 
                               target="<?php echo e($link['target'] ?? '_blank'); ?>"
                               class="friend-link"
                               data-url="<?php echo e($link['url'] ?? ''); ?>"
                               data-name="<?php echo e($link['name'] ?? '未命名链接'); ?>"
                               title="<?php echo e(($link['description'] ?? '') ?: ($link['name'] ?? '未命名链接')); ?>">
                                <?php if (!empty($link['logo'])): ?>
                                    <img src="uploads/<?php echo e($link['logo']); ?>" alt="<?php echo e($link['name'] ?? '友情链接'); ?>" class="link-logo">
                                <?php else: ?>
                                    <div class="link-logo-placeholder">
                                        <i class="layui-icon layui-icon-link"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="link-info">
                                    <span class="link-name"><?php echo e($link['name'] ?? '未命名链接'); ?></span>
                                    <?php if (!empty($link['description'])): ?>
                                        <span class="link-description"><?php echo e($link['description']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- 底部 -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-text">
                © <?php echo date('Y'); ?> <?php echo e($site_name); ?>
                <?php if ($icp_record): ?>
                    · <?php echo e($icp_record); ?>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script src="layui/layui.js"></script>
    <script>
        layui.use(['form'], function(){
            var form = layui.form;
            
            // 平滑滚动
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        const offsetTop = target.offsetTop - 80;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // 下拉菜单
            const navDropdown = document.querySelector('.nav-dropdown');
            const navDropdownToggle = document.querySelector('.nav-dropdown-toggle');
            
            if (navDropdownToggle) {
                navDropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    navDropdown.classList.toggle('active');
                });
            }
            
            // 点击其他地方关闭下拉菜单
            document.addEventListener('click', function(e) {
                if (!navDropdown.contains(e.target)) {
                    navDropdown.classList.remove('active');
                }
            });
            
            // 移动端菜单
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    const navMenu = document.querySelector('.nav-menu');
                    navMenu.style.display = navMenu.style.display === 'flex' ? 'none' : 'flex';
                    
                    // 移动端菜单打开时，重置下拉菜单状态
                    if (navMenu.style.display === 'flex') {
                        navDropdown.classList.remove('active');
                    }
                });
            }
            
            // 点击导航菜单项时关闭移动端菜单
            document.querySelectorAll('.nav-dropdown-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    const navMenu = document.querySelector('.nav-menu');
                    navMenu.style.display = 'none';
                    navDropdown.classList.remove('active');
                });
            });
            
            // 作品项点击和hover效果
            document.querySelectorAll('.work-item').forEach(item => {
                // hover效果
                item.addEventListener('mouseenter', function() {
                    this.style.opacity = '0.8';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.opacity = '1';
                });
                
                // 点击效果
                item.addEventListener('click', function(e) {
                    // 如果点击的是视频控件，不跳转
                    if (e.target.tagName === 'VIDEO' || e.target.closest('video')) {
                        return;
                    }
                    
                    const workId = this.dataset.workId;
                    if (workId) {
                        // 检查媒体类型指示器
                        const mediaIndicator = this.querySelector('.work-media-indicator');
                        if (mediaIndicator) {
                            const indicatorText = mediaIndicator.textContent;
                            // 如果同时有图片和视频，跳转到详细页面
                            if (indicatorText === '图片+视频') {
                                window.location.href = `work-detail.php?id=${workId}`;
                            }
                            // 如果只有图片，也可以跳转到详细页面查看大图
                            else if (indicatorText === '图片') {
                                window.location.href = `work-detail.php?id=${workId}`;
                            }
                            // 如果只有视频，不跳转（直接在这里播放）
                        }
                    }
                });
            });
            
            // 页面加载动画
            window.addEventListener('load', function() {
                const fadeElements = document.querySelectorAll('.fade-in-up');
                fadeElements.forEach((element, index) => {
                    setTimeout(() => {
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            });
            
            // 友情链接外部跳转警告
            document.querySelectorAll('.friend-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    const url = this.getAttribute('data-url');
                    const name = this.getAttribute('data-name');
                    const target = this.getAttribute('target');
                    
                    // 检查是否为外部链接
                    if (url && !isInternalLink(url)) {
                        e.preventDefault();
                        
                        // 显示离开提示
                        if (typeof layui !== 'undefined') {
                            layui.use(['layer'], function(){
                                var layer = layui.layer;
                                
                                layer.confirm(
                                    '<div style="padding: 10px 0;">' +
                                    '<p style="margin-bottom: 15px; font-size: 16px;">您即将离开本站，访问外部链接：</p>' +
                                    '<p style="margin-bottom: 15px; color: #1E9FFF; font-weight: bold;">' + name + '</p>' +
                                    '<p style="margin-bottom: 10px; font-size: 14px; color: #999; word-break: break-all;">' + url + '</p>' +
                                    '<p style="color: #ff6b6b; font-size: 13px;">⚠️ 请注意外部网站的安全性，本站不对外部链接的内容负责</p>' +
                                    '</div>',
                                    {
                                        title: '<i class="layui-icon layui-icon-tips" style="color: #ff9800;"></i> 外部链接提示',
                                        icon: 0,
                                        btn: ['继续访问', '取消'],
                                        btn1: function(index) {
                                            layer.close(index);
                                            // 打开外部链接
                                            if (target === '_blank') {
                                                window.open(url, '_blank');
                                            } else {
                                                window.location.href = url;
                                            }
                                        },
                                        btn2: function(index) {
                                            layer.close(index);
                                        }
                                    }
                                );
                            });
                        } else {
                            // 如果layui未加载，使用原生confirm
                            if (confirm('您即将离开本站访问：' + name + '\n\n' + url + '\n\n是否继续？')) {
                                if (target === '_blank') {
                                    window.open(url, '_blank');
                                } else {
                                    window.location.href = url;
                                }
                            }
                        }
                    }
                });
            });
            
            // 检查是否为内部链接
            function isInternalLink(url) {
                try {
                    const link = new URL(url, window.location.origin);
                    return link.hostname === window.location.hostname;
                } catch (e) {
                    // 如果URL解析失败，认为是内部链接
                    return true;
                }
            }

        });
    </script>
</body>
</html> 