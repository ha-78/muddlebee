@extends('layouts.app')

@section('title', $user->name)
@section('back', route('users.index'))

@section('content')
    <div class="space-y-6">
        <section class="flex items-center gap-4 rounded-card border border-surface-line bg-surface p-4">
            <x-avatar :user="$user" size="lg" />
            <div class="min-w-0 flex-1">
                <h2 class="truncate text-[17px] font-semibold">{{ $user->name }}</h2>
                <p class="truncate text-[13px] text-muted">{{ $user->email }}</p>
            </div>
            <x-ui.badge class="shrink-0">{{ __('users.role') }}: {{ $user->role->label() }}</x-ui.badge>
        </section>

        @if ($canChangeRole)
            <x-ui.form-card :label="__('users.role')" :action="route('users.role.update', $user)" method="PATCH">
                @if (count($assignableRoles) > 1)
                    <x-ui.field
                        :label="__('users.role')"
                        name="role"
                        :options="collect($assignableRoles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all()"
                        :selected="$user->role->value"
                    />
                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="tonal">{{ __('users.save_role') }}</x-ui.button>
                    </div>
                @else
                    <input type="hidden" name="role" value="{{ $assignableRoles[0]->value }}">
                    <x-ui.button type="submit" class="w-full">{{ __('users.make_member') }}</x-ui.button>

                    @error('role')
                        <p class="flex items-center gap-1.5 text-[13px] font-semibold text-field-error-ink">
                            <x-icon name="exclamation-circle" size="4" stroke="2" class="shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                @endif
            </x-ui.form-card>
        @endif

        @if ($canAssignFeatures)
            <section class="space-y-3">
                <h2 class="px-1 text-[13px] font-semibold uppercase tracking-wide text-muted">{{ __('users.permissions') }}</h2>

                @if ($featureGroups === [])
                    <p class="rounded-card border border-surface-line bg-surface px-4 py-3 text-[15px] text-muted">{{ __('users.no_permissions') }}</p>
                @else
                    <form method="POST" action="{{ route('users.features.update', $user) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        {{-- Each group takes its section's colors, so the switches read as that part of the app. --}}
                        @foreach ($featureGroups as $group)
                            <x-ui.group :heading="$group['label']" data-color="{{ $group['section']->color() }}">
                                @foreach ($group['features'] as $feature)
                                    <div>
                                        <input type="hidden" name="features[{{ $feature->value }}]" value="0">
                                        <x-ui.switch plain :label="$feature->label()" name="features[{{ $feature->value }}]" :checked="$user->allows($feature)" />
                                    </div>
                                @endforeach
                            </x-ui.group>
                        @endforeach

                        @error('features')
                            <x-ui.message type="error" :dismissible="false">{{ $message }}</x-ui.message>
                        @enderror

                        <x-ui.button type="submit" class="w-full">{{ __('users.save_permissions') }}</x-ui.button>
                    </form>
                @endif
            </section>
        @endif

        @if ($canResetPassword)
            {{-- Same pattern as invites: create a link, then copy it and send it yourself. --}}
            <x-ui.form-card :label="__('users.password_reset.heading')" :action="route('users.password-reset.store', $user)" icon="link">
                <p class="px-0.5 text-[13px] text-muted">{{ __('users.password_reset.hint') }}</p>

                @if ($resetLink)
                    <div class="space-y-2">
                        <p class="px-0.5 text-[13px] text-muted">
                            {{ __('users.password_reset.expires') }}
                            <time datetime="{{ $resetLink->expires_at->toAtomString() }}">{{ $resetLink->expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</time>
                        </p>

                        <div x-data="copyText" class="flex items-center gap-2">
                            <input
                                x-ref="link"
                                type="text"
                                readonly
                                value="{{ $resetLink->link() }}"
                                aria-label="{{ __('users.password_reset.link') }}"
                                spellcheck="false"
                                autocomplete="off"
                                x-on:focus="$el.select()"
                                class="h-10 w-full min-w-0 rounded-field bg-field px-3 text-base text-field-ink sm:text-[13px]"
                            >
                            <x-ui.button variant="tonal" class="h-10 shrink-0" x-on:click="copy($refs.link.value)">
                                <x-icon name="link" size="4" stroke="2" />
                                <span x-show="! copied">{{ __('users.invites.copy') }}</span>
                                <span x-show="copied" x-cloak>{{ __('users.invites.copied') }}</span>
                            </x-ui.button>
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-2">
                    @if ($resetLink)
                        <x-ui.button variant="ghost" type="submit" form="password-reset-remove">{{ __('users.password_reset.remove') }}</x-ui.button>
                    @endif
                    <x-ui.button type="submit" :variant="$resetLink ? 'tonal' : 'primary'" :class="$resetLink ? '' : 'w-full'">{{ $resetLink ? __('users.password_reset.replace') : __('users.password_reset.create') }}</x-ui.button>
                </div>
            </x-ui.form-card>

            @if ($resetLink)
                <form id="password-reset-remove" method="POST" action="{{ route('users.password-reset.destroy', $user) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endif
    </div>
@endsection
