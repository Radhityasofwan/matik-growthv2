@extends('layouts.app')

@section('title', 'UI Component & Style Test')

@section('content')
    {{-- Bungkus dengan theme DaisyUI agar pasti memakai tema "softblue" --}}
    <section data-theme="softblue" class="space-y-8">
        {{-- Header --}}
        <div>
            <h1 class="text-3xl font-bold text-neutral">UI Component & Style Test</h1>
            <p class="mt-1 text-neutral/60">
                Halaman ini digunakan untuk memverifikasi semua komponen visual, style, dan JavaScript berjalan normal.
            </p>
        </div>

        {{-- Colors --}}
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Color Palette (Tema: softblue)</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-10 gap-4 mt-4">
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-primary shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">primary</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-secondary shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">secondary</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-accent shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">accent</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-neutral shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">neutral</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-base-100 shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">base-100</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-base-200 shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">base-200</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-base-300 shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">base-300</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-info shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">info</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-success shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">success</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-warning shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">warning</p>
                    </div>
                    <div class="text-center">
                        <div class="w-full h-16 rounded-lg bg-error shadow-inner border border-black/10"></div>
                        <p class="text-sm font-medium mt-2">error</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Typography --}}
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Typography</h2>
                <div class="prose max-w-none mt-4">
                    <h1>Heading 1</h1>
                    <h2>Heading 2</h2>
                    <h3>Heading 3</h3>
                    <p>
                        This is a paragraph of text to demonstrate default body styles.
                        You can also have <strong>bold</strong>, <em>italic</em>, and <a href="#">links</a>.
                    </p>
                    <pre><code>const hello = 'world'</code></pre>
                    <ul>
                        <li>Item 1</li><li>Item 2</li><li>Item 3</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Buttons --}}
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

        {{-- Forms --}}
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
                        <label class="label cursor-pointer">
                            <span class="label-text">Checkbox</span>
                            <input type="checkbox" class="checkbox checkbox-primary" />
                        </label>
                        <label class="label cursor-pointer">
                            <span class="label-text">Toggle</span>
                            <input type="checkbox" class="toggle toggle-primary" checked />
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cards --}}
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Cards</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="card bg-base-200">
                        <div class="card-body items-center text-center">
                            <h3 class="card-title">Simple Card</h3>
                            <p>This is a standard card component.</p>
                        </div>
                    </div>

                    <x-card.stat title="Test Stat Card" value="1,234" change="▲ 5.2% this week" changeType="success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </x-card.stat>
                </div>
            </div>
        </div>

        {{-- Alerts + Badges (cek utilitas warna teks/bg) --}}
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Alerts & Badges</h2>
                <div class="flex flex-col gap-3 mt-3">
                    <div role="alert" class="alert alert-info">
                        <span>Info alert — contoh styling info.</span>
                    </div>
                    <div role="alert" class="alert alert-success">
                        <span>Success alert — berhasil!</span>
                    </div>
                    <div role="alert" class="alert alert-warning">
                        <span>Warning alert — perhatikan ini.</span>
                    </div>
                    <div role="alert" class="alert alert-error">
                        <span>Error alert — ada masalah.</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <div class="badge">Default</div>
                        <div class="badge badge-primary">Primary</div>
                        <div class="badge badge-secondary">Secondary</div>
                        <div class="badge badge-accent">Accent</div>
                        <div class="badge badge-outline">Outline</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal --}}
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Modal</h2>
                <p class="text-sm mt-2">Klik tombol untuk menguji modal.</p>
                <div class="card-actions justify-start mt-4">
                    <button class="btn btn-primary" onclick="document.getElementById('test_modal').showModal()">Buka Modal</button>
                </div>
                <dialog id="test_modal" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-lg">Hello!</h3>
                        <p class="py-4">Jika ini tampil, komponen modal DaisyUI bekerja.</p>
                        <div class="modal-action">
                            <form method="dialog"><button class="btn">Tutup</button></form>
                        </div>
                    </div>
                </dialog>
            </div>
        </div>

        {{-- Animation Test (AOS) --}}
        <div class="card bg-base-100 shadow-sm border border-base-300/50">
            <div class="card-body">
                <h2 class="card-title">Animation (AOS) Test</h2>
                <p class="text-sm mt-2">Kotak di bawah harus muncul dengan efek "fade-up".</p>
                <div class="mt-4 p-8 bg-secondary rounded-lg text-center font-semibold text-primary" data-aos="fade-up">
                    AOS is Working!
                </div>
            </div>
        </div>
    </section>
@endsection
