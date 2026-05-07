<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\Facades\Request;

/**
 * Smart dual-channel logger for system and user activity logs.
 *
 * Fluent entry points by message face: success, info, warning, error.
 * Developers can target system logs, activity logs, or both.
 *
 * Usage:
 *   Logger::success('User registered')->for($user)->save();
 *   Logger::warning('Disk space low')->systemOnly()->save();
 *   Logger::info('Profile updated')->for($user)->about($profile)->save();
 *   Logger::error('Payment failed', ['txn' => 'abc'])->activityOnly()->save();
 */
final class Logger
{
    private const FACE_MAP = [
        'success' => 'info',
        'info' => 'info',
        'warning' => 'warning',
        'error' => 'error',
    ];

    private string $face;

    private string $message;

    private array $context;

    private ?Model $causer = null;

    private ?Model $subject = null;

    private array $payload = [];

    private ?string $module = null;

    private ?string $event = null;

    private ?string $channel = null;

    private bool $toSystem = true;

    private bool $toActivity = true;

    private function __construct(string $face, string $message, array $context = [])
    {
        $this->face = $face;
        $this->message = $message;
        $this->context = $context;
    }

    public static function success(string $message, array $context = []): self
    {
        return new self('success', $message, $context);
    }

    public static function info(string $message, array $context = []): self
    {
        return new self('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): self
    {
        return new self('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): self
    {
        return new self('error', $message, $context);
    }

    public function for(?Model $user): self
    {
        $this->causer = $user;

        return $this;
    }

    public function about(?Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function withPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function module(string $name): self
    {
        $this->module = $name;

        return $this;
    }

    public function event(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function channel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function systemOnly(): self
    {
        $this->toSystem = true;
        $this->toActivity = false;

        return $this;
    }

    public function activityOnly(): self
    {
        $this->toSystem = false;
        $this->toActivity = true;

        return $this;
    }

    public function both(): self
    {
        $this->toSystem = true;
        $this->toActivity = true;

        return $this;
    }

    public function save(): void
    {
        $causer = $this->causer ?? Auth::user();

        if ($causer === null && $this->toActivity) {
            $this->toActivity = false;
        }

        if ($this->toSystem) {
            $this->writeSystemLog($causer);
        }

        if ($this->toActivity && $causer !== null) {
            $this->writeActivityLog($causer);
        }
    }

    private function writeSystemLog(?Model $causer): void
    {
        $level = self::FACE_MAP[$this->face] ?? 'info';

        $context = $this->context;

        if ($this->payload !== []) {
            $context['payload'] = $this->payload;
        }

        if ($this->module !== null) {
            $context['module'] = $this->module;
        }

        if ($this->channel !== null) {
            $context['channel'] = $this->channel;
        }

        if ($causer !== null) {
            $context['user_id'] = $causer->getKey();
        }

        LogFacade::{$level}($this->message, $context);
    }

    private function writeActivityLog(Model $causer): void
    {
        try {
            $activity = activity()
                ->causedBy($causer)
                ->event($this->event ?? $this->face)
                ->withProperties(array_filter([
                    'payload' => $this->payload !== [] ? $this->payload : null,
                    'ip_address' => Request::ip(),
                    'user_agent' => Request::userAgent(),
                ]));

            if ($this->module !== null) {
                $activity->useLog($this->module);
            }

            if ($this->subject !== null) {
                $activity->performedOn($this->subject);
            }

            $activity->log($this->message);
        } catch (\Throwable $e) {
            LogFacade::error('Failed to write activity log', [
                'face' => $this->face,
                'message' => $this->message,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
