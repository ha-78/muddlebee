{{-- The bee and the "muddlebee" wordmark. The word takes the text color around it, with "bee" in the muted shade.
     size: "sm" for the top bar, "lg" for the welcome page. --}}
@props(['size' => 'sm'])

<span {{ $attributes->class(['inline-flex items-center', 'gap-1.5' => $size === 'sm', 'gap-3' => $size === 'lg']) }}>
    <x-logo-mark @class(['size-9' => $size === 'sm', 'size-24' => $size === 'lg']) />
    <span @class(['font-logo font-semibold leading-none tracking-tight', 'text-[24px]' => $size === 'sm', 'text-[44px]' => $size === 'lg'])>muddle<span class="text-muted">bee</span></span>
</span>
