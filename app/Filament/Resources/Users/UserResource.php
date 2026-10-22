<?php

namespace App\Filament\Resources\Users;

use App\Enums\Rol;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Empresa;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Usuarios de la empresa abierta. El rol vive en la tabla intermedia empresa_user,
 * porque una misma persona puede ser administradora de una bloquería y encargada en otra.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $tenantOwnershipRelationshipName = 'empresas';

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(),
                Select::make('rol')
                    ->options(Rol::class)
                    ->default(Rol::Encargado)
                    ->required()
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->helperText(fn (?User $record): ?string => $record?->is(auth()->user())
                        ? 'No puedes cambiar tu propio rol.'
                        : null),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (?User $record): bool => $record === null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (?User $record): ?string => $record ? 'Déjala vacía para no cambiarla.' : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'empresas' => fn ($query) => $query->whereKey(Filament::getTenant()),
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('rol')
                    ->state(fn (User $record): ?Rol => Rol::tryFrom($record->empresas->first()?->pivot->rol ?? ''))
                    ->badge()
                    ->color(fn (?Rol $state): string => $state === Rol::Administrador ? 'primary' : 'gray'),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, User $record): array {
                        $data['rol'] = $record->rolEn(static::empresaActual());

                        return $data;
                    })
                    ->using(function (User $record, array $data): User {
                        $rol = $data['rol'] ?? null;
                        unset($data['rol']);

                        $record->update($data);

                        if ($rol !== null && $record->isNot(auth()->user())) {
                            static::empresaActual()->agregarUsuario($record, Rol::from($rol instanceof Rol ? $rol->value : $rol));
                        }

                        return $record;
                    }),
                Action::make('quitar')
                    ->label('Quitar de la empresa')
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('La persona ya no podrá entrar a esta bloquería. Su cuenta no se borra.')
                    ->authorize('delete')
                    ->action(fn (User $record) => static::empresaActual()->users()->detach($record)),
            ]);
    }

    public static function empresaActual(): Empresa
    {
        /** @var Empresa */
        return Filament::getTenant();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
