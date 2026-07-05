<?php

namespace App\Filament\Resources\ProdutoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantesRelationManager extends RelationManager
{
    protected static string $relationship = 'variantes';

    protected static ?string $recordTitleAttribute = 'sku';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Variante')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('preco_override')
                            ->label('Preço Override')
                            ->numeric()
                            ->prefix('MZN')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('disponivel')
                            ->label('Disponível')
                            ->default(true)
                            ->columnSpan(2),
                    ])->columns(4),

                Forms\Components\Section::make('Atributos')
                    ->schema([
                        Forms\Components\Repeater::make('atributos')
                            ->label('Atributos da Variante')
                            ->schema([
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo')
                                    ->options([
                                        'cor' => 'Cor',
                                        'tamanho' => 'Tamanho',
                                        'material' => 'Material',
                                        'peso' => 'Peso',
                                        'custom' => 'Personalizado',
                                    ])
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\ColorPicker::make('valor_hex')
                                    ->label('Cor (Hex)')
                                    ->visible(fn ($get) => $get('tipo') === 'cor')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar atributo')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('precoFinal')
                    ->label('Preço')
                    ->money('MZN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($record): string => $record->stock > 0 ? 'success' : 'danger'),

                Tables\Columns\IconColumn::make('disponivel')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('atributos')
                    ->label('Atributos')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return '-';
                        }
                        return collect($state)
                            ->map(fn ($attr) => "{$attr['nome']}: {$attr['valor']}")
                            ->join(', ');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
