<?php

declare(strict_types=1);

use App\Domain\Settings\Livewire\Forms\BrandingForm;
use Livewire\Component;

beforeEach(function () {
    $component = new class extends Component
    {
        public function render(): string
        {
            return '';
        }
    };
    $this->form = new BrandingForm($component, 'brandingForm');
});

describe('BrandingForm', function () {
    describe('detectPreset', function () {
        it('returns matching preset key when colors match', function () {
            $this->form->primary_color = '#059669';
            $this->form->secondary_color = '#6b7280';
            $this->form->accent_color = '#f97316';
            $this->form->base_color = '#ffffff';

            expect($this->form->detectPreset())->toBe('emerald');
        });

        it('returns null when no preset matches', function () {
            $this->form->primary_color = '#000000';
            $this->form->secondary_color = '#000000';
            $this->form->accent_color = '#000000';
            $this->form->base_color = '#000000';

            expect($this->form->detectPreset())->toBeNull();
        });

        it('returns null when colors are empty', function () {
            expect($this->form->detectPreset())->toBeNull();
        });
    });

    describe('applyPreset', function () {
        it('sets colors from a valid preset', function () {
            $this->form->applyPreset('ocean');

            expect($this->form->primary_color)->toBe('#0891b2');
            expect($this->form->secondary_color)->toBe('#64748b');
            expect($this->form->accent_color)->toBe('#7c3aed');
            expect($this->form->base_color)->toBe('#ffffff');
            expect($this->form->selected_preset)->toBe('ocean');
        });

        it('does nothing for unknown preset key', function () {
            $this->form->applyPreset('nonexistent');

            expect($this->form->primary_color)->toBe('');
            expect($this->form->selected_preset)->toBeNull();
        });
    });

    describe('brandLogoPreviewUrl', function () {
        it('returns null when no logo is set', function () {
            expect($this->form->brandLogoPreviewUrl())->toBeNull();
        });
    });

    describe('faviconPreviewUrl', function () {
        it('returns null when no favicon is set', function () {
            expect($this->form->faviconPreviewUrl())->toBeNull();
        });
    });

    describe('getPresets', function () {
        it('returns all presets from Theme', function () {
            $presets = $this->form->getPresets();

            expect($presets)->toHaveKeys(['sky', 'emerald', 'violet', 'rose', 'ocean', 'slate']);
        });
    });
});
