@extends('layouts.app')

@section('title', 'UI Component & Style Test')

@section('content')
    <div class="space-y-8">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold text-neutral">UI Component & Style Test</h1>
            <p class="mt-1 text-neutral/60">Halaman ini digunakan untuk memverifikasi semua komponen visual, style, dan JavaScript berjalan normal.</p>
        </div>

        <!-- Colors -->
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Color Palette (Tema: softblue)</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4 mt-4">
                    @foreach(['primary', 'secondary', 'accent', 'neutral', 'base-100', 'info', 'success', 'warning', 'error'] as $color)
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-{{$color}} shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2 capitalize">{{$color}}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Typography -->
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Typography</h2>
                <div class="prose max-w-none mt-4">
                    <h1>Heading 1</h1>
                    <h2>Heading 2</h2>
                    <h3>Heading 3</h3>
                    <p>This is a paragraph of text. It is used to demonstrate the default font, size, and spacing of body text. You can also have <strong>bold text</strong>, <em>italic text</em>, and <a href="#">links</a>.</p>
                    <code>This is a block of code.</code>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Buttons</h2>
                <div class="flex flex-wrap gap-2 items-center mt-4">
                    <button class="btn">Default</button>
                    <button class="btn btn-primary">Primary</button>
                    <button class="btn btn-secondary">Secondary</button>
                    <button class="btn btn-accent">Accent</button>
                    <button class="btn btn-ghost">Ghost</button>
                    <button class="btn btn-link">Link</button>
                    <button class="btn btn-outline">Outline</button>
                    <button class="btn btn-outline btn-primary">Outline Primary</button>
                    <button class="btn btn-primary" disabled>Disabled</button>
                </div>
            </div>
        </div>

        <!-- Forms -->
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Form Elements</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Input Text</span></label>
                        <input type="text" placeholder="Type here" class="input input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Input Error</span></label>
                        <input type="text" placeholder="Type here" class="input input-bordered input-error w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Select</span></label>
                        <select class="select select-bordered">
                            <option disabled selected>Pick one</option>
                            <option>Option 1</option>
                            <option>Option 2</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer"><span class="label-text">Checkbox</span><input type="checkbox" class="checkbox checkbox-primary" /></label>
                        <label class="label cursor-pointer"><span class="label-text">Toggle</span><input type="checkbox" class="toggle toggle-primary" checked /></label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards -->
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Cards</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="card bg-base-200">
                         <div class="card-body items-center text-center">
                            <h2 class="card-title">Simple Card</h2>
                            <p>This is a standard card component.</p>
                         </div>
                    </div>
                     <x-card.stat title="Test Stat Card" value="1,234" change="▲ 5.2% this week" changeType="success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </x-card.stat>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Modal</h2>
                <p class="text-sm mt-2">Klik tombol untuk menguji modal.</p>
                <div class="card-actions justify-start mt-4">
                    <button class="btn btn-primary" onclick="test_modal.showModal()">Buka Modal</button>
                </div>
                <dialog id="test_modal" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-lg">Hello!</h3>
                        <p class="py-4">Jika Anda bisa melihat ini, komponen modal dari DaisyUI bekerja dengan baik.</p>
                        <div class="modal-action">
                            <form method="dialog"><button class="btn">Tutup</button></form>
                        </div>
                    </div>
                </dialog>
            </div>
        </div>

        <!-- Animation Test -->
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Animation (AOS) Test</h2>
                <p class="text-sm mt-2">Kotak di bawah ini seharusnya muncul dengan efek "fade-up". Jika ya, AOS berjalan normal.</p>
                <div class="mt-4 p-8 bg-secondary rounded-lg text-center font-semibold text-primary" data-aos="fade-up">
                    AOS is Working!
                </div>
            </div>
        </div>
    </div>
@endsection
