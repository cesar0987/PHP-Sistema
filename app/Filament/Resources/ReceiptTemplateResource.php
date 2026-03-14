<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptTemplateResource\Pages;
use App\Filament\Resources\ReceiptTemplateResource\RelationManagers;
use App\Models\ReceiptTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReceiptTemplateResource extends Resource
{
    protected static ?string $model = ReceiptTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Plantilla')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Comprobante')
                            ->options([
                                'sale_ticket' => 'Venta - Ticket (80mm)',
                                'sale_invoice' => 'Venta - Factura (A4)',
                                'purchase_ticket' => 'Compra - Ticket (80mm)',
                            ])
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activar esta plantilla')
                            ->default(true),
                        Forms\Components\Textarea::make('content_html')
                            ->label('Contenido HTML + Variables (Blade)')
                            ->columnSpanFull()
                            ->rows(20)
                            ->hint('Usa variables como {{ $sale->total }} o {{ $purchase->total }}. Código HTML/CSS soportado.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activa'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última edición')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
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
            'index' => Pages\ManageReceiptTemplates::route('/'),
        ];
    }
}
