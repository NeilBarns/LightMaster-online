<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite( ['resources/css/app.css', 'resources/js/app.js'])

    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('imgs/lightmaster-icon.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- JQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- UIkit CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/css/uikit.min.css" />

    <!-- UIkit JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/js/uikit.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/uikit@3.20.8/dist/js/uikit-icons.min.js"></script>

    <!-- AG Grid -->
    <script defer src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>

    <!-- Chart.js  -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Fomantic IU -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js">
    </script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css">

</head>

<body class="antialiased flex justify-center flex-col items-center">
    <div class="h-full w-full flex justify-center content-center items-center">
        <div class="shadow-xl shadow-black rounded-sm p-1.5" style="width: 500px; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        border-radius: 0.375rem;">
            <div class="flex justify-center items-center px-4" style="height: 50%">
                {{-- <img style="height: 80%" src="{{ asset('imgs/isabela-state-university-logo.png') }}" alt="logo">
                --}}
                <img style="height: 80%" src="{{ asset('imgs/tempimage.png') }}" alt="logo">
            </div>
            <h4 class="ui divider">
            </h4>
            <div style="height: auto; padding: 5%">
                <form action="{{ route('auth.login') }}" method="POST" class="ui form">
                    @csrf
                    <div class="field">
                        <label>User name</label>
                        <input type="text" name="username" placeholder="User Name">
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Password">
                    </div>
                    <div class="two fields">
                        <div class="field">
                            <div class="ui small checkbox !m-0">
                                <input name="remember" type="checkbox" tabindex="0" class="hidden">
                                <label>Remember me</label>
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui small checkbox !m-0 float-right">
                                <a id="btnForgotPassword">Forgot password?</a>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        @error('failed')
                        <div class="ui negative message">
                            {{ $message }}
                        </div>
                        @enderror
                        <button class="ui small button" type="submit">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="forgotPasswordModal" class="modal-prime" style="display: none">
        <div class="modal-content">
            <span id="btnCloseFPModal" class="close">&times;</span>
            <h4 class="ui horizontal left aligned divider header !mb-11">
                Password reset
            </h4>
            {{-- action="{{ route('verify.email') }}" --}}
            <form method="POST" class="ui form">
                @csrf
                <div class="ui form">
                    <div class="!fields">
                        <div class="fluid required field">
                            <label>Email address</label>
                            <input id="fpEmail" type="text" name="email" placeholder="">
                            @error('email')
                            <div class="ui pointing red basic label">
                                {{-- {{ $message }} --}}
                            </div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="ui visible yellow message">
                    <i class="exclamation circle icon"></i>
                    An email containing a system-generated password will be sent to your email.
                </div>
                <div id="emailNoMatch" class="ui visible error message" hidden>
                    <i class="exclamation circle icon"></i>
                    Unrecognized email
                </div>
                <div class="ui fluid !mt-5">
                    <button id="btnConfirmFP" class="ui fluid small primary button">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>