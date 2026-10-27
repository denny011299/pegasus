@php
    $id = $id ?? 'esign-pad';
    $hint = $hint ?? 'Gambar tanda tangan di kotak. Digunakan saat menyetujui dokumen.';
@endphp
<div class="{{ $class ?? '' }}">
    <div id="{{ $id }}" class="pg-esign" data-esign-pad></div>
</div>
