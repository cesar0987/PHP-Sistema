<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static int $defaultTableRecordsPerPage = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del usuario')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre completo')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo electronico')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->revealable()
                                    ->required(fn ($operation) => $operation === 'create')
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->helperText(fn ($operation) => $operation === 'edit' ? 'Dejar vacio para no cambiar' : null),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Confirmar contraseña')
                                    ->password()
                                    ->revealable()
                                    ->same('password')
                                    ->required(fn ($operation) => $operation === 'create')
                                    ->dehydrated(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Rol del usuario')
                    ->description('Seleccione el rol principal. Los permisos se asignan automáticamente según el rol definido en el seeder.')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rol')
                            ->options(
                                \Spatie\Permission\Models\Role::pluck('name', 'name')
                                    ->mapWithKeys(fn ($name) => [$name => match ($name) {
                                        'admin' => 'Admin — Acceso total al sistema',
                                        'supervisor' => 'Supervisor — Supervisa sin acceso admin',
                                        'vendedor' => 'Vendedor — Opera ventas y caja',
                                        'almacenero' => 'Almacenero — Gestiona stock y ubicaciones',
                                        'cobrador' => 'Cobrador — Gestiona cobros de créditos',
                                        default => ucfirst($name),
                                    }])
                            )
                            ->required()
                            ->native(false)
                            ->live() // Hacer reactivo para actualizar los permisos mostrados
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->getRoleNames()->first());
                                }
                            })
                            ->dehydrated(false),
                            
                        Forms\Components\Placeholder::make('role_permissions')
                            ->label('Permisos asignados a este rol')
                            ->content(function (Forms\Get $get) {
                                $roleName = $get('role');
                                if (! $roleName) {
                                    return '-';
                                }
                                
                                /** @var \Spatie\Permission\Models\Role|null $role */
                                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->where('guard_name', 'web')->first();
                                
                                if (! $role) {
                                    return 'Este rol no tiene permisos específicos.';
                                }
                                $role->load('permissions');
                                
                                if ($role->permissions->isEmpty()) {
                                    return 'Este rol no tiene permisos específicos.';
                                }
                                
                                $badges = $role->permissions->pluck('name')->map(function ($p) {
                                    $label = ucfirst(str_replace('_', ' ', $p));
                                    // Usando clases tailwind de Filament para los badges
                                    return "<span class='inline-flex items-center justify-center min-h-6 px-2 py-0.5 text-sm font-medium tracking-tight rounded-xl bg-primary-500/10 text-primary-700 dark:text-primary-400 border border-primary-500/20'>✓ {$label}</span>";
                                })->implode(' ');
                                
                                return new \Illuminate\Support\HtmlString("<div class='flex flex-wrap gap-2 mt-2'>{$badges}</div>");
                            })
                            ->visible(fn (Forms\Get $get) => filled($get('role'))),
                    ]),

                Forms\Components\Section::make('Permisos adicionales')
                    ->description('Puede otorgar permisos adicionales a los que ya tiene por su rol.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns([
                                'default' => 3,
                            ])
                            ->schema(static::getPermissionCheckboxes()),
                    ]),
            ]);
    }

    protected static function getPermissionCheckboxes(): array
    {
        $modules = [
            'Productos' => ['ver_productos', 'crear_productos', 'editar_productos', 'eliminar_productos'],
            'Categorias' => ['ver_categorias', 'crear_categorias', 'editar_categorias', 'eliminar_categorias'],
            'Ventas' => ['ver_ventas', 'crear_ventas', 'editar_ventas', 'eliminar_ventas', 'anular_ventas'],
            'Compras' => ['ver_compras', 'crear_compras', 'editar_compras', 'eliminar_compras'],
            'Inventario' => ['ver_stock', 'ver_ajustes_inventario', 'crear_ajustes_inventario', 'editar_ajustes_inventario', 'eliminar_ajustes_inventario'],
            'Almacenes' => ['ver_almacenes', 'crear_almacenes', 'editar_almacenes', 'eliminar_almacenes', 'ver_ubicaciones', 'crear_ubicaciones', 'editar_ubicaciones', 'eliminar_ubicaciones'],
            'Clientes' => ['ver_clientes', 'crear_clientes', 'editar_clientes', 'eliminar_clientes'],
            'Proveedores' => ['ver_proveedores', 'crear_proveedores', 'editar_proveedores', 'eliminar_proveedores'],
            'Caja' => ['ver_cajas', 'crear_cajas', 'editar_cajas', 'eliminar_cajas'],
            'Comprobantes' => ['ver_comprobantes', 'crear_comprobantes', 'editar_comprobantes', 'eliminar_comprobantes', 'imprimir_comprobantes'],
            'Usuarios' => ['ver_usuarios', 'crear_usuarios', 'editar_usuarios', 'eliminar_usuarios', 'gestionar_roles'],
            'Sistema' => ['ver_dashboard', 'ver_reportes', 'ver_auditoria'],
        ];

        $sections = [];

        foreach ($modules as $label => $perms) {
            $options = [];
            foreach ($perms as $perm) {
                $readable = str_replace('_', ' ', $perm);
                $readable = ucfirst($readable);
                $options[$perm] = $readable;
            }

            $sections[] = Forms\Components\Section::make($label)
                ->compact()
                ->schema([
                    Forms\Components\CheckboxList::make("permissions_{$label}")
                        ->label('')
                        ->options($options)
                        ->columns(1)
                        ->afterStateHydrated(function ($component, $state, $record) use ($perms) {
                            if ($record) {
                                $directPermissions = $record->getDirectPermissions()->pluck('name')->toArray();
                                $component->state(array_intersect($directPermissions, $perms));
                            }
                        })
                        ->dehydrated(false),
                ]);
        }

        return $sections;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'supervisor' => 'warning',
                        'vendedor' => 'success',
                        'almacenero' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permisos directos')
                    ->getStateUsing(fn ($record) => $record->getDirectPermissions()->count())
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
