<x-filament-panels::page>
    <div class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl bg-danger-50 dark:bg-danger-400/10 p-4 text-center">
                <div class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                    {{ \App\Models\Customer::where('is_credit_enabled', true)->whereNotNull('credit_due_date')->where('credit_due_date', '<', now()->toDateString())->where('current_balance', '>', 0)->count() }}
                </div>
                <div class="text-sm text-danger-600 dark:text-danger-400">Clientes Vencidos</div>
            </div>
            <div class="rounded-xl bg-warning-50 dark:bg-warning-400/10 p-4 text-center">
                <div class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                    {{ \App\Models\Customer::where('is_credit_enabled', true)->whereNotNull('credit_due_date')->whereBetween('credit_due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])->where('current_balance', '>', 0)->count() }}
                </div>
                <div class="text-sm text-warning-600 dark:text-warning-400">Por vencer (7 días)</div>
            </div>
            <div class="rounded-xl bg-success-50 dark:bg-success-400/10 p-4 text-center">
                <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                    {{ \App\Models\Customer::where('is_credit_enabled', true)->whereNotNull('credit_due_date')->where('credit_due_date', '>', now()->addDays(7)->toDateString())->where('current_balance', '>', 0)->count() }}
                </div>
                <div class="text-sm text-success-600 dark:text-success-400">Vigentes</div>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
