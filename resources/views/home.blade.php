@extends('layouts.app')

@section('content')
    <span class="badge">Dummy test site</span>
    <h1>Hello from Laravel 👋</h1>
    <p class="lead">
        This is a simple placeholder website used to confirm that your VPS,
        PHP, and web server are all wired up correctly. If you can read this
        page from your server, the deployment works.
    </p>

    <div class="card">
        <h3 style="margin-top:0;">Environment</h3>
        <ul class="clean">
            @foreach ($features as $feature)
                <li>{{ $feature }}</li>
            @endforeach
        </ul>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Quick links</h3>
        <ul class="clean">
            <li><a href="{{ route('about') }}" style="color:#ff8a80;">About page</a> &mdash; another simple route</li>
            <li><a href="{{ route('health') }}" target="_blank" style="color:#ff8a80;">/health</a> &mdash; JSON status endpoint for monitoring</li>
        </ul>
    </div>
@endsection
