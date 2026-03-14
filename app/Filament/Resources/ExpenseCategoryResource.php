<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseCategoryResource\Pages;
use App\Filament\Resources\ExpenseCategoryResource\RelationManagers;
use App\Models\ExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?string $modelLabel = 'Categoría de Gasto';
    protected static ?string $pluralModelLabel = 'Categorías de Gastos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la Categoría')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50),
                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Solo Activos')
                    ->query(fn (Builder $query) => $query->where('active', true))
                    ->default(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseCategories::route('/'),
            'create' => Pages\CreateExpenseCategory::route('/create'),
            'edit' => Pages\EditExpenseCategory::route('/{record}/edit'),
        ];
    }
}
