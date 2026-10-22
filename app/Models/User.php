<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Empresa, $this>
     */
    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class)
            ->withPivot('rol')
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * @return Collection<int, Empresa>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->empresas;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->empresas()->whereKey($tenant)->exists();
    }

    public function rolEn(Empresa $empresa): ?Rol
    {
        $empresaId = $empresa->getKey();

        return once(function () use ($empresaId): ?Rol {
            $rol = $this->empresas()->whereKey($empresaId)->first()?->pivot->rol;

            return $rol === null ? null : Rol::from($rol);
        });
    }

    public function esAdministradorDe(?Empresa $empresa): bool
    {
        return $empresa !== null && $this->rolEn($empresa) === Rol::Administrador;
    }

    /**
     * Administrador de la empresa que está abierta en el panel.
     */
    public function esAdministrador(): bool
    {
        $empresa = Filament::getTenant();

        return $empresa instanceof Empresa && $this->esAdministradorDe($empresa);
    }
}
