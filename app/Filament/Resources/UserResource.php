<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Manajemen Kepegawaian';
    protected static ?string $navigationLabel = 'Staff';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nik')->unique(ignoreRecord: true),
                TextInput::make('name')->required(),
                Textarea::make('address'),
                TextInput::make('phone'),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context) => $context === 'create'),
                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nik')->sortable()->searchable()->placeholder("-"),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('email')->sortable(),
                TextColumn::make('phone')->sortable()->placeholder("-"),
                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'super_admin' => 'danger',
                            'panel_user' => 'success',
                            default => 'info',
                        };
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')->dateTime('d M Y H:i'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
