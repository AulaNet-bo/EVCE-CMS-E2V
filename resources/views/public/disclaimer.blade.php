<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso Legal - {{ $settings->platform_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $settings->font_family) }}:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-color:
                {{ $settings->primary_color }}
            ;
            --text-color:
                {{ $settings->text_color }}
            ;
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --font-family: '{{ $settings->font_family }}', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 60px auto;
            padding: 40px;
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 2.5rem;
            margin: 0;
        }

        .content {
            white-space: pre-line;
            font-size: 1.1rem;
            color: #4b5563;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 0.875rem;
            color: #9ca3af;
        }

        @media (max-width: 640px) {
            .container {
                margin: 20px;
                padding: 24px;
            }

            h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Aviso Legal</h1>
            <p>{{ $settings->platform_name }}</p>
        </div>

        <div class="content">
            {{ $settings->disclaimer_text }}
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $settings->platform_name }}. Todos los derechos reservados.
        </div>
    </div>
</body>

</html>