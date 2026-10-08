<x-mail::message>
# Neue Treffer gefunden

Ihr Suchagent **"{{ $searchAgent->title !== '' ? $searchAgent->title : 'Suchagent' }}"** hat {{ $matchCount }} {{ $matchCount === 1 ? 'neuen passenden Treffer' : 'neue passende Treffer' }} gefunden.

<x-mail::button :url="$resultsUrl">
Treffer ansehen
</x-mail::button>

Viele Grüße,<br>
{{ config('app.name') }}
</x-mail::message>
