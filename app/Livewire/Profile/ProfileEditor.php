<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Actions\Profile\UpdateProfileAction;
use App\Models\User;
use Livewire\Component;
use Mary\Traits\Toast;

class ProfileEditor extends Component
{
    use Toast;

    public User $user;
    
    public array $data = [];

    /**
     * Initialize the component.
     */
    public function mount()
    {
        $this->user = auth()->user();
        
        $profile = $this->user->profile;
        
        $this->data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $profile->phone ?? '',
            'address' => $profile->address ?? '',
            'bio' => $profile->bio ?? '',
        ];
    }

    /**
     * Save the profile changes.
     */
    public function save(UpdateProfileAction $updateProfile)
    {
        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.email' => 'required|email|unique:users,email,' . $this->user->id,
            'data.phone' => 'nullable|string',
            'data.address' => 'nullable|string',
            'data.bio' => 'nullable|string|max:1000',
        ]);

        $this->user->update([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
        ]);

        $updateProfile->execute($this->user, [
            'phone' => $this->data['phone'],
            'address' => $this->data['address'],
            'bio' => $this->data['bio'],
        ]);

        $this->success('Profile updated successfully.');
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header title="Your Profile" subtitle="Manage your account information" separator />
            
            <x-mary-form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-input label="Full Name" wire:model="data.name" icon="o-user" />
                    <x-mary-input label="Email Address" wire:model="data.email" icon="o-envelope" />
                    <x-mary-input label="Phone Number" wire:model="data.phone" icon="o-phone" />
                    <x-mary-textarea label="Bio" wire:model="data.bio" placeholder="Tell us about yourself..." class="md:col-span-2" />
                    <x-mary-textarea label="Address" wire:model="data.address" class="md:col-span-2" />
                </div>
                
                <x-slot:actions>
                    <x-mary-button label="Save Changes" type="submit" icon="o-check" class="btn-primary" />
                </x-slot:actions>
            </x-mary-form>
        </div>
        HTML;
    }
}
