<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenggajianResource\Pages;
use App\Models\Penggajian;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;

class PenggajianResource extends Resource
{
    protected static ?string $model = Penggajian::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Manajemen Kepegawaian';
    protected static ?string $navigationLabel = 'Penggajian';
    protected static ?string $pluralModelLabel = 'Data Penggajian';
    protected static ?string $modelLabel = 'Penggajian';

    public static function calculateNetSalary(Set $set, Get $get): void
    {
        $pokok    = (float) ($get('gaji_pokok') ?? 0);
        $tunjangan = (float) ($get('tunjangan') ?? 0);
        $potongan  = (float) ($get('potongan') ?? 0);

        $bersih = $pokok + $tunjangan - $potongan;

        $set('gaji_bersih', $bersih);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pegawai & Periode')
                    ->columns(3)
                    ->schema([
                        Select::make('user_id')
                            ->label('Pegawai')
                            ->relationship('user', 'name', fn(Builder $query) => $query->whereHas('roles', fn($q) => $q->where('name', 'Staff')))
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('tanggal_gaji')
                            ->label('Tanggal Gaji Dibayarkan')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('periode', date('Y-m', strtotime($state)));
                                }
                            }),

                        TextInput::make('periode')
                            ->label('Periode (YYYY-MM)')
                            ->readOnly(),
                    ]),

                Forms\Components\Section::make('Komponen Gaji')
                    ->columns(3)
                    ->schema([
                        TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR'),

                        TextInput::make('tunjangan')
                            ->label('Total Tunjangan')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR'),

                        TextInput::make('potongan')
                            ->label('Total Potongan')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR'),

                        TextInput::make('gaji_bersih')
                            ->label('Gaji Bersih (Netto)')
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->dehydrated()
                            ->afterStateHydrated(fn(Set $set, Get $get) => self::calculateNetSalary($set, $get))
                            ->suffix('IDR'),

                        Select::make('status')
                            ->options(['draft' => 'Draft', 'paid' => 'Sudah Dibayar'])
                            ->default('draft')
                            ->label('Status Pembayaran')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('tunjangan')
                    ->label('Tunjangan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('potongan')
                    ->label('Potongan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gaji_bersih')
                    ->label('Gaji Bersih')
                    ->numeric()
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'warning',
                        'paid' => 'success',
                    }),

                TextColumn::make('tanggal_gaji')
                    ->label('Dibayarkan')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPenggajians::route('/'),
            'create' => Pages\CreatePenggajian::route('/create'),
            'edit' => Pages\EditPenggajian::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'HRD'])) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Slip Gaji Periode ' . $infolist->getRecord()->periode)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Pegawai'),

                        TextEntry::make('tanggal_gaji')
                            ->label('Tanggal Pembayaran')
                            ->date(),

                        TextEntry::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->numeric(decimalPlaces: 0, thousandsSeparator: '.'),

                        TextEntry::make('tunjangan')
                            ->label('Tunjangan')
                            ->numeric(decimalPlaces: 0, thousandsSeparator: '.'),

                        TextEntry::make('potongan')
                            ->label('Potongan')
                            ->numeric(decimalPlaces: 0, thousandsSeparator: '.'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ]),

                Section::make('Total Pembayaran')
                    ->schema([
                        TextEntry::make('gaji_bersih')
                            ->label('GAJI BERSIH (NETTO)')
                            ->numeric(decimalPlaces: 0, thousandsSeparator: '.')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('success'),
                    ]),
            ]);
    }
}
