<?php
$directory = new RecursiveDirectoryIterator('c:/xampp/htdocs/sajib/sajib/main');
$iterator = new RecursiveIteratorIterator($directory);

$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getRealPath());
        $new_content = preg_replace('/^\s*mysqli_close\(\$conn\);\s*$/m', '', $content);
        $new_content = str_replace('mysqli_close($conn);', '', $new_content);
        if ($content !== $new_content) {
            file_put_contents($file->getRealPath(), $new_content);
            echo "Updated " . $file->getRealPath() . "\n";
            $count++;
        }
    }
}
echo "Total files updated: $count\n";
