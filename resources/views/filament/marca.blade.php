{{-- Emblema del logo con el nombre al lado. Se usa en la barra del panel y en la pantalla de inicio de sesión. --}}
<style>
    .marca { display: inline-flex; align-items: center; gap: 0.6rem; height: 100%; }
    .marca img { height: 100%; width: auto; aspect-ratio: 1; }
    .marca-texto { display: flex; flex-direction: column; justify-content: center; line-height: 1; font-style: italic; text-transform: uppercase; }
    .marca-antetitulo { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; color: var(--gray-950); }
    .marca-nombre { font-size: 1.15rem; font-weight: 800; letter-spacing: 0.01em; color: var(--primary-800); }
    .dark .marca-antetitulo { color: var(--gray-100); }
    .dark .marca-nombre { color: var(--primary-400); }
</style>
<span class="marca">
    <img src="{{ asset('img/emblema.png') }}" alt="">
    <span class="marca-texto">
        <span class="marca-antetitulo">{{ config('bloqueria.marca.antetitulo') }}</span>
        <span class="marca-nombre">{{ config('bloqueria.marca.nombre') }}</span>
    </span>
</span>
