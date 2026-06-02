<?php

declare(strict_types=1);

use App\Domain\Settings\Livewire\Forms\MailSettingsForm;
use Livewire\Component;

beforeEach(function () {
    $component = new class extends Component
    {
        public function render(): string
        {
            return '';
        }
    };
    $this->form = new MailSettingsForm($component, 'mailForm');
});

describe('MailSettingsForm', function () {
    describe('toMailConfig', function () {
        it('returns mail config array', function () {
            $this->form->mail_host = 'smtp.example.com';
            $this->form->mail_port = '587';
            $this->form->mail_encryption = 'tls';
            $this->form->mail_username = 'user';
            $this->form->mail_password = 'pass';
            $this->form->mail_from_address = 'test@example.com';
            $this->form->mail_from_name = 'Test';

            $config = $this->form->toMailConfig();

            expect($config['host'])->toBe('smtp.example.com');
            expect($config['port'])->toBe('587');
            expect($config['encryption'])->toBe('tls');
            expect($config['username'])->toBe('user');
            expect($config['password'])->toBe('pass');
            expect($config['from_address'])->toBe('test@example.com');
            expect($config['from_name'])->toBe('Test');
        });

        it('converts none encryption to null', function () {
            $this->form->mail_encryption = 'none';

            $config = $this->form->toMailConfig();

            expect($config['encryption'])->toBeNull();
        });

        it('has default values', function () {
            $config = $this->form->toMailConfig();

            expect($config['port'])->toBe('587');
            expect($config['encryption'])->toBe('tls');
        });
    });
});
