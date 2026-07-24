<?php

namespace Dpb\MasterPermissionGuard\Exceptions;

use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MissingPermissionException extends \RuntimeException
{
    public function __construct(
        private readonly string $operation,
        private readonly string $table
    ) {
        parent::__construct(
            __('dpb-mpg::exceptions.not_allowed', [
                'operation' => $operation,
                'table' => $table,
            ])
        );
    }

    public function render(Request $request): Response
    {
        Notification::make()
            ->body($this->getMessage())
            ->danger()
            ->send();

        $payload = [
            'error' => 'forbidden',
            'op' => $this->operation,
            'table' => $this->table,
        ];
        if ($request->expectsJson()) {
            return response()->json($payload, 403);
        }

        return response($this->getMessage().'<br>todo: treba lepší error handling!', 403);
        // return redirect()->back();
    }
}
