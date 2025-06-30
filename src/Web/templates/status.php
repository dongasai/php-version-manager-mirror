<!-- 状态概览 -->
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

<!-- 详细统计 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> 存储分布</h5>
            </div>
            <div class="card-body">
                <canvas id="storageChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> 文件数量</h5>
            </div>
            <div class="card-body">
                <canvas id="filesChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 同步状态 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-sync"></i> 同步状态</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>类型</th>
                        <th>文件数</th>
                        <th>大小</th>
                        <th>最后同步</th>
                        <th>状态</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fab fa-php"></i> PHP 源码包</td>
                        <td><?= number_format($status['php_files']) ?></td>
                        <td><?= $formatSize($status['php_size']) ?></td>
                        <td><?= date('Y-m-d H:i:s', $status['php_last_update']) ?></td>
                        <td>
                            <?php if (time() - $status['php_last_update'] < 86400): ?>
                                <span class="badge badge-success">正常</span>
                            <?php elseif (time() - $status['php_last_update'] < 259200): ?>
                                <span class="badge badge-warning">需要更新</span>
                            <?php else: ?>
                                <span class="badge badge-danger">过期</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-puzzle-piece"></i> PECL 扩展包</td>
                        <td><?= number_format($status['pecl_files']) ?></td>
                        <td><?= $formatSize($status['pecl_size']) ?></td>
                        <td><?= date('Y-m-d H:i:s', $status['pecl_last_update']) ?></td>
                        <td>
                            <?php if (time() - $status['pecl_last_update'] < 86400): ?>
                                <span class="badge badge-success">正常</span>
                            <?php elseif (time() - $status['pecl_last_update'] < 259200): ?>
                                <span class="badge badge-warning">需要更新</span>
                            <?php else: ?>
                                <span class="badge badge-danger">过期</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-plug"></i> 特定扩展源码</td>
                        <td><?= number_format($status['extension_files']) ?></td>
                        <td><?= $formatSize($status['extension_size']) ?></td>
                        <td><?= date('Y-m-d H:i:s', $status['extension_last_update']) ?></td>
                        <td>
                            <?php if (time() - $status['extension_last_update'] < 86400): ?>
                                <span class="badge badge-success">正常</span>
                            <?php elseif (time() - $status['extension_last_update'] < 259200): ?>
                                <span class="badge badge-warning">需要更新</span>
                            <?php else: ?>
                                <span class="badge badge-danger">过期</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-box"></i> Composer 包</td>
                        <td><?= number_format($status['composer_files']) ?></td>
                        <td><?= $formatSize($status['composer_size']) ?></td>
                        <td><?= date('Y-m-d H:i:s', $status['composer_last_update']) ?></td>
                        <td>
                            <?php if (time() - $status['composer_last_update'] < 86400): ?>
                                <span class="badge badge-success">正常</span>
                            <?php elseif (time() - $status['composer_last_update'] < 259200): ?>
                                <span class="badge badge-warning">需要更新</span>
                            <?php else: ?>
                                <span class="badge badge-danger">过期</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 系统状态 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-server"></i> 系统状态</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>CPU 使用率</h6>
                <div class="progress mb-3">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= $system['cpu_usage'] ?>%;" aria-valuenow="<?= $system['cpu_usage'] ?>" aria-valuemin="0" aria-valuemax="100"><?= $system['cpu_usage'] ?>%</div>
                </div>
                
                <h6>内存使用率</h6>
                <div class="progress mb-3">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $system['memory_usage'] ?>%;" aria-valuenow="<?= $system['memory_usage'] ?>" aria-valuemin="0" aria-valuemax="100"><?= $system['memory_usage'] ?>%</div>
                </div>
                
                <h6>磁盘使用率</h6>
                <div class="progress mb-3">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $system['disk_usage'] ?>%;" aria-valuenow="<?= $system['disk_usage'] ?>" aria-valuemin="0" aria-valuemax="100"><?= $system['disk_usage'] ?>%</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th>主机名</th>
                                <td><?= $system['hostname'] ?></td>
                            </tr>
                            <tr>
                                <th>操作系统</th>
                                <td><?= $system['os'] ?></td>
                            </tr>
                            <tr>
                                <th>内核版本</th>
                                <td><?= $system['kernel'] ?></td>
                            </tr>
                            <tr>
                                <th>PHP 版本</th>
                                <td><?= $system['php_version'] ?></td>
                            </tr>
                            <tr>
                                <th>Web 服务器</th>
                                <td><?= $system['web_server'] ?></td>
                            </tr>
                            <tr>
                                <th>运行时间</th>
                                <td><?= $system['uptime'] ?></td>
                            </tr>
                            <tr>
                                <th>负载</th>
                                <td><?= $system['load'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加Chart.js库 -->
<?php $scripts = ['https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js']; ?>

<!-- 添加图表脚本 -->
<?php
$inline_scripts = <<<JS
$(document).ready(function() {
    // 存储分布图表
    var storageCtx = document.getElementById('storageChart').getContext('2d');
    var storageChart = new Chart(storageCtx, {
        type: 'pie',
        data: {
            labels: ['PHP 源码包', 'PECL 扩展包', '特定扩展源码', 'Composer 包'],
            datasets: [{
                data: [{$status['php_size']}, {$status['pecl_size']}, {$status['extension_size']}, {$status['composer_size']}],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            legend: {
                position: 'right',
            },
            title: {
                display: true,
                text: '存储空间分布'
            }
        }
    });
    
    // 文件数量图表
    var filesCtx = document.getElementById('filesChart').getContext('2d');
    var filesChart = new Chart(filesCtx, {
        type: 'bar',
        data: {
            labels: ['PHP 源码包', 'PECL 扩展包', '特定扩展源码', 'Composer 包'],
            datasets: [{
                label: '文件数量',
                data: [{$status['php_files']}, {$status['pecl_files']}, {$status['extension_files']}, {$status['composer_files']}],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            },
            title: {
                display: true,
                text: '文件数量分布'
            }
        }
    });
});
JS;
?>
