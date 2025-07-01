<?php

namespace Mirror\Command;

/**
 * 命令接口
 */
interface CommandInterface
{
    /**
     * 获取命令名称
     *
     * @return string
     */
    public function getName();

    /**
     * 获取命令描述
     *
     * @return string
     */
    public function getDescription();

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = []);
}
