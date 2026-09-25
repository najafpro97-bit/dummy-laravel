@extends('layouts.app')

@section('content')
    <h1>About this test site</h1>
    <p class="lead">
        This Laravel application was generated as a dummy project for testing
        on a personal VPS. It has no database dependencies beyond the default
        SQLite setup and no external services.
    </p>

    <div class="card">
        <ul class="clean">
            <li><strong>Framework:</strong> Laravel {{ app()->version() }}</li>
            <li><strong>PHP:</strong> {{ PHP_VERSION }}</li>
            <li><strong>Environment:</strong> {{ app()->environment() }}</li>
            <li><strong>Debug mode:</strong> {{ config('app.debug') ? 'enabled' : 'disabled' }}</li>
            <li><strong>Timezone:</strong> {{ config('app.timezone') }}</li>
        </ul>
    </div>

    <p class="lead" style="margin-top:24px;">
        Back to <a href="{{ route('home') }}" style="color:#ff8a80;">home</a>.
    </p>
@endsection
