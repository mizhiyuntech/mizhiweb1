<?php
require_once 'database.php';

// 获取作品ID
$work_id = intval($_GET['id'] ?? 0);

if ($work_id <= 0) {
    header('Location: index.php');
    exit;
}

// 获取作品信息
$work = getWork($work_id);

if (!$work) {
    header('Location: index.php');
    exit;
}

// 获取网站设置
$site_settings = getSiteSettings();
$site_name = $site_settings['site_name'] ?? '作品集网站';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($work['title']); ?> - <?php echo e($site_name); ?></title>
    <meta name="description" content="<?php echo e($work['description']); ?>">
    <link rel="stylesheet" href="layui/css/layui.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f8f8;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 30px;
            transition: color 0.3s ease;
        }
        
        .back-button:hover {
            color: #333;
        }
        
        .back-button i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .work-detail {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .work-header {
            padding: 40px 40px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .work-title {
            font-size: 32px;
            font-weight: 300;
            margin-bottom: 15px;
            color: #333;
        }
        
        .work-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .work-meta span {
            background: #f5f5f5;
            padding: 5px 12px;
            border-radius: 20px;
        }
        
        .work-content {
            padding: 40px;
        }
        
        .media-section {
            margin-bottom: 40px;
        }
        
        .media-section h3 {
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 20px;
            color: #333;
        }
        
        .media-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .media-item {
            background: #f8f8f8;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 16/9;
            position: relative;
        }
        
        .media-item img,
        .media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-item video {
            cursor: pointer;
        }
        
        .media-label {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .single-media {
            grid-column: span 2;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .description-section {
            margin-bottom: 40px;
        }
        
        .description-section h3 {
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 20px;
            color: #333;
        }
        
        .description-content {
            font-size: 16px;
            line-height: 1.8;
            color: #666;
            white-space: pre-wrap;
        }
        
        .tags-section {
            margin-bottom: 40px;
        }
        
        .tags-section h3 {
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 20px;
            color: #333;
        }
        
        .tags-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .tag {
            background: #f0f0f0;
            color: #666;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 300;
        }
        
        .external-link {
            text-align: center;
            margin-top: 40px;
        }
        
        .external-link a {
            display: inline-block;
            background: #333;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .external-link a:hover {
            background: #555;
        }
        
        /* 移动端适配 */
        @media (max-width: 768px) {
            .container {
                padding: 20px 10px;
            }
            
            .work-title {
                font-size: 24px;
            }
            
            .work-header {
                padding: 30px 20px 15px;
            }
            
            .work-content {
                padding: 30px 20px;
            }
            
            .media-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .single-media {
                grid-column: span 1;
            }
            
            .work-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        /* 全屏浏览样式 */
        .fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .fullscreen-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }
        
        .fullscreen-content img,
        .fullscreen-content video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .fullscreen-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .fullscreen-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="layui-icon layui-icon-return"></i>
            返回作品集
        </a>
        
        <div class="work-detail">
            <div class="work-header">
                <h1 class="work-title"><?php echo e($work['title']); ?></h1>
                <div class="work-meta">
                    <span><?php echo e($work['category_name'] ?? '未分类'); ?></span>
                    <span>发布于 <?php echo date('Y-m-d', strtotime($work['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="work-content">
                <!-- 媒体展示区域 -->
                <div class="media-section">
                    <h3>作品展示</h3>
                    <div class="media-grid">
                        <?php
                        $has_image = !empty($work['image']);
                        $has_video = !empty($work['video']);
                        
                        if ($has_image && $has_video): ?>
                            <!-- 同时有图片和视频 -->
                            <div class="media-item" onclick="openFullscreen('image', 'uploads/<?php echo e($work['image']); ?>')">
                                <img src="uploads/<?php echo e($work['image']); ?>" alt="<?php echo e($work['title']); ?>">
                                <div class="media-label">图片</div>
                            </div>
                            <div class="media-item">
                                <video controls>
                                    <source src="uploads/<?php echo e($work['video']); ?>" type="video/mp4">
                                    您的浏览器不支持视频播放
                                </video>
                                <div class="media-label">视频</div>
                            </div>
                        <?php elseif ($has_video): ?>
                            <!-- 只有视频 -->
                            <div class="media-item single-media">
                                <video controls>
                                    <source src="uploads/<?php echo e($work['video']); ?>" type="video/mp4">
                                    您的浏览器不支持视频播放
                                </video>
                                <div class="media-label">视频</div>
                            </div>
                        <?php elseif ($has_image): ?>
                            <!-- 只有图片 -->
                            <div class="media-item single-media" onclick="openFullscreen('image', 'uploads/<?php echo e($work['image']); ?>')">
                                <img src="uploads/<?php echo e($work['image']); ?>" alt="<?php echo e($work['title']); ?>">
                                <div class="media-label">图片</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 描述区域 -->
                <?php if (!empty($work['description'])): ?>
                    <div class="description-section">
                        <h3>作品描述</h3>
                        <div class="description-content"><?php echo e($work['description']); ?></div>
                    </div>
                <?php endif; ?>
                
                <!-- 标签区域 -->
                <?php if (!empty($work['tags'])): ?>
                    <div class="tags-section">
                        <h3>标签</h3>
                        <div class="tags-list">
                            <?php 
                            $tags = explode(',', $work['tags']);
                            foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if ($tag): ?>
                                    <span class="tag"><?php echo e($tag); ?></span>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 外部链接 -->
                <?php if (!empty($work['url'])): ?>
                    <div class="external-link">
                        <a href="<?php echo e($work['url']); ?>" target="_blank">
                            <i class="layui-icon layui-icon-link"></i>
                            查看原作品
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 全屏浏览层 -->
    <div class="fullscreen-overlay" id="fullscreenOverlay">
        <button class="fullscreen-close" onclick="closeFullscreen()">
            <i class="layui-icon layui-icon-close"></i>
        </button>
        <div class="fullscreen-content" id="fullscreenContent">
            <!-- 内容将由JavaScript动态插入 -->
        </div>
    </div>
    
    <script src="layui/layui.js"></script>
    <script>
        // 全屏浏览功能
        function openFullscreen(type, src) {
            const overlay = document.getElementById('fullscreenOverlay');
            const content = document.getElementById('fullscreenContent');
            
            if (type === 'image') {
                content.innerHTML = `<img src="${src}" alt="作品图片">`;
            } else if (type === 'video') {
                content.innerHTML = `<video controls><source src="${src}" type="video/mp4"></video>`;
            }
            
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeFullscreen() {
            const overlay = document.getElementById('fullscreenOverlay');
            overlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // ESC键关闭全屏
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFullscreen();
            }
        });
        
        // 点击遮罩层关闭
        document.getElementById('fullscreenOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFullscreen();
            }
        });
    </script>
</body>
</html> 