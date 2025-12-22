<x-filament-panels::page>
    <x-filament-panels::form wire:submit="updateProfile">
        {{ $this->profileForm }}
        
        <div class="mt-6">
            <x-filament::button type="submit">
                Update Profile
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <x-filament-panels::form wire:submit="updatePassword" class="mt-8">
        {{ $this->passwordForm }}
        
        <div class="mt-6">
            <x-filament::button type="submit">
                Update Password
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
