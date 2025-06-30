<?php

namespace Mirror\Web;

/**
 * 视图类
 */
class View
{
    /**
     * 模板目录
     *
     * @var string
     */
    private $templateDir;

    /**
     * 布局模板
     *
     * @var string|null
     */
    private $layout = null;

    /**
     * 活动页面
     *
     * @var string
     */
    private $activePage = 'home';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->templateDir = ROOT_DIR . '/srcMirror/Web/templates';
    }

    /**
     * 设置布局模板
     *
     * @param string|null $layout 布局模板名称，null表示不使用布局
     * @return self
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * 设置活动页面
     *
     * @param string $page 页面标识
     * @return self
     */
    public function setActivePage($page)
    {
        $this->activePage = $page;
        return $this;
    }

    /**
     * 渲染模板
     *
     * @param string $template 模板名称
     * @param array $data 模板数据
     */
    public function render($template, array $data = [])
    {
        // 检查模板文件是否存在
        $templateFile = $this->templateDir . '/' . $template . '.php';

        if (!file_exists($templateFile)) {
            throw new \Exception("模板文件不存在: $templateFile");
        }

        // 添加活动页面到数据
        $data['active_page'] = $this->activePage;

        // 提取变量
        extract($data);

        // 开始输出缓冲
        ob_start();

        // 包含模板文件
        include $templateFile;

        // 获取缓冲内容并结束缓冲
        $content = ob_get_clean();

        // 如果设置了布局，则使用布局模板
        if ($this->layout !== null) {
            $layoutFile = $this->templateDir . '/' . $this->layout . '.php';

            if (!file_exists($layoutFile)) {
                throw new \Exception("布局模板文件不存在: $layoutFile");
            }

            // 将内容传递给布局模板
            $data['content'] = $content;
            $data['active_page'] = $this->activePage;

            // 提取变量
            extract($data);

            // 开始输出缓冲
            ob_start();

            // 包含布局模板文件
            include $layoutFile;

            // 获取缓冲内容并结束缓冲
            $content = ob_get_clean();
        }

        // 输出内容
        echo $content;
    }
}
