<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>File Storage</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>

<header class="app-navbar">
  <div class="app-container">
    <a href="{{ route('files.index') }}" class="app-logo">
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
        <rect x="2" y="1" width="10" height="13" rx="1.5" stroke="currentColor" stroke-width="1.5" fill="none"/>
        <path d="M6 5h6M6 8h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M10 1v4h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
      </svg>
      File Storage
    </a>
  </div>
</header>

<main class="app-container app-main">
  @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
@stack('scripts')
</body>
</html>
