<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SearchAgentResource\Pages;
use App\Models\SearchAgent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SearchAgentResource extends Resource
{
    protected static ?string $model = SearchAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Suchagenten';

    protected static ?string $modelLabel = 'Suchagent';

    protected static ?string $pluralModelLabel = 'Suchagenten';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('General'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('user_id')
                            ->label(__('Owner'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder(__('Unassigned (legacy import)'))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('postcode')
                            ->label(__('Postcode'))
                            ->maxLength(5),
                        Forms\Components\TextInput::make('radius')
                            ->label(__('Radius (km)'))
                            ->numeric(),
                    ]),

                Forms\Components\Section::make(__('Price'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('price_from')
                            ->label(__('Price from (€)'))
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('price_to')
                            ->label(__('Price to (€)'))
                            ->numeric()
                            ->prefix('€'),
                    ]),

                Forms\Components\Section::make(__('Size & rooms'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('size_from')
                            ->label(__('Size m² from'))
                            ->numeric(),
                        Forms\Components\TextInput::make('size_to')
                            ->label(__('Size m² to'))
                            ->numeric(),
                        Forms\Components\TextInput::make('min_rooms')
                            ->label(__('Min. rooms'))
                            ->numeric(),
                        Forms\Components\TextInput::make('max_rooms')
                            ->label(__('Max. rooms'))
                            ->numeric(),
                    ]),

                Forms\Components\Section::make(__('Valuation'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('pot_return_from')
                            ->label(__('Potential return % from'))
                            ->numeric(),
                        Forms\Components\TextInput::make('pot_return_to')
                            ->label(__('Potential return % to'))
                            ->numeric(),
                    ]),

                Forms\Components\Section::make(__('System'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Forms\Components\TextInput::make('last_processed_listing_id')
                            ->label(__('Last processed listing ID'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Owner'))
                    ->placeholder(__('Unassigned'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('postcode')
                    ->label(__('Postcode'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('radius')
                    ->label(__('Radius (km)'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_from')
                    ->label(__('Price from (€)'))
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_to')
                    ->label(__('Price to (€)'))
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('size_from')
                    ->label(__('Size m² from'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('size_to')
                    ->label(__('Size m² to'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('min_rooms')
                    ->label(__('Min. rooms'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('max_rooms')
                    ->label(__('Max. rooms'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pot_return_from')
                    ->label(__('Potential return % from'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pot_return_to')
                    ->label(__('Potential return % to'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSearchAgents::route('/'),
            'create' => Pages\CreateSearchAgent::route('/create'),
            'edit' => Pages\EditSearchAgent::route('/{record}/edit'),
        ];
    }
}
