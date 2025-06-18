<?php

namespace Tourze\LoginProtectBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
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

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[IndexColumn]
    #[ORM\Column(length: 120, options: ['comment' => '唯一标志'])]
    private ?string $identifier = null;

    #[IndexColumn]
    #[ORM\Column(length: 20, options: ['comment' => '登录结果'])]
    private ?string $action = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '解锁时间'])]
    private ?\DateTimeImmutable $unlockTime = null;

    #[IndexColumn]
    #[ORM\Column(length: 100, options: ['comment' => '会话ID', 'default' => ''])]
    private string $sessionId = '';

    #[CreateIpColumn]
    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '创建时IP'])]
    private ?string $createdFromIp = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getUnlockTime(): ?\DateTimeImmutable
    {
        return $this->unlockTime;
    }

    public function setUnlockTime(?\DateTimeInterface $unlockTime): self
    {
        $this->unlockTime = $unlockTime instanceof \DateTimeImmutable ? $unlockTime : ($unlockTime !== null ? \DateTimeImmutable::createFromInterface($unlockTime) : null);

        return $this;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function setCreatedFromIp(?string $createdFromIp): void
    {
        $this->createdFromIp = $createdFromIp;
    }
    
    public function __toString(): string
    {
        return sprintf('LoginLog #%s - %s (%s)', $this->id ?? 'new', $this->identifier ?? 'unknown', $this->action ?? 'unknown');
    }
}
