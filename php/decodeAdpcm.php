<?php
function decodeAdpcm(string $oggFile): string | null
{
    // Validate file
    if (!is_file($oggFile) || strtolower(pathinfo($oggFile, PATHINFO_EXTENSION)) !== 'ogg') {
        throw new InvalidArgumentException("Input file must be an existing .ogg file");
    }

    $dir  = dirname($oggFile);
    $name = basename($oggFile); // e.g. random.ogg
    $baseNameNoExt = pathinfo($name, PATHINFO_FILENAME);

    $step1 = $dir . DIRECTORY_SEPARATOR . $name . "_1.wav";
    $step2 = $dir . DIRECTORY_SEPARATOR . $name . "_2.wav";
    $final = $dir . DIRECTORY_SEPARATOR . $baseNameNoExt . ".wav";

    // Step 1: ima -> wav mono
    $cmd1 = sprintf(
        'sox -t ima -r 8000 -c 1 %s %s',
        escapeshellarg($oggFile),
        escapeshellarg($step1)
    );

    // Step 2: mono -> stereo, trim start
    $cmd2 = sprintf(
        'sox %s -c 2 -r 8000 -b 16 %s trim 0.2',
        escapeshellarg($step1),
        escapeshellarg($step2)
    );

    // Step 3: stereo -> stereo (gain normalization)
    $cmd3 = sprintf(
        'sox %s -c 2 -r 8000 -b 16 %s gain -n',
        escapeshellarg($step2),
        escapeshellarg($final)
    );

    try {
        foreach ([$cmd1, $cmd2, $cmd3] as $cmd) {
            exec($cmd, $output, $ret);
            if ($ret !== 0) {
                return null;
            }
        }
    } finally {
        // cleanup input + temp files
        @unlink($oggFile);
        @unlink($step1);
        @unlink($step2);
    }

    return $final;
}

