<div>
    <div class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Letzte Aktivitäten</div>

    @forelse($activities as $activity)
        <div class="mb-3 flex gap-3 border-b border-[color:var(--nx-line)] pb-3 last:mb-0 last:border-0 last:pb-0" wire:key="pa-{{ $activity->id }}">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-faint)]"></span>
            <div class="min-w-0 flex-1">
                <div class="text-sm leading-snug text-[color:var(--nx-text)]">
                    {{ $activity->message ?: \Illuminate\Support\Str::headline($activity->name ?? 'Aktivität') }}
                </div>
                <div class="mt-0.5 flex items-center gap-1.5 text-xs text-[color:var(--nx-faint)]">
                    @svg('heroicon-o-clock', 'w-3 h-3 opacity-60')
                    <span>{{ $activity->created_at->diffForHumans() }}</span>
                    @if($activity->user)
                        <span>· {{ $activity->user->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <x-nx-empty icon="heroicon-o-bolt">
            Noch keine Aktivitäten
            <x-slot name="action">
                <span class="text-[11px] text-[color:var(--nx-faint)]">Druck-Ereignisse erscheinen hier</span>
            </x-slot>
        </x-nx-empty>
    @endforelse
</div>
