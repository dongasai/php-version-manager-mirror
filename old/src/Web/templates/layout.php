<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= isset($title) ? $title : 'PVM 下载站' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.3/css/all.min.css">
    <style>
        body {
            padding-top: 56px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
        }
        .footer {
            padding: 20px 0;
            margin-top: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        .sidebar {
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            padding-top: 20px;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #f8f9fa;
            border-right: 1px solid #e9ecef;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #007bff;
        }
        .sidebar .nav-link:hover {
            color: #0056b3;
        }
        .sidebar .nav-link i {
            margin-right: 5px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.075);
        }
        .version-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            margin-right: 5px;
        }
        .breadcrumb {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .progress {
            height: 5px;
            margin-bottom: 10px;
        }
        .progress-thin {
            height: 2px;
        }
        .stats-card {
            text-align: center;
            padding: 15px;
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #007bff;
        }
        .stats-card .stats-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stats-card .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">PVM 下载站</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item <?= $active_page === 'home' ? 'active' : '' ?>">
                        <a class="nav-link" href="/"><i class="fas fa-home"></i> 首页</a>
                    </li>
                    <li class="nav-item <?= $active_page === 'php' ? 'active' : '' ?>">
                        <a class="nav-link" href="/php/"><i class="fab fa-php"></i> PHP</a>
                    </li>
                    <li class="nav-item <?= $active_page === 'pecl' ? 'active' : '' ?>">
                        <a class="nav-link" href="/pecl/"><i class="fas fa-puzzle-piece"></i> PECL</a>
                    </li>
                    <li class="nav-item <?= $active_page === 'extensions' ? 'active' : '' ?>">
                        <a class="nav-link" href="/extensions/"><i class="fas fa-plug"></i> 扩展</a>
                    </li>
                    <li class="nav-item <?= $active_page === 'composer' ? 'active' : '' ?>">
                        <a class="nav-link" href="/composer/"><i class="fas fa-box"></i> Composer</a>
                    </li>
                    <li class="nav-item <?= $active_page === 'status' ? 'active' : '' ?>">
                        <a class="nav-link" href="/status/"><i class="fas fa-chart-line"></i> 状态</a>
                    </li>
                    <li class="nav-item <?= $active_page === 'docs' ? 'active' : '' ?>">
                        <a class="nav-link" href="/docs/"><i class="fas fa-book"></i> 文档</a>
                    </li>
                </ul>
                <form class="form-inline my-2 my-lg-0" action="/search/" method="get">
                    <input class="form-control mr-sm-2" type="search" name="q" placeholder="搜索" aria-label="搜索">
                    <button class="btn btn-outline-light my-2 my-sm-0" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
    </nav>

    <!-- 内容区域 -->
    <div class="content">
        <?php if (isset($use_container) && $use_container): ?>
        <div class="container py-4">
            <?php if (isset($show_breadcrumb) && $show_breadcrumb): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> 首页</a></li>
                    <?php if (isset($breadcrumbs)): ?>
                        <?php foreach ($breadcrumbs as $crumb): ?>
                            <?php if ($crumb === end($breadcrumbs)): ?>
                                <li class="breadcrumb-item active" aria-current="page"><?= $crumb['name'] ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item"><a href="<?= $crumb['path'] ?>"><?= $crumb['name'] ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </nav>
            <?php endif; ?>
            
            <?php if (isset($page_title)): ?>
            <h1 class="mb-4"><?= $page_title ?></h1>
            <?php endif; ?>
            
            <?= $content ?>
        </div>
        <?php else: ?>
            <?= $content ?>
        <?php endif; ?>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?= date('Y') ?> PVM 镜像应用</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p>
                        <a href="https://github.com/dongasai/php-version-manager" target="_blank"><i class="fab fa-github"></i> GitHub</a> |
                        <a href="/docs/"><i class="fas fa-book"></i> 文档</a> |
                        <a href="/status/"><i class="fas fa-chart-line"></i> 状态</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?= $inline_scripts ?>
        </script>
    <?php endif; ?>
</body>
</html>
