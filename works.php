<?php
require_once 'auth.php';
require_once 'layout.php';
checkAdminAuth();

$admin = getCurrentAdmin();
$site_settings = getSiteSettings();
$pdo = getDB();

$message = '';
$message_type = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $action === 'edit' ? intval($_POST['id']) : 0;
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category_id = intval($_POST['category_id']);
        $url = trim($_POST['url']);
        $tags = trim($_POST['tags']);
        $sort_order = intval($_POST['sort_order']);
        $is_active = ($_POST['is_active'] ?? '0') === '1' ? 1 : 0;
        
        if (empty($title)) {
            $message = '作品标题不能为空';
            $message_type = 'error';
        } else {
            try {
                // 处理图片上传（支持多张图片）
                $image_path = '';
                if (isset($_FILES['image']) && is_array($_FILES['image']['error'])) {
                    // 多张图片上传
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $uploaded_images = [];
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    for ($i = 0; $i < count($_FILES['image']['error']); $i++) {
                        if ($_FILES['image']['error'][$i] === 0) {
                            $file_ext = strtolower(pathinfo($_FILES['image']['name'][$i], PATHINFO_EXTENSION));
                            
                            if (in_array($file_ext, $allowed_types)) {
                                $file_name = uniqid() . '_' . $i . '.' . $file_ext;
                                $file_path = $upload_dir . $file_name;
                                
                                if (move_uploaded_file($_FILES['image']['tmp_name'][$i], $file_path)) {
                                    $uploaded_images[] = $file_name;
                                } else {
                                    $message = '图片上传失败';
                                    $message_type = 'error';
                                    break;
                                }
                            } else {
                                $message = '只允许上传 JPG、PNG、GIF、WEBP 格式的图片';
                                $message_type = 'error';
                                break;
                            }
                        }
                    }
                    
                    if (!empty($uploaded_images)) {
                        $image_path = implode(',', $uploaded_images);
                    }
                } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    // 单张图片上传（保持兼容性）
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_ext, $allowed_types)) {
                        $file_name = uniqid() . '.' . $file_ext;
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                            $image_path = $file_name;
                        } else {
                            $message = '图片上传失败';
                            $message_type = 'error';
                        }
                    } else {
                        $message = '只允许上传 JPG、PNG、GIF、WEBP 格式的图片';
                        $message_type = 'error';
                    }
                }
                
                // 处理视频上传
                $video_path = '';
                if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['mp4'];
                    $max_size = 10 * 1024 * 1024; // 10MB
                    
                    if (in_array($file_ext, $allowed_types)) {
                        if ($_FILES['video']['size'] <= $max_size) {
                            $file_name = uniqid() . '.' . $file_ext;
                            $file_path = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($_FILES['video']['tmp_name'], $file_path)) {
                                $video_path = $file_name;
                            } else {
                                $message = '视频上传失败';
                                $message_type = 'error';
                            }
                        } else {
                            $message = '视频文件大小不能超过 10MB';
                            $message_type = 'error';
                        }
                    } else {
                        $message = '只允许上传 MP4 格式的视频';
                        $message_type = 'error';
                    }
                }
                
                if (empty($message)) {
                    if ($action === 'add') {
                        $stmt = $pdo->prepare("INSERT INTO works (title, description, category_id, image, video, url, tags, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $description, $category_id, $image_path, $video_path, $url, $tags, $sort_order, $is_active]);
                        $message = '作品添加成功';
                        $message_type = 'success';
                    } else {
                        // 编辑时，如果没有上传新文件，保留原文件
                        if (empty($image_path) && empty($video_path)) {
                            $stmt = $pdo->prepare("UPDATE works SET title = ?, description = ?, category_id = ?, url = ?, tags = ?, sort_order = ?, is_active = ? WHERE id = ?");
                            $stmt->execute([$title, $description, $category_id, $url, $tags, $sort_order, $is_active, $id]);
                        } else {
                            // 获取旧文件
                            $stmt = $pdo->prepare("SELECT image, video FROM works WHERE id = ?");
                            $stmt->execute([$id]);
                            $old_files = $stmt->fetch();
                            
                            // 删除旧文件
                            if (!empty($image_path) && $old_files['image']) {
                                $old_images = explode(',', $old_files['image']);
                                foreach ($old_images as $old_image) {
                                    $old_image = trim($old_image);
                                    if ($old_image && file_exists('../uploads/' . $old_image)) {
                                        unlink('../uploads/' . $old_image);
                                    }
                                }
                            }
                            if (!empty($video_path) && $old_files['video'] && file_exists('../uploads/' . $old_files['video'])) {
                                unlink('../uploads/' . $old_files['video']);
                            }
                            
                            // 更新数据库
                            $final_image = !empty($image_path) ? $image_path : $old_files['image'];
                            $final_video = !empty($video_path) ? $video_path : $old_files['video'];
                            
                            $stmt = $pdo->prepare("UPDATE works SET title = ?, description = ?, category_id = ?, image = ?, video = ?, url = ?, tags = ?, sort_order = ?, is_active = ? WHERE id = ?");
                            $stmt->execute([$title, $description, $category_id, $final_image, $final_video, $url, $tags, $sort_order, $is_active, $id]);
                        }
                        $message = '作品更新成功';
                        $message_type = 'success';
                    }
                }
            } catch (Exception $e) {
                $message = '操作失败：' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        
        try {
            // 删除图片和视频文件
            $stmt = $pdo->prepare("SELECT image, video FROM works WHERE id = ?");
            $stmt->execute([$id]);
            $files = $stmt->fetch();
            
            if ($files['image']) {
                $images = explode(',', $files['image']);
                foreach ($images as $image) {
                    $image = trim($image);
                    if ($image && file_exists('../uploads/' . $image)) {
                        unlink('../uploads/' . $image);
                    }
                }
            }
            if ($files['video'] && file_exists('../uploads/' . $files['video'])) {
                unlink('../uploads/' . $files['video']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM works WHERE id = ?");
            $stmt->execute([$id]);
            $message = '作品删除成功';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = '删除失败：' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// 获取作品列表（带分页）
$page = intval($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

$category_filter = intval($_GET['category'] ?? 0);
$search = trim($_GET['search'] ?? '');

$where_conditions = ['1=1'];
$params = [];

if ($category_filter > 0) {
    $where_conditions[] = 'w.category_id = ?';
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = '(w.title LIKE ? OR w.description LIKE ? OR w.tags LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where_conditions);

// 获取总数
$count_sql = "SELECT COUNT(*) FROM works w WHERE " . $where_clause;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();

// 获取作品列表
$sql = "SELECT w.*, c.name as category_name 
        FROM works w 
        LEFT JOIN categories c ON w.category_id = c.id 
        WHERE " . $where_clause . " 
        ORDER BY w.sort_order ASC, w.id DESC 
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$works = $stmt->fetchAll();

// 获取分类列表（包括停用的分类，以便管理员能看到完整情况）
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();

// 获取当前编辑的作品
$edit_work = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM works WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_work = $stmt->fetch();
}

$total_pages = ceil($total_count / $per_page);

// 开始构建页面内容
ob_start();
?>

<!-- 简化的样式 -->
<style>
    .work-card {
        transition: all 0.3s ease;
    }
    
    .work-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .work-image {
        border-radius: 6px;
        overflow: hidden;
    }
    
    .layui-upload {
        display: inline-block;
    }
    
    /* 移动端作品卡片优化 */
    @media screen and (max-width: 768px) {
        .work-card {
            margin-bottom: 15px;
        }
        
        .work-image {
            height: 150px !important;
        }
        
        .layui-col-md3 {
            margin-bottom: 15px;
        }
        
        /* 搜索表单优化 */
        .layui-col-sm4, .layui-col-sm3, .layui-col-sm2 {
            margin-bottom: 10px;
        }
        
        /* 按钮全宽显示 */
        .layui-btn-fluid {
            width: 100%;
            margin-bottom: 5px;
        }
    }
    
    @media screen and (max-width: 480px) {
        .work-image {
            height: 120px !important;
        }
        
        .work-card .layui-card-body {
            padding: 10px !important;
        }
        
        /* 作品标题字体调整 */
        .work-title {
            font-size: 13px !important;
        }
        
        /* 隐藏部分信息以节省空间 */
        .work-meta {
            font-size: 10px !important;
        }
    }
</style>


                
                <!-- 搜索和筛选 -->
                <div class="layui-row">
                    <div class="layui-col-xs12">
                        <div class="layui-card">
                            <div class="layui-card-body">
                                <form method="GET">
                                    <div class="layui-row layui-col-space15">
                                        <div class="layui-col-xs12 layui-col-sm4">
                                            <input type="text" name="search" placeholder="搜索作品标题、描述或标签..." 
                                                   value="<?php echo e($search); ?>" class="layui-input">
                                        </div>
                                        <div class="layui-col-xs12 layui-col-sm3">
                                            <select name="category" class="layui-input">
                                                <option value="">所有分类</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                                        <?php echo e($category['name']); ?><?php echo $category['is_active'] ? '' : ' (已停用)'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="layui-col-xs6 layui-col-sm2">
                                            <button type="submit" class="layui-btn layui-btn-normal layui-btn-fluid">
                                                <i class="layui-icon layui-icon-search"></i>
                                                搜索
                                            </button>
                                        </div>
                                        <div class="layui-col-xs6 layui-col-sm3">
                                            <button type="button" class="layui-btn layui-btn-primary layui-btn-fluid" onclick="showAddForm()">
                                                <i class="layui-icon layui-icon-add-1"></i>
                                                添加作品
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 作品列表 -->
                <div class="layui-row">
                    <div class="layui-col-xs12">
                        <div class="layui-card">
                            <div class="layui-card-header">
                                作品列表 <span class="layui-badge"><?php echo $total_count; ?></span>
                            </div>
                            <div class="layui-card-body">
                                <?php if (empty($works)): ?>
                                    <div style="text-align: center; padding: 60px 0; color: #999;">
                                        <i class="layui-icon layui-icon-picture" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                                        <p>暂无作品数据</p>
                                        <button class="layui-btn layui-btn-primary" onclick="showAddForm()">添加第一个作品</button>
                                    </div>
                                <?php else: ?>
                                    <div class="layui-row layui-col-space15">
                                        <?php foreach ($works as $work): ?>
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md3">
                                                <div class="layui-card work-card">
                                                    <div class="layui-card-body">
                                                                                                <!-- 作品图片/视频 -->
                                        <div class="work-image" style="width: 100%; height: 150px; background: #f5f5f5; border-radius: 6px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 10px;">
                                            <?php if ($work['video']): ?>
                                                <video controls style="width: 100%; height: 100%; object-fit: cover;">
                                                    <source src="../uploads/<?php echo e($work['video']); ?>" type="video/mp4">
                                                    您的浏览器不支持视频播放
                                                </video>
                                            <?php elseif ($work['image']): ?>
                                                <?php 
                                                $images = explode(',', $work['image']);
                                                if (count($images) > 1): ?>
                                                    <div style="display: flex; flex-wrap: wrap; height: 100%;">
                                                        <?php foreach ($images as $index => $image): ?>
                                                            <img src="../uploads/<?php echo e(trim($image)); ?>" alt="<?php echo e($work['title']); ?>" style="width: <?php echo count($images) > 2 ? '50%' : '100%'; ?>; height: <?php echo count($images) > 2 ? '50%' : '100%'; ?>; object-fit: cover; <?php echo $index > 0 ? 'border-left: 1px solid #fff;' : ''; ?>">
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="../uploads/<?php echo e($work['image']); ?>" alt="<?php echo e($work['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #ccc; font-size: 12px;">暂无图片/视频</span>
                                            <?php endif; ?>
                                        </div>
                                                        
                                                        <!-- 作品信息 -->
                                                        <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo e($work['title']); ?></h3>
                                                        <div style="font-size: 11px; color: #999; margin-bottom: 8px;">
                                                            <?php echo e($work['category_name'] ?? '未分类'); ?> | 
                                                            <span class="layui-badge <?php echo $work['is_active'] ? 'layui-bg-green' : 'layui-bg-gray'; ?>" style="font-size: 10px;">
                                                                <?php echo $work['is_active'] ? '启用' : '禁用'; ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <!-- 操作按钮 -->
                                                        <div class="layui-row layui-col-space5">
                                                            <div class="layui-col-xs6">
                                                                <button class="layui-btn layui-btn-xs layui-btn-normal layui-btn-fluid" onclick="editWork(<?php echo $work['id']; ?>)">
                                                                    <i class="layui-icon layui-icon-edit"></i>
                                                                    编辑
                                                                </button>
                                                            </div>
                                                            <div class="layui-col-xs6">
                                                                <button class="layui-btn layui-btn-xs layui-btn-danger layui-btn-fluid" onclick="deleteWork(<?php echo $work['id']; ?>, '<?php echo e($work['title']); ?>')">
                                                                    <i class="layui-icon layui-icon-delete"></i>
                                                                    删除
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- 分页 -->
                                    <?php if ($total_pages > 1): ?>
                                        <div style="text-align: center; margin-top: 30px;">
                                            <?php
                                            $base_url = '?';
                                            $params = [];
                                            if ($search) $params[] = 'search=' . urlencode($search);
                                            if ($category_filter) $params[] = 'category=' . $category_filter;
                                            if ($params) $base_url .= implode('&', $params) . '&';
                                            ?>
                                            
                                            <?php if ($page > 1): ?>
                                                <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="layui-btn layui-btn-primary">上一页</a>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <?php if ($i == $page): ?>
                                                    <span class="layui-btn layui-btn-normal"><?php echo $i; ?></span>
                                                <?php else: ?>
                                                    <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" class="layui-btn layui-btn-primary"><?php echo $i; ?></a>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="layui-btn layui-btn-primary">下一页</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加/编辑作品弹窗 -->
    
        <script>
        var layuiForm, layuiLayer;
        
        // 页面加载完成后检查并显示消息提示
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof layui !== 'undefined') {
                layui.use(['layer'], function(){
                    var layer = layui.layer;
                    
                    // 显示消息提示
                    <?php if ($message): ?>
                        <?php if ($message_type === 'success'): ?>
                            layer.msg('<?php echo addslashes($message); ?>', {
                                icon: 1,
                                time: 3000,
                                offset: 'auto'
                            });
                        <?php else: ?>
                            layer.msg('<?php echo addslashes($message); ?>', {
                                icon: 2,
                                time: 4000,
                                offset: 'auto'
                            });
                        <?php endif; ?>
                    <?php endif; ?>
                });
            }
        });
        
        // 简单的发布作品功能
        function showAddForm() {
            if (typeof layui === 'undefined') {
                alert('正在加载，请稍候...');
                return;
            }
            
            layui.use(['layer', 'form'], function(){
                var layer = layui.layer;
                var form = layui.form;
                
                // 创建简单的表单HTML
                var formHtml = `
                    <form class="layui-form" style="padding: 20px;" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品标题*</label>
                            <div class="layui-input-block">
                                <input type="text" name="title" required lay-verify="required" placeholder="请输入作品标题" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品分类</label>
                            <div class="layui-input-block">
                                <select name="category_id">
                                    <option value="0">未分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo e($category['name']); ?><?php echo $category['is_active'] ? '' : ' (已停用)'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="layui-form-item layui-form-text">
                            <label class="layui-form-label">作品描述</label>
                            <div class="layui-input-block">
                                <textarea name="description" placeholder="请输入作品描述..." class="layui-textarea" style="min-height: 150px;"></textarea>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品链接</label>
                            <div class="layui-input-block">
                                <input type="url" name="url" placeholder="请输入作品链接（可选）" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">标签</label>
                            <div class="layui-input-block">
                                <input type="text" name="tags" placeholder="请输入标签，用逗号分隔" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品图片</label>
                            <div class="layui-input-block">
                                <div class="layui-upload">
                                    <button type="button" class="layui-btn layui-btn-normal" id="uploadBtn">
                                        <i class="layui-icon layui-icon-upload"></i> 选择图片
                                    </button>
                                    <input type="file" name="image[]" accept="image/*" multiple style="display: none;" id="fileInput">
                                </div>
                                <div class="layui-form-mid layui-word-aux">支持JPG、PNG、GIF、WEBP格式，可选择多张图片</div>
                                <div id="imagePreview" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品视频</label>
                            <div class="layui-input-block">
                                <div class="layui-upload">
                                    <button type="button" class="layui-btn layui-btn-normal" id="videoUploadBtn">
                                        <i class="layui-icon layui-icon-upload"></i> 选择视频
                                    </button>
                                    <input type="file" name="video" accept="video/mp4" style="display: none;" id="videoFileInput">
                                </div>
                                <div class="layui-form-mid layui-word-aux">支持MP4格式，最大10MB</div>
                                <div id="videoPreview" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">排序</label>
                            <div class="layui-input-block">
                                <input type="number" name="sort_order" value="0" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">状态</label>
                            <div class="layui-input-block">
                                <input type="checkbox" name="is_active" lay-skin="switch" lay-text="启用|禁用" checked>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal" lay-submit lay-filter="addWork">
                                    <i class="layui-icon layui-icon-ok"></i> 保存作品
                                </button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="layer.closeAll()">
                                    取消
                                </button>
                            </div>
                        </div>
                    </form>
                `;
                
                // 打开弹窗
                var $ = layui.$;
                var isMobile = $(window).width() <= 768;
                var index = layer.open({
                    type: 1,
                    title: '添加作品',
                    content: formHtml,
                    area: isMobile ? ['95%', '85%'] : ['600px', '700px'],
                    maxmin: !isMobile,
                    success: function(layero, index) {
                        // 渲染表单
                        form.render();
                        
                        // 绑定图片上传
                        var uploadBtn = layero.find('#uploadBtn')[0];
                        var fileInput = layero.find('#fileInput')[0];
                        var imagePreview = layero.find('#imagePreview')[0];
                        
                        uploadBtn.onclick = function() {
                            fileInput.click();
                        };
                        
                        fileInput.onchange = function(e) {
                            var files = e.target.files;
                            if (files.length > 0) {
                                imagePreview.innerHTML = '';
                                for (var i = 0; i < files.length; i++) {
                                    var file = files[i];
                                    var reader = new FileReader();
                                    reader.onload = function(e) {
                                        var img = document.createElement('img');
                                        img.src = e.target.result;
                                        img.style.cssText = 'max-width: 150px; max-height: 150px; border-radius: 6px; margin-right: 10px; margin-bottom: 10px;';
                                        imagePreview.appendChild(img);
                                    };
                                    reader.readAsDataURL(file);
                                }
                            }
                        };
                        
                        // 绑定视频上传
                        var videoUploadBtn = layero.find('#videoUploadBtn')[0];
                        var videoFileInput = layero.find('#videoFileInput')[0];
                        var videoPreview = layero.find('#videoPreview')[0];
                        
                        videoUploadBtn.onclick = function() {
                            videoFileInput.click();
                        };
                        
                        videoFileInput.onchange = function(e) {
                            var file = e.target.files[0];
                            if (file) {
                                // 检查文件大小
                                var maxSize = 10 * 1024 * 1024; // 10MB
                                if (file.size > maxSize) {
                                    layer.msg('视频文件大小不能超过10MB', {icon: 2});
                                    videoFileInput.value = '';
                                    return;
                                }
                                
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    videoPreview.innerHTML = '<video controls style="max-width: 300px; max-height: 200px; border-radius: 6px;"><source src="' + e.target.result + '" type="video/mp4"></video>';
                                };
                                reader.readAsDataURL(file);
                            }
                        };
                        
                        // 表单提交
                        form.on('submit(addWork)', function(data) {
                            // 创建FormData
                            var formData = new FormData();
                            var formElement = layero.find('form')[0];
                            
                            // 添加表单数据
                            var inputs = formElement.querySelectorAll('input, textarea, select');
                            for (var i = 0; i < inputs.length; i++) {
                                var input = inputs[i];
                                if (input.type === 'file') {
                                    if (input.name === 'image[]' && input.files.length > 0) {
                                        // 处理多个图片文件
                                        for (var j = 0; j < input.files.length; j++) {
                                            formData.append('image[]', input.files[j]);
                                        }
                                    } else if (input.name === 'video' && input.files[0]) {
                                        // 处理单个视频文件
                                        formData.append(input.name, input.files[0]);
                                    }
                                } else if (input.type === 'checkbox') {
                                    formData.append(input.name, input.checked ? '1' : '0');
                                } else {
                                    formData.append(input.name, input.value);
                                }
                            }
                            
                            // 提交表单
                            layer.msg('正在保存...', {icon: 16, shade: 0.3});
                            
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', window.location.href, true);
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4) {
                                    if (xhr.status === 200) {
                                        layer.closeAll();
                                        layer.msg('作品添加成功！', {icon: 1}, function() {
                                            window.location.reload();
                                        });
                                    } else {
                                        layer.msg('保存失败，请重试', {icon: 2});
                                    }
                                }
                            };
                            xhr.send(formData);
                            
                            return false; // 阻止默认提交
                        });
                    }
                });
                         });
        }
        
        <?php if ($edit_work): ?>
        // 如果是编辑模式，自动打开编辑表单
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                showEditForm(<?php echo $edit_work['id']; ?>);
            }, 500);
        });
        <?php endif; ?>
        
        // 显示编辑表单
        function showEditForm(workId) {
            if (typeof layui === 'undefined') {
                alert('正在加载，请稍候...');
                return;
            }
            
            layui.use(['layer', 'form'], function(){
                var layer = layui.layer;
                var form = layui.form;
                
                // 获取编辑数据（从PHP传递）
                <?php if ($edit_work): ?>
                var editData = {
                    id: <?php echo $edit_work['id']; ?>,
                    title: '<?php echo e($edit_work['title']); ?>',
                    category_id: <?php echo $edit_work['category_id']; ?>,
                    description: `<?php echo addslashes($edit_work['description']); ?>`,
                    url: '<?php echo e($edit_work['url']); ?>',
                    tags: '<?php echo e($edit_work['tags']); ?>',
                    sort_order: <?php echo $edit_work['sort_order']; ?>,
                    is_active: <?php echo $edit_work['is_active'] ? 'true' : 'false'; ?>
                };
                <?php else: ?>
                var editData = null;
                <?php endif; ?>
                
                if (!editData) {
                    layer.msg('编辑数据获取失败', {icon: 2});
                    return;
                }
                
                // 创建编辑表单HTML
                var formHtml = `
                    <form class="layui-form" style="padding: 20px;" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="${editData.id}">
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品标题*</label>
                            <div class="layui-input-block">
                                <input type="text" name="title" value="${editData.title}" required lay-verify="required" placeholder="请输入作品标题" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品分类</label>
                            <div class="layui-input-block">
                                <select name="category_id">
                                    <option value="0" ${editData.category_id == 0 ? 'selected' : ''}>未分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" ${editData.category_id == <?php echo $category['id']; ?> ? 'selected' : ''}><?php echo e($category['name']); ?><?php echo $category['is_active'] ? '' : ' (已停用)'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="layui-form-item layui-form-text">
                            <label class="layui-form-label">作品描述</label>
                            <div class="layui-input-block">
                                <textarea name="description" placeholder="请输入作品描述..." class="layui-textarea" style="min-height: 150px;">${editData.description}</textarea>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品链接</label>
                            <div class="layui-input-block">
                                <input type="url" name="url" value="${editData.url}" placeholder="请输入作品链接（可选）" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">标签</label>
                            <div class="layui-input-block">
                                <input type="text" name="tags" value="${editData.tags}" placeholder="请输入标签，用逗号分隔" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品图片</label>
                            <div class="layui-input-block">
                                <div class="layui-upload">
                                    <button type="button" class="layui-btn layui-btn-normal" id="editUploadBtn">
                                        <i class="layui-icon layui-icon-upload"></i> 选择新图片
                                    </button>
                                    <input type="file" name="image[]" accept="image/*" multiple style="display: none;" id="editFileInput">
                                </div>
                                <div class="layui-form-mid layui-word-aux">支持JPG、PNG、GIF、WEBP格式，可选择多张图片，不选择则保持原图片</div>
                                <div id="editImagePreview" style="margin-top: 10px;">
                                    <?php if ($edit_work && $edit_work['image']): ?>
                                        <?php 
                                        $images = explode(',', $edit_work['image']);
                                        foreach ($images as $image): ?>
                                            <img src="../uploads/<?php echo e(trim($image)); ?>" style="max-width: 150px; max-height: 150px; border-radius: 6px; margin-right: 10px; margin-bottom: 10px;">
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">作品视频</label>
                            <div class="layui-input-block">
                                <div class="layui-upload">
                                    <button type="button" class="layui-btn layui-btn-normal" id="editVideoUploadBtn">
                                        <i class="layui-icon layui-icon-upload"></i> 选择新视频
                                    </button>
                                    <input type="file" name="video" accept="video/mp4" style="display: none;" id="editVideoFileInput">
                                </div>
                                <div class="layui-form-mid layui-word-aux">支持MP4格式，最大10MB，不选择则保持原视频</div>
                                <div id="editVideoPreview" style="margin-top: 10px;">
                                    <?php if ($edit_work && $edit_work['video']): ?>
                                        <video controls style="max-width: 300px; max-height: 200px; border-radius: 6px;">
                                            <source src="../uploads/<?php echo e($edit_work['video']); ?>" type="video/mp4">
                                        </video>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">排序</label>
                            <div class="layui-input-block">
                                <input type="number" name="sort_order" value="${editData.sort_order}" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">状态</label>
                            <div class="layui-input-block">
                                <input type="checkbox" name="is_active" lay-skin="switch" lay-text="启用|禁用" ${editData.is_active ? 'checked' : ''}>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal" lay-submit lay-filter="editWork">
                                    <i class="layui-icon layui-icon-ok"></i> 保存修改
                                </button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="layer.closeAll(); window.location.href='works.php';">
                                    取消
                                </button>
                            </div>
                        </div>
                    </form>
                `;
                
                // 打开编辑弹窗
                var $ = layui.$;
                var isMobile = $(window).width() <= 768;
                var index = layer.open({
                    type: 1,
                    title: '编辑作品',
                    content: formHtml,
                    area: isMobile ? ['95%', '85%'] : ['600px', '700px'],
                    maxmin: !isMobile,
                    success: function(layero, index) {
                        // 渲染表单
                        form.render();
                        
                        // 绑定图片上传
                        var uploadBtn = layero.find('#editUploadBtn')[0];
                        var fileInput = layero.find('#editFileInput')[0];
                        var imagePreview = layero.find('#editImagePreview')[0];
                        
                        uploadBtn.onclick = function() {
                            fileInput.click();
                        };
                        
                        fileInput.onchange = function(e) {
                            var files = e.target.files;
                            if (files.length > 0) {
                                imagePreview.innerHTML = '';
                                for (var i = 0; i < files.length; i++) {
                                    var file = files[i];
                                    var reader = new FileReader();
                                    reader.onload = function(e) {
                                        var img = document.createElement('img');
                                        img.src = e.target.result;
                                        img.style.cssText = 'max-width: 150px; max-height: 150px; border-radius: 6px; margin-right: 10px; margin-bottom: 10px;';
                                        imagePreview.appendChild(img);
                                    };
                                    reader.readAsDataURL(file);
                                }
                            }
                        };
                        
                        // 绑定视频上传
                        var videoUploadBtn = layero.find('#editVideoUploadBtn')[0];
                        var videoFileInput = layero.find('#editVideoFileInput')[0];
                        var videoPreview = layero.find('#editVideoPreview')[0];
                        
                        videoUploadBtn.onclick = function() {
                            videoFileInput.click();
                        };
                        
                        videoFileInput.onchange = function(e) {
                            var file = e.target.files[0];
                            if (file) {
                                // 检查文件大小
                                var maxSize = 10 * 1024 * 1024; // 10MB
                                if (file.size > maxSize) {
                                    layer.msg('视频文件大小不能超过10MB', {icon: 2});
                                    videoFileInput.value = '';
                                    return;
                                }
                                
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    videoPreview.innerHTML = '<video controls style="max-width: 300px; max-height: 200px; border-radius: 6px;"><source src="' + e.target.result + '" type="video/mp4"></video>';
                                };
                                reader.readAsDataURL(file);
                            }
                        };
                        
                        // 表单提交
                        form.on('submit(editWork)', function(data) {
                            // 创建FormData
                            var formData = new FormData();
                            var formElement = layero.find('form')[0];
                            
                            // 添加表单数据
                            var inputs = formElement.querySelectorAll('input, textarea, select');
                            for (var i = 0; i < inputs.length; i++) {
                                var input = inputs[i];
                                if (input.type === 'file') {
                                    if (input.name === 'image[]' && input.files.length > 0) {
                                        // 处理多个图片文件
                                        for (var j = 0; j < input.files.length; j++) {
                                            formData.append('image[]', input.files[j]);
                                        }
                                    } else if (input.name === 'video' && input.files[0]) {
                                        // 处理单个视频文件
                                        formData.append(input.name, input.files[0]);
                                    }
                                } else if (input.type === 'checkbox') {
                                    formData.append(input.name, input.checked ? '1' : '0');
                                } else {
                                    formData.append(input.name, input.value);
                                }
                            }
                            
                            // 提交表单
                            layer.msg('正在保存...', {icon: 16, shade: 0.3});
                            
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', 'works.php', true);
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4) {
                                    if (xhr.status === 200) {
                                        layer.closeAll();
                                        layer.msg('作品更新成功！', {icon: 1}, function() {
                                            window.location.href = 'works.php';
                                        });
                                    } else {
                                        layer.msg('保存失败，请重试', {icon: 2});
                                    }
                                }
                            };
                            xhr.send(formData);
                            
                            return false; // 阻止默认提交
                        });
                    }
                });
            });
        }
        
        // 编辑作品
        function editWork(id) {
            // 直接跳转到编辑页面，让PHP处理数据并自动弹出编辑表单
            window.location.href = '?edit=' + id;
        }
        
        // 删除作品
        function deleteWork(id, title) {
            layui.use('layer', function(){
                var layer = layui.layer;
                layer.confirm('确定要删除作品《' + title + '》吗？', {
                    icon: 3,
                    title: '确认删除',
                    btn: ['确定删除', '取消']
                }, function(confirmIndex) {
                    // 显示删除中状态
                    layer.load(2, {content: '正在删除...'});
                    
                    // 使用AJAX提交删除请求
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'works.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            layer.closeAll(); // 关闭加载层
                            
                            if (xhr.status === 200) {
                                // 检查返回内容是否包含成功标识
                                if (xhr.responseText.indexOf('作品删除成功') !== -1) {
                                    layer.msg('作品删除成功！', {
                                        icon: 1,
                                        time: 2000
                                    }, function() {
                                        // 成功后刷新页面
                                        window.location.reload();
                                    });
                                } else {
                                    layer.msg('删除失败，请重试', {icon: 2, time: 3000});
                                }
                            } else {
                                layer.msg('删除失败，服务器错误', {icon: 2, time: 3000});
                            }
                        }
                    };
                    
                    // 发送删除请求
                    xhr.send('action=delete&id=' + id);
                    layer.close(confirmIndex);
                });
            });
        }
    </script>
    
    <?php
    // 获取页面内容
    $content = ob_get_clean();
    
    // 渲染完整页面
    echo renderAdminLayout('作品管理', 'works', $content);
    ?> 