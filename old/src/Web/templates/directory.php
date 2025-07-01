<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-folder-open"></i> 目录内容</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary mr-2" id="showFilter">
                    <i class="fas fa-filter"></i> 筛选
                </button>
                <a href="#" class="btn btn-sm btn-outline-secondary" id="toggleView">
                    <i class="fas fa-th-large"></i> 切换视图
                </a>
            </div>
        </div>
    </div>

    <!-- 筛选面板 -->
    <div id="filterPanel" class="card-body border-bottom" style="display: <?= isset($filterApplied) && $filterApplied ? 'block' : 'none' ?>;">
        <form method="get" class="row">
            <?php if (strpos($path, 'php') === 0): ?>
            <div class="col-md-3 mb-3">
                <label for="version">PHP版本</label>
                <select class="form-control" id="version" name="version">
                    <option value="">全部版本</option>
                    <option value="5.6" <?= isset($queryParams['version']) && $queryParams['version'] === '5.6' ? 'selected' : '' ?>>5.6.x</option>
                    <option value="7.0" <?= isset($queryParams['version']) && $queryParams['version'] === '7.0' ? 'selected' : '' ?>>7.0.x</option>
                    <option value="7.1" <?= isset($queryParams['version']) && $queryParams['version'] === '7.1' ? 'selected' : '' ?>>7.1.x</option>
                    <option value="7.2" <?= isset($queryParams['version']) && $queryParams['version'] === '7.2' ? 'selected' : '' ?>>7.2.x</option>
                    <option value="7.3" <?= isset($queryParams['version']) && $queryParams['version'] === '7.3' ? 'selected' : '' ?>>7.3.x</option>
                    <option value="7.4" <?= isset($queryParams['version']) && $queryParams['version'] === '7.4' ? 'selected' : '' ?>>7.4.x</option>
                    <option value="8.0" <?= isset($queryParams['version']) && $queryParams['version'] === '8.0' ? 'selected' : '' ?>>8.0.x</option>
                    <option value="8.1" <?= isset($queryParams['version']) && $queryParams['version'] === '8.1' ? 'selected' : '' ?>>8.1.x</option>
                    <option value="8.2" <?= isset($queryParams['version']) && $queryParams['version'] === '8.2' ? 'selected' : '' ?>>8.2.x</option>
                    <option value="8.3" <?= isset($queryParams['version']) && $queryParams['version'] === '8.3' ? 'selected' : '' ?>>8.3.x</option>
                </select>
            </div>
            <?php elseif (strpos($path, 'pecl') === 0 || strpos($path, 'extensions') === 0): ?>
            <div class="col-md-3 mb-3">
                <label for="version">PHP版本兼容性</label>
                <select class="form-control" id="version" name="version">
                    <option value="">全部版本</option>
                    <option value="5.6" <?= isset($queryParams['version']) && $queryParams['version'] === '5.6' ? 'selected' : '' ?>>PHP 5.6</option>
                    <option value="7.0" <?= isset($queryParams['version']) && $queryParams['version'] === '7.0' ? 'selected' : '' ?>>PHP 7.0</option>
                    <option value="7.1" <?= isset($queryParams['version']) && $queryParams['version'] === '7.1' ? 'selected' : '' ?>>PHP 7.1</option>
                    <option value="7.2" <?= isset($queryParams['version']) && $queryParams['version'] === '7.2' ? 'selected' : '' ?>>PHP 7.2</option>
                    <option value="7.3" <?= isset($queryParams['version']) && $queryParams['version'] === '7.3' ? 'selected' : '' ?>>PHP 7.3</option>
                    <option value="7.4" <?= isset($queryParams['version']) && $queryParams['version'] === '7.4' ? 'selected' : '' ?>>PHP 7.4</option>
                    <option value="8.0" <?= isset($queryParams['version']) && $queryParams['version'] === '8.0' ? 'selected' : '' ?>>PHP 8.0</option>
                    <option value="8.1" <?= isset($queryParams['version']) && $queryParams['version'] === '8.1' ? 'selected' : '' ?>>PHP 8.1</option>
                    <option value="8.2" <?= isset($queryParams['version']) && $queryParams['version'] === '8.2' ? 'selected' : '' ?>>PHP 8.2</option>
                    <option value="8.3" <?= isset($queryParams['version']) && $queryParams['version'] === '8.3' ? 'selected' : '' ?>>PHP 8.3</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-3 mb-3">
                <label for="ext">文件类型</label>
                <select class="form-control" id="ext" name="ext">
                    <option value="">全部类型</option>
                    <option value="tar.gz" <?= isset($queryParams['ext']) && $queryParams['ext'] === 'tar.gz' ? 'selected' : '' ?>>tar.gz</option>
                    <option value="tar.bz2" <?= isset($queryParams['ext']) && $queryParams['ext'] === 'tar.bz2' ? 'selected' : '' ?>>tar.bz2</option>
                    <option value="tar.xz" <?= isset($queryParams['ext']) && $queryParams['ext'] === 'tar.xz' ? 'selected' : '' ?>>tar.xz</option>
                    <option value="zip" <?= isset($queryParams['ext']) && $queryParams['ext'] === 'zip' ? 'selected' : '' ?>>zip</option>
                    <option value="tgz" <?= isset($queryParams['ext']) && $queryParams['ext'] === 'tgz' ? 'selected' : '' ?>>tgz</option>
                    <option value="phar" <?= isset($queryParams['ext']) && $queryParams['ext'] === 'phar' ? 'selected' : '' ?>>phar</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="search">搜索</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="输入关键词" value="<?= isset($queryParams['search']) ? htmlspecialchars($queryParams['search']) : '' ?>">
            </div>

            <div class="col-md-2 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary mr-2">应用筛选</button>
                <a href="/<?= $path ?>" class="btn btn-outline-secondary">重置</a>
            </div>
        </form>

        <?php if (isset($filterApplied) && $filterApplied): ?>
        <div class="alert alert-info mb-0 mt-2">
            <i class="fas fa-info-circle"></i> 当前筛选: <?= $filterDescription ?>
            <a href="/<?= $path ?>" class="btn btn-sm btn-outline-info ml-2">清除筛选</a>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <!-- 表格视图 -->
        <div id="tableView">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>文件名</th>
                            <th>大小</th>
                            <th>修改时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($path)): ?>
                        <tr>
                            <td>
                                <a href="/<?= dirname($path) ?>" class="text-primary">
                                    <i class="fas fa-level-up-alt"></i> 上级目录
                                </a>
                            </td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($files as $file): ?>
                            <?php
                                $fullPath = $filePath . '/' . $file;
                                $isDir = is_dir($fullPath);
                                $size = $isDir ? '-' : $formatSize(filesize($fullPath));
                                $modTime = date('Y-m-d H:i:s', filemtime($fullPath));
                                $fileExt = pathinfo($file, PATHINFO_EXTENSION);

                                // 确定文件图标
                                $icon = 'fas fa-file';
                                if ($isDir) {
                                    $icon = 'fas fa-folder';
                                } elseif (in_array($fileExt, ['php', 'phar'])) {
                                    $icon = 'fab fa-php';
                                } elseif (in_array($fileExt, ['zip', 'gz', 'tar', 'bz2', 'xz'])) {
                                    $icon = 'fas fa-file-archive';
                                } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                                    $icon = 'fas fa-file-image';
                                } elseif (in_array($fileExt, ['txt', 'md', 'log'])) {
                                    $icon = 'fas fa-file-alt';
                                }
                            ?>
                            <tr>
                                <td>
                                    <a href="/<?= $path ?>/<?= $file ?>" class="<?= $isDir ? 'text-primary' : 'text-dark' ?>">
                                        <i class="<?= $icon ?>"></i> <?= $file ?><?= $isDir ? '/' : '' ?>
                                    </a>
                                </td>
                                <td><?= $size ?></td>
                                <td><?= $modTime ?></td>
                                <td>
                                    <a href="/<?= $path ?>/<?= $file ?>" class="btn btn-sm btn-outline-primary" title="下载">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 网格视图 -->
        <div id="gridView" class="p-3" style="display: none;">
            <div class="row">
                <?php if (!empty($path)): ?>
                <div class="col-md-2 col-sm-3 col-4 mb-3">
                    <a href="/<?= dirname($path) ?>" class="text-decoration-none">
                        <div class="card h-100">
                            <div class="card-body text-center py-3">
                                <i class="fas fa-level-up-alt fa-2x text-primary mb-2"></i>
                                <p class="mb-0 text-truncate">上级目录</p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <?php foreach ($files as $file): ?>
                    <?php
                        $fullPath = $filePath . '/' . $file;
                        $isDir = is_dir($fullPath);
                        $fileExt = pathinfo($file, PATHINFO_EXTENSION);

                        // 确定文件图标
                        $icon = 'fas fa-file';
                        $iconColor = 'text-secondary';

                        if ($isDir) {
                            $icon = 'fas fa-folder';
                            $iconColor = 'text-primary';
                        } elseif (in_array($fileExt, ['php', 'phar'])) {
                            $icon = 'fab fa-php';
                            $iconColor = 'text-info';
                        } elseif (in_array($fileExt, ['zip', 'gz', 'tar', 'bz2', 'xz'])) {
                            $icon = 'fas fa-file-archive';
                            $iconColor = 'text-warning';
                        } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                            $icon = 'fas fa-file-image';
                            $iconColor = 'text-success';
                        } elseif (in_array($fileExt, ['txt', 'md', 'log'])) {
                            $icon = 'fas fa-file-alt';
                            $iconColor = 'text-dark';
                        }
                    ?>
                    <div class="col-md-2 col-sm-3 col-4 mb-3">
                        <a href="/<?= $path ?>/<?= $file ?>" class="text-decoration-none">
                            <div class="card h-100">
                                <div class="card-body text-center py-3">
                                    <i class="<?= $icon ?> fa-2x <?= $iconColor ?> mb-2"></i>
                                    <p class="mb-0 text-truncate"><?= $file ?><?= $isDir ? '/' : '' ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
// 添加内联脚本
$inline_scripts = <<<JS
$(document).ready(function() {
    // 切换视图
    $('#toggleView').click(function(e) {
        e.preventDefault();
        $('#tableView').toggle();
        $('#gridView').toggle();

        var icon = $(this).find('i');
        if (icon.hasClass('fa-th-large')) {
            icon.removeClass('fa-th-large').addClass('fa-list');
        } else {
            icon.removeClass('fa-list').addClass('fa-th-large');
        }
    });

    // 显示/隐藏筛选面板
    $('#showFilter').click(function(e) {
        e.preventDefault();
        $('#filterPanel').toggle();
    });

    // 自动提交表单当选择变化时
    $('#version, #ext').change(function() {
        // 如果两个字段都为空且没有搜索词，则不提交
        if ($('#version').val() === '' && $('#ext').val() === '' && $('#search').val() === '') {
            return;
        }
        $(this).closest('form').submit();
    });
});
JS;
?>
