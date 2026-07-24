<?php
$src_dir = __DIR__ . '/../../Eternal-Lore';
$dst_dir = __DIR__ . '/../docs/lore';

if (!is_dir($dst_dir)) {
    mkdir($dst_dir, 0777, true);
}

copy("{$src_dir}/LORE-MASTER-OFICIAL.md", "{$dst_dir}/LORE-MASTER-OFICIAL.md");
copy("{$src_dir}/manifest_lore.json", "{$dst_dir}/manifest_lore.json");

echo "Copied Lore files to docs/lore/ inside repository.\n";
