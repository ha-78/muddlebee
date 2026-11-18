@extends('layouts.front')

@section('content')
    <div class="mx-auto max-w-sm space-y-4 rounded-card border border-surface-line bg-surface p-5 shadow-sm">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">{{ __('users.password_reset.choose_heading') }}</h1>
            <p class="mt-2 text-[15px] font-semibold">{{ $user->name }}</p>
            <p class="text-[13px] text-muted">{{ __('users.password_reset.choose_intro') }}</p>
        </div>

        <form method="POST" action="{{ route('password-reset.store', ['token' => $link->token]) }}" class="space-y-3">
            @csrf

            <x-ui.field :label="__('users.email')" name="email" type="email" :value="$user->email" readonly autocomplete="username" />
            <x-ui.field :label="__('users.password')" name="password" type="password" required autocomplete="new-password" />
            <x-ui.field :label="__('users.password_confirmation')" name="password_confirmation" type="password" required autocomplete="new-password" />

            <x-ui.button type="submit" class="mt-2 w-full">{{ __('users.password_reset.save') }}</x-ui.button>
        </form>
    </div>
@endsection
