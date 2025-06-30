<!-- 统计卡片 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <i class="fas fa-file"></i>
            <div class="stats-value"><?= number_format($status['total_files']) ?></div>
            <div class="stats-label">总文件数</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <i class="fas fa-hdd"></i>
            <div class="stats-value"><?= $formatSize($status['total_size']) ?></div>
            <div class="stats-label">总大小</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <i class="fas fa-sync"></i>
            <div class="stats-value"><?= date('Y-m-d', $status['last_update']) ?></div>
            <div class="stats-label">最后更新</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <i class="fas fa-clock"></i>
            <div class="stats-value"><?= date('H:i:s', $status['last_update']) ?></div>
            <div class="stats-label">更新时间</div>
        </div>
    </div>
</div>

<!-- PHP 源码包 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fab fa-php"></i> PHP 源码包</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            $phpVersions = isset($config['php']['versions']) ? $config['php']['versions'] : [];
            if (is_array($phpVersions)):
                foreach ($phpVersions as $majorVersion => $versionRange): ?>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <a href="/php/?version=<?= htmlspecialchars($majorVersion) ?>" class="btn btn-outline-primary btn-block">
                        PHP <?= htmlspecialchars($majorVersion) ?>
                    </a>
                </div>
            <?php
                endforeach;
            endif; ?>
        </div>
        <a href="/php/" class="btn btn-sm btn-info mt-2">
            <i class="fas fa-list"></i> 浏览所有 PHP 源码包
        </a>
    </div>
</div>

<!-- PECL 扩展包 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-puzzle-piece"></i> PECL 扩展包</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            $peclExtensions = isset($config['pecl']['extensions']) ? $config['pecl']['extensions'] : [];
            if (is_array($peclExtensions)):
                $count = 0;
                foreach ($peclExtensions as $extension => $versionRange):
                    if ($count++ < 12): // 只显示前12个扩展
            ?>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <a href="/pecl/?extension=<?= htmlspecialchars($extension) ?>" class="btn btn-outline-success btn-block">
                        <?= htmlspecialchars($extension) ?>
                    </a>
                </div>
            <?php
                    endif;
                endforeach;
            endif;
            ?>
        </div>
        <?php if (is_array($peclExtensions) && count($peclExtensions) > 12): ?>
            <button class="btn btn-sm btn-secondary mt-2" id="showMorePecl">
                <i class="fas fa-plus"></i> 显示更多
            </button>
        <?php endif; ?>
        <a href="/pecl/" class="btn btn-sm btn-info mt-2 ml-2">
            <i class="fas fa-list"></i> 浏览所有 PECL 扩展包
        </a>
    </div>
</div>

<!-- 特定扩展源码 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plug"></i> 特定扩展源码</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            $extensions = isset($config['extensions']) ? $config['extensions'] : [];
            if (is_array($extensions)):
                foreach ($extensions as $extension => $extConfig): ?>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <a href="/extensions/<?= htmlspecialchars($extension) ?>/" class="btn btn-outline-danger btn-block">
                        <?= htmlspecialchars($extension) ?>
                    </a>
                </div>
            <?php
                endforeach;
            endif; ?>
        </div>
        <a href="/extensions/" class="btn btn-sm btn-info mt-2">
            <i class="fas fa-list"></i> 浏览所有特定扩展源码
        </a>
    </div>
</div>

<!-- Composer 包 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-box"></i> Composer 包</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php
            $composerVersions = isset($config['composer']['versions']) ? $config['composer']['versions'] : [];
            if (is_array($composerVersions)):
                foreach ($composerVersions as $version): ?>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <a href="/composer/composer-<?= htmlspecialchars($version) ?>.phar" class="btn btn-outline-dark btn-block">
                        <?= htmlspecialchars($version) ?>
                    </a>
                </div>
            <?php
                endforeach;
            endif; ?>
        </div>
        <a href="/composer/" class="btn btn-sm btn-info mt-2">
            <i class="fas fa-list"></i> 浏览所有 Composer 包
        </a>
    </div>
</div>

<!-- API 接口 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-code"></i> API 接口</h5>
    </div>
    <div class="card-body">
        <div class="list-group">
            <a href="/api/status.json" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                镜像状态
                <span class="badge badge-primary badge-pill"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="/api/php.json" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                PHP 源码包列表
                <span class="badge badge-primary badge-pill"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="/api/pecl.json" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                PECL 扩展包列表
                <span class="badge badge-primary badge-pill"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="/api/extensions.json" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                特定扩展源码列表
                <span class="badge badge-primary badge-pill"><i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="/api/composer.json" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                Composer 包列表
                <span class="badge badge-primary badge-pill"><i class="fas fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
</div>

<!-- 使用说明 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> 使用说明</h5>
    </div>
    <div class="card-body">
        <p>在 PVM 配置文件中添加以下镜像配置：</p>
        <pre class="bg-light p-3 rounded"><code>// 编辑 ~/.pvm/config/mirrors.php
return [
    'php' => [
        'official' => 'https://www.php.net/distributions',
        'mirrors' => [
            'local' => '<?= htmlspecialchars(isset($config['server']['public_url']) ? $config['server']['public_url'] : 'http://localhost:34403') ?>/php',
        ],
        'default' => 'local',
    ],
    // 其他配置...
];</code></pre>
    </div>
</div>

<?php
// 添加内联脚本
$inline_scripts = <<<JS
$(document).ready(function() {
    // 显示更多PECL扩展
    $('#showMorePecl').click(function() {
        $('.pecl-hidden').removeClass('d-none');
        $(this).hide();
    });
});
JS;
?>
