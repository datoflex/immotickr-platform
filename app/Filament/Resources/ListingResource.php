<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Livewire\SearchAgents\Manager;
use App\Models\Listing;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Inserate';

    protected static ?string $modelLabel = 'Inserat';

    protected static ?string $pluralModelLabel = 'Inserate';

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
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('source_url')
                            ->label(__('Source URL'))
                            ->url()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('detail_url')
                            ->label(__('Detail URL'))
                            ->url()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('Address'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('raw_address')
                            ->label(__('Raw address'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('street')
                            ->label(__('Street'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('zip')
                            ->label(__('Zip'))
                            ->maxLength(16),
                        Forms\Components\TextInput::make('city')
                            ->label(__('City'))
                            ->maxLength(128),
                        Forms\Components\TextInput::make('state')
                            ->label(__('State'))
                            ->maxLength(128),
                        Forms\Components\TextInput::make('country')
                            ->label(__('Country'))
                            ->maxLength(128),
                    ]),

                Forms\Components\Section::make(__('Price'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label(__('Price')),
                        Forms\Components\TextInput::make('price_cents')
                            ->label(__('Price (cents)'))
                            ->numeric(),
                        Forms\Components\TextInput::make('price_m2')
                            ->label(__('Price / m²')),
                        Forms\Components\TextInput::make('price_m2_cents')
                            ->label(__('Price / m² (cents)'))
                            ->numeric(),
                        Forms\Components\TextInput::make('preisbewertung')
                            ->label(__('Price valuation')),
                        Forms\Components\TextInput::make('standortbewertung')
                            ->label(__('Location valuation')),
                    ]),

                Forms\Components\Section::make(__('Property details'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('flaeche')
                            ->label(__('Size m²')),
                        Forms\Components\TextInput::make('zimmer')
                            ->label(__('Rooms')),
                        Forms\Components\TextInput::make('baujahr')
                            ->label(__('Construction year')),
                        Forms\Components\TextInput::make('erbbaurecht')
                            ->label(__('Leasehold')),
                        Forms\Components\TextInput::make('zv')
                            ->label('ZV'),
                        Forms\Components\TextInput::make('vermietet')
                            ->label(__('Rented')),
                    ]),

                Forms\Components\Section::make(__('Return & rent'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('rendite_pot')
                            ->label(__('Potential return')),
                        Forms\Components\TextInput::make('rendite_pot_num')
                            ->label(__('Potential return (numeric)'))
                            ->numeric(),
                        Forms\Components\TextInput::make('rendite_ist')
                            ->label(__('Current return')),
                        Forms\Components\TextInput::make('rendite_ist_num')
                            ->label(__('Current return (numeric)'))
                            ->numeric(),
                        Forms\Components\TextInput::make('miete_pot_m2')
                            ->label(__('Potential rent / m²')),
                        Forms\Components\TextInput::make('miete_pot_m2_cents')
                            ->label(__('Potential rent / m² (cents)'))
                            ->numeric(),
                        Forms\Components\TextInput::make('miete_ist_m2')
                            ->label(__('Current rent / m²')),
                        Forms\Components\TextInput::make('miete_ist_m2_cents')
                            ->label(__('Current rent / m² (cents)'))
                            ->numeric(),
                    ]),

                Forms\Components\Section::make(__('System'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('hash')
                            ->label('Hash')
                            ->required()
                            ->maxLength(64)
                            ->disabled(fn (?Listing $record) => $record !== null),
                        Forms\Components\DateTimePicker::make('email_date')
                            ->label(__('Email date')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('city')
                    ->label(__('City'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('zip')
                    ->label(__('Zip'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('Price')),
                Tables\Columns\TextColumn::make('price_m2')
                    ->label(__('Price / m²'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('flaeche')
                    ->label(__('Size m²'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zimmer')
                    ->label(__('Rooms'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('baujahr')
                    ->label(__('Construction year'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rendite_pot')
                    ->label(__('Potential return'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('hash')
                    ->label('Hash')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                Tables\Columns\TextColumn::make('email_date')
                    ->label(__('Email date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('location')
                    ->form([
                        Forms\Components\Select::make('postcode')
                            ->label('Ort')
                            ->placeholder('Postleitzahl oder Ort suchen')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => static::locationOptions($search))
                            ->getOptionLabelUsing(fn (string $value): string => trim($value.' '.Location::where('postcode', $value)->value('city_name'))),
                        Forms\Components\Select::make('radius')
                            ->label('Umkreis')
                            ->options(collect(Manager::RADIUS_STEPS)->mapWithKeys(fn (int $km): array => [$km => "{$km} km"])->all())
                            ->default(0)
                            ->selectablePlaceholder(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['postcode'] ?? null),
                        fn (Builder $query) => $query->nearPostcode($data['postcode'], (float) ($data['radius'] ?? 0)),
                    ))
                    ->indicateUsing(fn (array $data): ?string => filled($data['postcode'] ?? null)
                        ? 'Ort: '.$data['postcode'].' + '.(int) ($data['radius'] ?? 0).' km'
                        : null),
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

    /**
     * Postcodes whose number or place name starts with the typed text, labelled "10115 Berlin".
     *
     * @return array<string, string>
     */
    private static function locationOptions(string $search): array
    {
        $prefix = addcslashes(trim($search), '%_\\').'%';

        return Location::query()
            ->where('postcode', 'like', $prefix)
            ->orWhere('city_name', 'like', $prefix)
            ->orderBy('postcode')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Location $location): array => [$location->postcode => "{$location->postcode} {$location->city_name}"])
            ->all();
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
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}
