@props([
    'action' => 'edit',
    'icon' => null,
    'color' => null,
    'size' => 'xs',
    'outline' => true,
    'flat' => false,
    'label' => null,
    'tooltip' => null,
    'href' => null,
    'target' => null,
])

@php
    $actionConfigs = [
        'view' => [
            'icon' => 'eye',
            'color' => 'white',
            'defaultLabel' => __('common.actions.view'),
        ],
        'edit' => [
            'icon' => 'pencil',
            'color' => 'primary',
            'defaultLabel' => __('common.actions.edit'),
        ],
        'delete' => [
            'icon' => 'trash',
            'color' => 'red',
            'defaultLabel' => __('common.actions.delete'),
        ],
        'verify' => [
            'icon' => 'check',
            'color' => 'emerald',
            'defaultLabel' => __('common.actions.verify'),
        ],
        'download' => [
            'icon' => 'arrow-down-tray',
            'color' => 'primary',
            'defaultLabel' => __('common.actions.download'),
        ],
        'custom' => [
            'icon' => 'ellipsis-horizontal',
            'color' => 'primary',
            'defaultLabel' => '',
        ],
    ];

    $config = $actionConfigs[$action] ?? $actionConfigs['custom'];
    $resolvedIcon = $icon ?? $config['icon'];
    $resolvedColor = $color ?? $config['color'];
    $resolvedLabel = $label ?? $config['defaultLabel'];
    $resolvedTooltip = $tooltip ?? $resolvedLabel;
@endphp

<x-ts-button.circle
    :icon="$resolvedIcon"
    :color="$resolvedColor"
    :outline="$outline && ! $flat"
    :flat="$flat"
    :xs="$size === 'xs'"
    :sm="$size === 'sm'"
    :md="$size === 'md'"
    :href="$href"
    :target="$target"
    :aria-label="$resolvedLabel"
    :tooltip="$resolvedTooltip"
    {{ $attributes }}
/>
