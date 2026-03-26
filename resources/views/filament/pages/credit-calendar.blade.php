<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Stats de vencimientos por venta (no por cliente) --}}
        @php
            $vencidas  = \App\Models\Sale::where('payment_method', 'credito')
                ->where('status', 'completed')
                ->whereNotNull('credit_due_date')
                ->where('credit_due_date', '<', now()->toDateString())
                ->count();
            $porVencer = \App\Models\Sale::where('payment_method', 'credito')
                ->where('status', 'completed')
                ->whereNotNull('credit_due_date')
                ->whereBetween('credit_due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count();
            $vigentes  = \App\Models\Sale::where('payment_method', 'credito')
                ->where('status', 'completed')
                ->whereNotNull('credit_due_date')
                ->where('credit_due_date', '>', now()->addDays(7)->toDateString())
                ->count();
            $sinFecha  = \App\Models\Sale::where('payment_method', 'credito')
                ->where('status', 'completed')
                ->whereNull('credit_due_date')
                ->count();

            $totalDeuda = \App\Models\Sale::where('payment_method', 'credito')
                ->where('status', 'completed')
                ->sum('total')
                - \App\Models\CustomerPayment::whereHas('sale', fn ($q) => $q->where('payment_method', 'credito')->where('status', 'completed'))
                ->sum('amount');
        @endphp

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl bg-danger-50 dark:bg-danger-400/10 border border-danger-200 dark:border-danger-800 p-4 text-center">
                <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">{{ $vencidas }}</div>
                <div class="text-sm font-medium text-danger-600 dark:text-danger-400 mt-1">Ventas Vencidas</div>
            </div>
            <div class="rounded-xl bg-warning-50 dark:bg-warning-400/10 border border-warning-200 dark:border-warning-800 p-4 text-center">
                <div class="text-3xl font-bold text-warning-600 dark:text-warning-400">{{ $porVencer }}</div>
                <div class="text-sm font-medium text-warning-600 dark:text-warning-400 mt-1">Por vencer (7 días)</div>
            </div>
            <div class="rounded-xl bg-success-50 dark:bg-success-400/10 border border-success-200 dark:border-success-800 p-4 text-center">
                <div class="text-3xl font-bold text-success-600 dark:text-success-400">{{ $vigentes }}</div>
                <div class="text-sm font-medium text-success-600 dark:text-success-400 mt-1">Vigentes</div>
            </div>
            <div class="rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ number_format(max(0, $totalDeuda), 0, ',', '.') }}</div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Saldo Total (Gs)</div>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
