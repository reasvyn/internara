<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Events\BaseEvent;
use App\Support\PiiMasker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LogFacade;
use Illuminate\Support\Facades\Request;

final class SmartLogger
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

    private string|BaseEvent|null $event = null;

    private ?string $channel = null;

    private bool $toSystem = true;

    private bool $toActivity = true;

    private bool $maskPii = false;

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

    public function event(string|BaseEvent $event): self
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

    public function withPiiMasking(): self
    {
        $this->maskPii = true;

        return $this;
    }

    public function save(): void
    {
        $eventName = $this->resolveEventName();

        $this->dispatchEvent();
        $this->applyPiiMasking();
        $this->resolveTranslations($eventName);

        $causer = $this->causer ?? Auth::user();

        if ($this->toSystem) {
            $this->writeSystemLog($causer, $eventName);
        }

        if ($this->shouldWriteActivityLog($causer)) {
            $this->writeActivityLog($causer, $eventName);
        }
    }

    private function shouldWriteActivityLog(?Model $causer): bool
    {
        if (! $this->toActivity) {
            return false;
        }

        return $causer !== null || $this->activityOnly();
    }

    private function dispatchEvent(): void
    {
        if ($this->event instanceof BaseEvent) {
            event($this->event);
            $this->payload = array_merge($this->event->toPayload(), $this->payload);
        }
    }

    private function applyPiiMasking(): void
    {
        if ($this->maskPii) {
            $this->payload = PiiMasker::maskArray($this->payload);
        }
    }

    private function resolveTranslations(?string $eventName): void
    {
        if ($eventName === null) {
            return;
        }

        $locale = App::getLocale();
        $description = __('log.'.$eventName, [], $locale);
        if ($description !== 'log.'.$eventName) {
            $this->context['event_description'] = $description;
        }
        $altLocale = $locale === 'id' ? 'en' : 'id';
        $altDescription = __('log.'.$eventName, [], $altLocale);
        if ($altDescription !== 'log.'.$eventName) {
            $this->context['event_description_'.$altLocale] = $altDescription;
        }
    }

    private function resolveEventName(): ?string
    {
        if ($this->event instanceof BaseEvent) {
            return $this->event->eventName();
        }

        return $this->event;
    }

    private function buildSystemContext(?Model $causer, ?string $eventName): array
    {
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

        return $context;
    }

    private function writeSystemLog(?Model $causer, ?string $eventName = null): void
    {
        $level = self::FACE_MAP[$this->face] ?? 'info';

        LogFacade::{$level}($this->message, $this->buildSystemContext($causer, $eventName));
    }

    private function writeActivityLog(?Model $causer = null, ?string $eventName = null): void
    {
        try {
            $activity = activity();

            if ($causer !== null) {
                $activity->causedBy($causer);
            }

            $ip = Request::ip();
            $ua = Request::userAgent();

            if ($this->maskPii) {
                $ip = $ip !== null ? PiiMasker::maskIp($ip) : null;
                $ua = $ua !== null ? PiiMasker::maskUserAgent($ua) : null;
            }

            $activity
                ->event($eventName ?? $this->face)
                ->withProperties(array_filter([
                    'payload' => $this->payload !== [] ? $this->payload : null,
                    'ip_address' => $ip,
                    'user_agent' => $ua,
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
                'module' => $this->module,
                'event' => $eventName,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
        }
    }
}
