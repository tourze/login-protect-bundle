<?php

namespace Tourze\LoginProtectBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\LoginProtectBundle\Entity\LoginLog;

/**
 * 登录日志管理控制器
 */
#[AdminCrud(routePath: '/login-protect/login-log', routeName: 'login_protect_login_log')]
final class LoginLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LoginLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('登录日志')
            ->setEntityLabelInPlural('登录日志')
            ->setPageTitle('index', '登录日志列表')
            ->setPageTitle('detail', '登录日志详情')
            ->setHelp('index', '记录系统中所有用户的登录尝试，包括成功和失败的情况')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['identifier', 'action', 'createdFromIp', 'sessionId'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
        ;

        yield TextField::new('identifier', '用户标识')
            ->setHelp('登录用户的唯一标识符')
            ->hideOnForm()
        ;

        yield TextField::new('action', '登录结果')
            ->setHelp('登录操作的结果状态')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $this->formatActionStatus($value);
            })
        ;

        yield TextField::new('createdFromIp', '登录IP')
            ->setHelp('发起登录请求的IP地址')
            ->hideOnForm()
        ;

        yield TextField::new('sessionId', '会话ID')
            ->setHelp('登录会话的唯一标识')
            ->hideOnForm()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('登录尝试的时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('unlockTime', '解锁时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('账户解锁的时间，如果为空表示未被锁定')
            ->hideOnForm()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('identifier', '用户标识'))
            ->add(TextFilter::new('action', '登录结果'))
            ->add(TextFilter::new('createdFromIp', '登录IP'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('unlockTime', '解锁时间'))
        ;
    }

    private function formatActionStatus(?string $action): string
    {
        if (null === $action) {
            return '未知';
        }

        return match ($action) {
            'success' => '<span class="badge badge-success">登录成功</span>',
            'failed' => '<span class="badge badge-danger">登录失败</span>',
            'locked' => '<span class="badge badge-warning">账户锁定</span>',
            'blocked' => '<span class="badge badge-dark">IP封禁</span>',
            default => "<span class=\"badge badge-secondary\">{$action}</span>",
        };
    }
}
