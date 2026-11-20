<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Culinary</title>
</head>
<body>
    <h1>Explore Culinary Bali</h1>

    @foreach ($foods as $food)
        <div>
            <img src="{{ asset($food['image']) }}" alt="{{ $food['name'] }}" width="200">
            <h2>{{ $food['name'] }}</h2>
            <p><strong>Asal Daerah:</strong> {{ $food['region'] }}</p>
            <p>{{ $food['description'] }}</p>
            <a href="{{ url('/food-detail/' . $food['slug']) }}">Lihat Detail</a>
        </div>
    @endforeach
</body>
</html>