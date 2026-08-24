<?php
declare(strict_types=1);

$source = $argv[1] ?? '';
$target = $argv[2] ?? '';
$geo = json_decode((string) file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
$wanted = [];
foreach ($geo['features'] as $feature) {
    $code = (string) ($feature['properties']['code'] ?? '');
    if (in_array($code, ['39', '69'], true)) $wanted[$code] = $feature;
}

function departmentPath(array $feature, float $x, float $y, float $w, float $h): string {
    $rings = $feature['geometry']['coordinates'];
    if ($feature['geometry']['type'] === 'Polygon') $rings = [$rings];
    $all = [];
    foreach ($rings as $polygon) foreach ($polygon as $ring) foreach ($ring as $point) $all[] = $point;
    $xs = array_column($all, 0); $ys = array_column($all, 1);
    $minX=min($xs); $maxX=max($xs); $minY=min($ys); $maxY=max($ys);
    $scale=min($w/($maxX-$minX),$h/($maxY-$minY));
    $offsetX=$x+($w-($maxX-$minX)*$scale)/2; $offsetY=$y+($h-($maxY-$minY)*$scale)/2;
    $paths=[];
    foreach ($rings as $polygon) foreach ($polygon as $ring) {
        $parts=[];
        foreach ($ring as $i=>$point) {
            $px=$offsetX+($point[0]-$minX)*$scale;
            $py=$offsetY+$h-($point[1]-$minY)*$scale-($h-($maxY-$minY)*$scale)/2;
            $parts[]=($i===0?'M':'L').number_format($px,1,'.','').' '.number_format($py,1,'.','');
        }
        $paths[]=implode(' ',$parts).' Z';
    }
    return implode(' ',$paths);
}

$p39=departmentPath($wanted['39'],40,80,410,520);
$p69=departmentPath($wanted['69'],550,80,410,520);
$svg=<<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 680" role="img" aria-labelledby="title desc">
<title id="title">Zones d’intervention ETS Guillaume : Jura et Rhône</title>
<desc id="desc">Illustration des contours des départements du Jura 39 et du Rhône 69.</desc>
<rect width="1000" height="680" rx="28" fill="#111111"/>
<path d="$p39" fill="#8f1118" stroke="#ffffff" stroke-width="7" stroke-linejoin="round"/>
<path d="$p69" fill="#d21f2b" stroke="#ffffff" stroke-width="7" stroke-linejoin="round"/>
<g fill="#ffffff" text-anchor="middle" font-family="Arial,Helvetica,sans-serif">
  <text x="270" y="318" font-size="96" font-weight="800">39</text><text x="270" y="372" font-size="34" font-weight="700">JURA</text>
  <text x="720" y="318" font-size="96" font-weight="800">69</text><text x="720" y="372" font-size="34" font-weight="700">RHÔNE</text>
  <circle cx="270" cy="465" r="12" fill="#ffffff"/><text x="270" y="502" font-size="22">Petit-Noir</text>
  <circle cx="720" cy="455" r="12" fill="#ffffff"/><text x="720" y="492" font-size="22">Lyon</text>
  <text x="500" y="644" font-size="28" font-weight="700">ETS GUILLAUME • ZONES D’INTERVENTION</text>
</g>
</svg>
SVG;
file_put_contents($target,$svg);
