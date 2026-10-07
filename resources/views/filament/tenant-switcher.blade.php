@if ($tenants->isNotEmpty())
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <x-filament::button
                color="gray"
                size="sm"
                icon="heroicon-o-building-office-2"
                icon-position="after"
            >
                {{ $tenants->firstWhere('id', $selectedTenantId)?->name }}
            </x-filament::button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($tenants as $tenant)
                <form
                    method="POST"
                    action="{{ route('tenant.switch') }}"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="tenant_id"
                        value="{{ $tenant->id }}"
                    >

                    <x-filament::dropdown.list.item
                        tag="button"
                        type="submit"
                        icon="{{ $tenant->id === $selectedTenantId
                            ? 'heroicon-m-check'
                            : 'heroicon-m-building-office-2' }}"
                    >
                        {{ $tenant->name }}
                    </x-filament::dropdown.list.item>
                </form>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@endif
