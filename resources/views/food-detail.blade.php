<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Detail</title>
</head>
<body>
    <a href="{{ url('/explore') }}">Kembali ke Explore Culinary</a>

    <h1>{{ $food['name'] }}</h1>
    <p><strong>Asal Daerah :</strong> {{ $food['region'] }}</p>
    <img src="{{ asset($food['image']) }}" alt="{{ $food['name'] }}" width="300">
    <p>{{ $food['description'] }}</p>
</body>
</html>