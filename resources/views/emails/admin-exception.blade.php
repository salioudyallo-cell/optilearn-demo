Une exception non gérée est survenue sur {{ config('brand.name') }}.

Type    : {{ $exceptionClass }}
Message : {{ $exceptionMessage }}
Origine : {{ $location }}
Contexte: {{ $context }}
Date    : {{ $occurredAt }}

--- Trace ---
{{ $trace }}
