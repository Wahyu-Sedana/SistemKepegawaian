<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\UserHistory;
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
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

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
                TextInput::make('nik')
                    ->label('NIK')
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(3),
                TextInput::make('phone')
                    ->label('No. Telepon')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context) => $context === 'create')
                    ->helperText(fn(string $context) => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : null),
                Select::make('roles')
                    ->label('Peran')
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
                TextColumn::make('nik')
                    ->label('NIK')
                    ->sortable()
                    ->searchable()
                    ->placeholder("-"),
                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No. Telepon')
                    ->sortable()
                    ->placeholder("-"),
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
                TextColumn::make('created_at')
                    ->label('Tanggal Bergabung')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name')
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Staff')
                    ->modalDescription('Apakah Anda yakin ingin menghapus staff ini? Data akan disimpan ke history.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->form([
                        Textarea::make('deletion_reason')
                            ->label('Alasan Penghapusan')
                            ->placeholder('Contoh: Resign, PHK, Pensiun, dll.')
                            ->rows(3),
                    ])
                    ->before(function (User $record, array $data) {

                        if ($record->id === Auth::id()) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Menghapus')
                                ->body('Anda tidak dapat menghapus akun sendiri.')
                                ->send();

                            return false;
                        }


                        try {
                            UserHistory::createFromUser(
                                $record,
                                Auth::id(),
                                $data['deletion_reason'] ?? 'Tidak ada keterangan'
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Menyimpan History')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->send();

                            return false;
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Staff Dihapus')
                            ->body('Data staff berhasil dihapus dan tersimpan di history.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Staff Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua staff yang dipilih? Data akan disimpan ke history.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->form([
                            Textarea::make('deletion_reason')
                                ->label('Alasan Penghapusan')
                                ->placeholder('Contoh: Restructuring, Pemutusan Kontrak, dll.')
                                ->rows(3),
                        ])
                        ->before(function ($records, array $data) {
                            $currentUserId = Auth::id();
                            $savedCount = 0;
                            $skippedCount = 0;

                            foreach ($records as $record) {
                                if ($record->id === $currentUserId) {
                                    $skippedCount++;
                                    continue;
                                }

                                try {
                                    UserHistory::createFromUser(
                                        $record,
                                        $currentUserId,
                                        $data['deletion_reason'] ?? 'Bulk delete'
                                    );
                                    $savedCount++;
                                } catch (\Exception $e) {
                                    // Log error tapi lanjutkan
                                    \Log::error('Failed to save user history: ' . $e->getMessage());
                                }
                            }

                            // Notifikasi hasil
                            if ($skippedCount > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('Beberapa Staff Dilewati')
                                    ->body("$skippedCount staff dilewati (termasuk akun Anda sendiri).")
                                    ->send();
                            }

                            if ($savedCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('History Tersimpan')
                                    ->body("$savedCount staff berhasil disimpan ke history.")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
