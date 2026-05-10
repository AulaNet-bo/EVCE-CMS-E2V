<?php
$file = 'app/Filament/Resources/StationResource.php';
$content = file_get_contents($file);

// Fix the last_heartbeat column to handle objects
$oldLine = "Tables\Columns\TextColumn::make('last_heartbeat')->dateTime()->sortable(),";
$newLine = "Tables\Columns\TextColumn::make('last_heartbeat')->formatStateUsing(fn(\$state)=>is_object(\$state)?null:\$state)->dateTime()->sortable(),";

if (strpos($content, 'formatStateUsing') === false) {
    $content = str_replace($oldLine, $newLine, $content);
}

// Enable all fields (remove ->disabled())
$content = str_replace('->disabled()', '', $content);

file_put_contents($file, $content);
echo "StationResource Fixed successfully.\n";
