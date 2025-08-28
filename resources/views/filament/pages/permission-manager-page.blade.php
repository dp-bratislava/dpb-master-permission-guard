<x-filament::page>
    {{ $this->form }}
    
    <x-filament::card>
        <table>
            <thead>
                <tr>
                    <th>{{ __('dpb-mpg::translations.filament_page.table.headers.guard') }}</th>
                    <th>{{ __('dpb-mpg::translations.filament_page.table.headers.package') }}</th>
                    <th>{{ __('dpb-mpg::translations.filament_page.table.headers.table') }}</th>
                    <th>{{ __('dpb-mpg::translations.filament_page.table.headers.permission') }}</th>
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
    </x-filament::card>
</x-filament::page>