<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
    </form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">{{ __('User role assignments') }}</x-slot>
        <x-slot name="description">
            {{ __('Assign Cachet roles to local and externally provisioned users. Admins bypass the matrix via is_admin.') }}
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
