{{-- Menggunakan object-contain untuk memastikan rasio aspek logo selalu benar --}}
<img src="{{ asset('/storage/images/logo-matik.png') }}" alt="Matik Logo" {{ $attributes->merge(['class' => 'h-full w-auto object-contain']) }}>

