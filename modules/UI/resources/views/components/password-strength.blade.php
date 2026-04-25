@props([
    'password' => '',
])

@if(!empty($password))
    @php
        $score = 0;
        if (strlen($password) >= 8) $score++;
        if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/\d/', $password)) $score++;
        if (preg_match('/[^a-zA-Z\d]/', $password)) $score++;

        $color = match($score) {
            0 => 'bg-base-300',
            1 => 'bg-error',
            2 => 'bg-warning',
            3 => 'bg-info',
            4 => 'bg-success',
            default => 'bg-base-300',
        };

        $label = match($score) {
            0 => 'Too short',
            1 => 'Weak',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Strong',
            default => '',
        };
    @endphp

    <div {{ $attributes->merge(['class' => 'mt-2 space-y-1.5']) }}>
        <div class="flex h-1 w-full overflow-hidden rounded-full bg-base-300">
            @for ($i = 1; $i <= 4; $i++)
                <div @class([
                    'h-full flex-1 transition-all duration-500 border-r border-base-100 last:border-0',
                    $color => $score >= $i,
                    'bg-transparent' => $score < $i,
                ])></div>
            @endfor
        </div>
        <div class="flex justify-between items-center text-[10px] uppercase tracking-wider font-bold">
            <span class="text-base-content/40">Strength</span>
            <span @class([
                'transition-colors duration-500',
                str_replace('bg-', 'text-', $color)
            ])>{{ $label }}</span>
        </div>
    </div>
@endif
