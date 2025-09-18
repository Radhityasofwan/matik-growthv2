<!DOCTYPE html>
<html lang="en" data-theme="night">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Matik Growth Up</title>
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            /* NOTE: Pastikan gambar Anda ada di `storage/public/images/background.jpg` 
               dan Anda sudah menjalankan `php artisan storage:link`. 
            */
            background-image: url("storage/images/bg-login.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        
        <!-- Logo and Brand Name -->
        <div class="flex flex-col items-center justify-center mb-8 text-white drop-shadow-lg">
            <img src="/storage/images/logo-matik.png" alt="Matik Logo" class="h-20 w-auto">
            <span class="text-4xl font-extrabold tracking-tight mt-2">growth up</span>
        </div>

        <!-- Glassmorphism Card -->
        <div class="w-full max-w-md p-8 space-y-6 bg-black/30 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20">
            <div>
                <h1 class="text-3xl font-bold text-white text-center">
                    Welcome Back! 👋
                </h1>
                <p class="text-center text-gray-300 mt-2">
                    Sign in to continue your journey.
                </p>
            </div>

            <!-- Session Status & Validation Errors -->
            @if (session('status'))
                <div role="alert" class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div role="alert" class="alert alert-error">
                     <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <div>
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form class="space-y-6" method="POST" action="{{ route('login') }}">
                @csrf
                
                <!-- Email Input -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-gray-300">Email</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                        </span>
                        <input type="email" name="email" id="email" placeholder="your@email.com" class="input input-bordered w-full pl-10 bg-black/20" required value="{{ old('email') }}" />
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-control">
                    <label class="label">
                        <span class="label-text text-gray-300">Password</span>
                    </label>
                    <div class="relative">
                         <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input type="password" name="password" id="password" placeholder="••••••••" class="input input-bordered w-full pl-10 bg-black/20" required />
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-sm">
                    <label class="label cursor-pointer justify-start gap-2 p-0">
                        <input type="checkbox" id="remember" name="remember" class="checkbox checkbox-info" />
                        <span class="label-text text-gray-300">Remember me</span> 
                    </label>
                    {{-- Ganti '#' dengan route('password.request') jika ada --}}
                    <a href="#" class="font-medium text-sky-400 hover:text-sky-300">Forgot Password?</a>
                </div>

                <!-- Submit Button -->
                <div class="form-control pt-4">
                    <button type="submit" class="btn btn-primary w-full">Sign in</button>
                </div>
            </form>

            <div class="text-center text-sm text-gray-400">
                {{-- Ganti '#' dengan route('register') jika ada --}}
                Don't have an account? <a href="#" class="font-medium text-sky-400 hover:text-sky-300">Sign up</a>
            </div>
        </div>
    </div>
</body>
</html>

