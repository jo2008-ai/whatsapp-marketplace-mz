<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdutoResource\Pages;
use App\Filament\Resources\ProdutoResource\RelationManagers\VariantesRelationManager;
use App\Models\Produto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Gestão de Produtos';

    protected static ?string $modelLabel = 'Produto';

    protected static ?string $pluralModelLabel = 'Produtos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Produto')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('preco')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->suffix('MZN')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->columnSpan(1),

                                Forms\Components\Select::make('categoria_id')
                                    ->relationship('categoria', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Select::make('vendedor_id')
                            ->relationship('vendedor', 'nome')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(1),

                Forms\Components\Section::make('Imagens')
                    ->schema([
                        Forms\Components\FileUpload::make('imagem')
                            ->image()
                            ->imageEditor()
                            ->directory('produtos')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpan(1),

                        Forms\Components\FileUpload::make('imagem2')
                            ->label('Imagem Trás')
                            ->image()
                            ->imageEditor()
                            ->directory('produtos')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('disponivel')
                            ->default(true)
                            ->label('Disponível'),

                        Forms\Components\Toggle::make('destaque')
                            ->default(false)
                            ->label('Destaque'),
                    ])->columns(2),

                Forms\Components\Section::make('Variantes (Cores e Tamanhos)')
                    ->schema([
                        Forms\Components\KeyValue::make('cores')
                            ->label('Cores')
                            ->addActionLabel('Adicionar cor')
                            ->reorderable()
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('tamanhos')
                            ->label('Tamanhos')
                            ->addActionLabel('Adicionar tamanho')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('imagem_url')
                    ->label('Imagem')
                    ->circular()
                    ->defaultImageUrl(url('https://via.placeholder.com/50'))
                    ->disk('public'),

                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('categoria.nome')
                    ->label('Categoria')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendedor.nome')
                    ->label('Vendedor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('preco')
                    ->money('MZN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->color(fn (Produto $record): string => $record->stock > 0 ? 'success' : 'danger'),

                Tables\Columns\IconColumn::make('disponivel')
                    ->boolean(),

                Tables\Columns\IconColumn::make('destaque')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->relationship('categoria', 'nome')
                    ->label('Categoria')
                    ->placeholder('Todas'),

                Tables\Filters\TernaryFilter::make('disponivel')
                    ->label('Disponível'),

                Tables\Filters\TernaryFilter::make('destaque')
                    ->label('Destaque'),
            ])
            ->actions([
                Tables\Actions\Action::make('matriz')
                    ->label('Matriz')
                    ->icon('heroicon-o-table-cells')
                    ->url(fn (Produto $record): string => route('filament.admin.resources.produtos.matrix', $record)),
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
            VariantesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
            'matrix' => Pages\VariantMatrix::route('/{record}/matrix'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return parent::getNavigationItems();
    }
}
