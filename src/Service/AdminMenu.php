<?php

namespace Tourze\LoginProtectBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\LoginProtectBundle\Entity\LoginLog;

/**
 * 登录保护菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('安全管理')) {
            $item->addChild('安全管理')
                ->setAttribute('icon', 'fas fa-shield-alt')
            ;
        }

        $securityMenu = $item->getChild('安全管理');
        if (null === $securityMenu) {
            return;
        }

        $securityMenu->addChild('登录日志')
            ->setUri($this->linkGenerator->getCurdListPage(LoginLog::class))
            ->setAttribute('icon', 'fas fa-history')
        ;
    }
}
