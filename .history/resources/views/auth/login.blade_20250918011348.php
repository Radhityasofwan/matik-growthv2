<!DOCTYPE html>
<html lang="en" data-theme="night">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Matik Growth Hub</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        
        <a href="#" class="flex items-center mb-6 text-3xl font-semibold text-white drop-shadow-lg">
            Matik Growth Hub
        </a>

        <div class="card w-full max-w-md bg-base-100 bg-opacity-10 backdrop-blur-md shadow-2xl">
            <div class="card-body">
                <h1 class="card-title text-2xl justify-center text-base-content">
                    Sign in to your account
                </h1>

                @if (session('status'))
                    <div role="alert" class="alert alert-success mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="alert alert-error mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <h3 class="font-bold">Whoops! Something went wrong.</h3>
                            <ul class="mt-1 list-disc list-inside text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif


                <form class="space-y-4 mt-4" method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Your email</span>
                        </label>
                        <input type="email" name="email" id="email" placeholder="name@company.com" class="input input-bordered w-full" required value="{{ old('email') }}" />
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Password</span>
                        </label>
                        <input type="password" name="password" id="password" placeholder="••••••••" class="input input-bordered w-full" required />
                    </div>

                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" id="remember" name="remember" class="checkbox checkbox-primary" />
                            <span class="label-text">Remember me</span> 
                        </label>
                    </div>

                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary w-full">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>