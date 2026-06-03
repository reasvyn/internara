<?php

declare(strict_types=1);

namespace App\Domain\Settings\Aggregates\Setting\Livewire\Forms;

use Livewire\Form;

class MailSettingsForm extends Form
{
    public string $mail_from_address = '';

    public string $mail_from_name = '';

    public string $mail_host = '';

    public string $mail_port = '587';

    public string $mail_encryption = 'tls';

    public string $mail_username = '';

    public string $mail_password = '';

    protected function rules(): array
    {
        return [
            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string|max:100',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|numeric',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'mail_from_address' => __('setting.fields.mail_from_address'),
            'mail_from_name' => __('setting.fields.mail_from_name'),
            'mail_host' => __('setting.fields.mail_host'),
            'mail_port' => __('setting.fields.mail_port'),
            'mail_encryption' => __('setting.fields.mail_encryption'),
            'mail_username' => __('setting.fields.mail_username'),
            'mail_password' => __('setting.fields.mail_password'),
        ];
    }

    /**
     * Get mail config array for TestMailSettingsAction.
     */
    public function toMailConfig(): array
    {
        return [
            'host' => $this->mail_host,
            'port' => $this->mail_port,
            'encryption' => $this->mail_encryption === 'none' ? null : $this->mail_encryption,
            'username' => $this->mail_username,
            'password' => $this->mail_password,
            'from_address' => $this->mail_from_address,
            'from_name' => $this->mail_from_name,
        ];
    }
}
