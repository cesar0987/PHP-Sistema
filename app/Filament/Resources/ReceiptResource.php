<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages\CreateReceipt;
use App\Filament\Resources\ReceiptResource\Pages\EditReceipt;
use App\Filament\Resources\ReceiptResource\Pages\ListReceipts;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Facturacion';

    protected static ?string $modelLabel = 'Comprobante';

    protected static ?string $pluralModelLabel = 'Comprobantes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('sale_id')->label('Venta Nro.')->relationship('sale', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => "Venta #{$record->id} - ".number_format($record->total, 0, ',', '.').' Gs')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('type')->label('Tipo')->options([
                'ticket' => 'Ticket',
                'invoice' => 'Factura',
                'receipt' => 'Recibo',
            ])->required(),
            Forms\Components\TextInput::make('number')->label('Numero')->required()->unique(ignorable: fn ($record) => $record),
            Forms\Components\DateTimePicker::make('generated_at')->label('Fecha de emision'),
            Forms\Components\TextInput::make('file_path')->label('Archivo')->readOnly(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Numero')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sale_id')->label('Venta #')->sortable(),
                Tables\Columns\TextColumn::make('sale.customer.name')->label('Cliente')->placeholder('Consumidor final'),
                Tables\Columns\TextColumn::make('sale.total')->label('Monto')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' Gs'),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'invoice' => 'success',
                        'ticket' => 'info',
                        'receipt' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ticket' => 'Ticket',
                        'invoice' => 'Factura',
                        'receipt' => 'Recibo',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('generated_at')->label('Emitido')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('generated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Tipo')->options([
                    'ticket' => 'Ticket',
                    'invoice' => 'Factura',
                    'receipt' => 'Recibo',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Receipt $record) {
                        $service = app(ReceiptService::class);

                        return $service->downloadPdf($record);
                    }),
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
            'index' => ListReceipts::route('/'),
            'create' => CreateReceipt::route('/create'),
            'edit' => EditReceipt::route('/{record}/edit'),
        ];
    }
}
