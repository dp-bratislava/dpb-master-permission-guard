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
                            <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">Guard</th>
                            <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs">Permission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->permissions as $permission)
                            <tr class="align-middle">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $permission['id'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $permission['guard_name'] }}</td>
                                <td class="px-4 py-3">{{ $permission['name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>




{{--
        <table>
            <thead>
                <tr>
                    <th>
                        ID
                    </th>
                    @if ($fiters['type'] === 'mpg')
                        <th>
                            {{ __('dpb-mpg::translations.filament_page.table.headers.table') }}
                        </th>
                    @else
                        
                    @endif
                    <th>
                        {{ __('dpb-mpg::translations.filament_page.table.headers.guard') }}
                    </th>
                    <th>
                        {{ __('dpb-mpg::translations.filament_page.table.headers.package') }}
                    </th>
                    <th>
                        {{ __('dpb-mpg::translations.filament_page.table.headers.permission') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->permissions as $guard => $packages)
                    @foreach ($packages as $package => $permissions)
                        @if ($package === 'dpb-mpg')
                            @foreach ($permissions as $table => $operations)
                                @foreach ($operations as $operation)
                                    <tr>
                                        <td>{{ $guard }}</td>
                                        <td>{{ $package }}</td>
                                        <td>{{ $table }}</td>
                                        <td>{{ $operation['name'] ?? 'not set' }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @else
                            @foreach ($permissions as $permission)
                                <tr>
                                    <td>{{ $guard }}</td>
                                    <td>{{ $package }}</td>
                                    <td></td>
                                    <td>{{ $permission['name'] ?? 'not set' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                @endforeach
            </tbody>
        </table>
--}}
    </x-filament::card>
</x-filament::page>