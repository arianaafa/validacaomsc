<x-mail::layout>
<x-slot:header>
<x-mail::header :url="rtrim((string) config('leads.frontend_url', config('app.url')), '/')">
Audita MSC
</x-mail::header>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} **Audita MSC** · Aura Tech. Todos os direitos reservados.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
