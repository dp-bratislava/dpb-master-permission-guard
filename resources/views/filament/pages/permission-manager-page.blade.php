<x-filament::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>
    
    <x-filament::card>
        
        <div class="overflow-x-auto">
            <div class="rounded-xl border border-gray-200 shadow-sm">
                <table class="w-full table-auto text-sm">
                    <thead class="align-bottom">
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">ID</th>
                            <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">{{ __('dpb-mpg::translations.filament_page.table.headers.guard') }}</th>
                            @if(($this->filters['type'] ?? '') === 'mpg')
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">{{ __('dpb-mpg::translations.filament_page.table.headers.table') }}</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">{{ __('dpb-mpg::translations.filament_page.table.headers.action') }}</th>
                            @endif
                            <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">{{ __('dpb-mpg::translations.filament_page.table.headers.permission') }}</th>
                            <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">{{ __('dpb-mpg::translations.filament_page.table.headers.roles') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->permissions as $permission)
                            <tr class="align-middle">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $permission['id'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $permission['guard_name'] }}</td>
                                @if(($this->filters['type'] ?? '') === 'mpg')
                                    @php
                                        list($ext, $table, $action) = explode('.', $permission['name'], 3) + [null, null, null];
                                    @endphp
                                    <td class="px-4 py-3">{{ $table }}</td>
                                    <td class="px-4 py-3">{{ __("dpb-mpg::translations.permission_actions.$action") }}</td>
                                @endif
                                <td class="px-4 py-3">{{ $permission['name'] }}</td>
                                <td>
                                    {{ ($this->manageAssignedRolesAction)(['id' => $permission['id'], 'roles' => $permission['roles']]) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-filament::card>
</x-filament::page>