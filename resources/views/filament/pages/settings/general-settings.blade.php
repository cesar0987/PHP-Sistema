<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <x-filament::section icon="heroicon-o-building-storefront">
            <x-slot name="heading">
                Datos del Sistema
            </x-slot>

            <div class="flex flex-col gap-4">
                <div>
                    <span class="text-sm font-medium text-gray-500">Nombre de la Empresa</span>
                    <p class="text-base text-gray-900 dark:text-gray-100">{{ config('app.name') }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Entorno</span>
                    <p class="text-base text-gray-900 dark:text-gray-100">{{ env('APP_ENV', 'produccion') }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-server">
            <x-slot name="heading">
                Acerca del Servidor
            </x-slot>

            <div class="flex flex-col gap-4">
                <div>
                    <span class="text-sm font-medium text-gray-500">Versión PHP</span>
                    <p class="text-base text-gray-900 dark:text-gray-100">{{ phpversion() }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Versión Laravel</span>
                    <p class="text-base text-gray-900 dark:text-gray-100">{{ app()->version() }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Hora local del servidor</span>
                    <p class="text-base text-gray-900 dark:text-gray-100">{{ now()->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
