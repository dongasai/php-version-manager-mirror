<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Models\Role;
use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Menu;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建管理员用户
        $admin = Administrator::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'avatar' => '',
            ]
        );

        // 获取超级管理员角色
        $role = Role::where('slug', 'administrator')->first();
        if ($role && !$admin->roles->contains($role->id)) {
            $admin->roles()->attach($role->id);
        }

        // 创建队列管理菜单
        $this->createQueueMenus();
    }

    /**
     * 创建队列管理菜单
     */
    protected function createQueueMenus()
    {
        // 创建队列管理父菜单
        $queueMenu = Menu::firstOrCreate(
            ['title' => '队列管理'],
            [
                'parent_id' => 0,
                'order' => 50,
                'icon' => 'fa-tasks',
                'uri' => '',
                'extension' => '',
                'show' => 1,
            ]
        );

        // 创建队列仪表板子菜单
        Menu::firstOrCreate(
            ['title' => '队列仪表板'],
            [
                'parent_id' => $queueMenu->id,
                'order' => 0,
                'icon' => 'fa-dashboard',
                'uri' => 'queue-dashboard',
                'extension' => '',
                'show' => 1,
            ]
        );

        // 创建队列任务子菜单
        Menu::firstOrCreate(
            ['title' => '队列任务'],
            [
                'parent_id' => $queueMenu->id,
                'order' => 1,
                'icon' => 'fa-list',
                'uri' => 'queue-jobs',
                'extension' => '',
                'show' => 1,
            ]
        );

        // 创建失败任务子菜单
        Menu::firstOrCreate(
            ['title' => '失败任务'],
            [
                'parent_id' => $queueMenu->id,
                'order' => 2,
                'icon' => 'fa-exclamation-triangle',
                'uri' => 'failed-jobs',
                'extension' => '',
                'show' => 1,
            ]
        );

        // 创建同步任务子菜单
        Menu::firstOrCreate(
            ['title' => '同步任务'],
            [
                'parent_id' => $queueMenu->id,
                'order' => 3,
                'icon' => 'fa-sync',
                'uri' => 'sync-jobs',
                'extension' => '',
                'show' => 1,
            ]
        );

        // 创建系统管理父菜单
        $systemMenu = Menu::firstOrCreate(
            ['title' => '系统管理'],
            [
                'parent_id' => 0,
                'order' => 60,
                'icon' => 'fa-cogs',
                'uri' => '',
                'extension' => '',
                'show' => 1,
            ]
        );

        // 创建系统配置子菜单
        Menu::firstOrCreate(
            ['title' => '系统配置'],
            [
                'parent_id' => $systemMenu->id,
                'order' => 1,
                'icon' => 'fa-cog',
                'uri' => 'system-configs',
                'extension' => '',
                'show' => 1,
            ]
        );

        // 创建访问日志子菜单
        Menu::firstOrCreate(
            ['title' => '访问日志'],
            [
                'parent_id' => $systemMenu->id,
                'order' => 2,
                'icon' => 'fa-file-text',
                'uri' => 'access-logs',
                'extension' => '',
                'show' => 1,
            ]
        );
    }
}
