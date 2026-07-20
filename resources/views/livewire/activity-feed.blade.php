<div>
    <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Letzte Aktivitäten</div>

    @forelse($activities as $activity)
        <div class="flex gap-3 pb-3 mb-3 border-b border-[var(--ui-border)] last:border-0 last:pb-0 last:mb-0" wire:key="pa-{{ $activity->id }}">
            <span class="mt-1.5 w-2 h-2 rounded-full bg-[var(--ui-primary)] shrink-0"></span>
            <div class="min-w-0 flex-1">
                <div class="text-sm text-[var(--ui-secondary)] leading-snug">
                    {{ $activity->message ?: \Illuminate\Support\Str::headline($activity->name ?? 'Aktivität') }}
                </div>
                <div class="flex items-center gap-1.5 text-xs text-[var(--ui-muted)] mt-0.5">
                    @svg('heroicon-o-clock', 'w-3 h-3 opacity-60')
                    <span>{{ $activity->created_at->diffForHumans() }}</span>
                    @if($activity->user)
                        <span>· {{ $activity->user->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="py-8 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)] mb-3">
                @svg('heroicon-o-bolt', 'w-5 h-5 text-[var(--ui-muted)]')
            </div>
            <p class="text-xs text-[var(--ui-muted)] m-0">Noch keine Aktivitäten</p>
            <p class="text-[11px] text-[var(--ui-muted)] mt-1 m-0">Druck-Ereignisse erscheinen hier</p>
        </div>
    @endforelse
</div>
