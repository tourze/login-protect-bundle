<?php

namespace Tourze\LoginProtectBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\CreatedFromIpAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\LoginProtectBundle\Repository\LoginLogRepository;
use Tourze\ScheduleEntityCleanBundle\Attribute\AsScheduleClean;

/**
 * 登录日志比较重要，所以单独记录到数据库，方便后续审计
 */
#[AsScheduleClean(expression: '22 2 * * *', defaultKeepDay: 120, keepDayEnv: 'LOGIN_LOG_PERSIST_DAY_NUM')]
#[ORM\Entity(repositoryClass: LoginLogRepository::class, readOnly: true)]
#[ORM\Table(name: 'login_attempt', options: ['comment' => '登录日志'])]
class LoginLog implements \Stringable
{
    use CreateTimeAware;
    use SnowflakeKeyAware;
    use CreatedFromIpAware;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[IndexColumn]
    #[ORM\Column(length: 120, options: ['comment' => '唯一标志'])]
    private ?string $identifier = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[IndexColumn]
    #[ORM\Column(length: 20, options: ['comment' => '登录结果'])]
    private ?string $action = null;

    #[Assert\Type(type: \DateTimeImmutable::class)]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '解锁时间'])]
    private ?\DateTimeImmutable $unlockTime = null;

    #[Assert\Length(max: 100)]
    #[IndexColumn]
    #[ORM\Column(length: 100, options: ['comment' => '会话ID', 'default' => ''])]
    private string $sessionId = '';

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getUnlockTime(): ?\DateTimeImmutable
    {
        return $this->unlockTime;
    }

    public function setUnlockTime(?\DateTimeInterface $unlockTime): void
    {
        $this->unlockTime = $unlockTime instanceof \DateTimeImmutable ? $unlockTime : (null !== $unlockTime ? \DateTimeImmutable::createFromInterface($unlockTime) : null);
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function __toString(): string
    {
        return sprintf('LoginLog #%s - %s (%s)', $this->id ?? 'new', $this->identifier ?? 'unknown', $this->action ?? 'unknown');
    }

    public function setCreateTime(?\DateTimeInterface $createTime): void
    {
        $this->createTime = $createTime instanceof \DateTimeImmutable ? $createTime : (null !== $createTime ? \DateTimeImmutable::createFromInterface($createTime) : null);
    }
}
