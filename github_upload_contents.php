<?php

#V1 

// Increase memory limit for large repositories
ini_set('memory_limit', '2048M'); // Increase to 2GB
ini_set('max_execution_time', 600); // Increase to 10 minutes
set_time_limit(600);

// GitHub authentication. Set GITHUB_TOKEN in the environment before running.
$token = getenv('GITHUB_TOKEN') ?: '';
if ($token === '') {
    die("Missing required environment variable: GITHUB_TOKEN\n");
}
$username = 'JamesAllgood';
$repo = 'RideSimsCustomCode';
$repoUrl = "https://github.com/$username/$repo.git";

// filepath: /Users/james/Desktop/github push.php

// Change working directory to the repository root (absolute path)
$repoPath = "/kunden/homepages/34/d764919300/htdocs/www_ridesims_com_v3_composer/web/modules/contrib/custom/utility";
chdir($repoPath);

// Configure Git for large repositories
configureGit($repoPath);

// Verify and set up repository remote configuration
function verifyAndSetupRemote($repoPath, $username, $repo, $token) {
    // Check if remote origin exists
    exec('git remote -v', $remoteOutput, $remoteReturn);
    $hasOrigin = false;
    
    foreach ($remoteOutput as $line) {
        if (strpos($line, 'origin') === 0) {
            $hasOrigin = true;
            break;
        }
    }
    
    // If origin doesn't exist or we need to reset it
    if (!$hasOrigin) {
        // First try to add the remote
        echo "Remote 'origin' not found. Adding remote...\n";
        exec("git remote add origin https://$token@github.com/$username/$repo.git 2>&1", $addOutput, $addReturn);
        
        if ($addReturn !== 0) {
            echo "Error adding remote origin: " . implode("\n", $addOutput) . "\n";
            return false;
        }
    } else {
        // Update the existing remote with the correct URL
        echo "Updating remote 'origin' URL...\n";
        exec("git remote set-url origin https://$token@github.com/$username/$repo.git 2>&1", $setUrlOutput, $setUrlReturn);
        
        if ($setUrlReturn !== 0) {
            echo "Error updating remote origin URL: " . implode("\n", $setUrlOutput) . "\n";
            return false;
        }
    }
    
    // Verify remote settings
    exec('git remote -v', $verifyOutput, $verifyReturn);
    echo "Remote configuration:\n" . implode("\n", $verifyOutput) . "\n";
    
    return true;
}

// Call this function early in the script
if (!verifyAndSetupRemote($repoPath, $username, $repo, $token)) {
    echo "Failed to configure repository remote. Exiting.\n";
    exit(1);
}

// Function to check and clean git lock files
function cleanGitLocks($repoPath) {
    $lockFiles = [
        $repoPath . '/.git/index.lock',
        $repoPath . '/.git/refs/heads/*.lock',
        $repoPath . '/.git/HEAD.lock'
    ];
    
    foreach ($lockFiles as $lockFile) {
        $matches = glob($lockFile);
        if ($matches) {
            foreach ($matches as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}

// Add this right after the chdir($repoPath) call
cleanGitLocks($repoPath);

// Always use main branch
$branch = "main";

// Before resetting to main, stash any local changes
exec("git stash push --include-untracked 2>&1", $stashOutput, $stashReturn);

// Reset to main branch and ensure we're up to date, but don't overwrite local changes
exec("git fetch origin main 2>&1", $fetchOutput, $fetchReturn);
exec("git checkout main 2>&1", $checkoutOutput, $checkoutReturn);

// Instead of hard reset, merge changes
exec("git merge origin/main --strategy-option=ours 2>&1", $mergeOutput, $mergeReturn);

// Pop stashed changes back
if ($stashReturn === 0) {
    exec("git stash pop 2>&1", $popOutput, $popReturn);
}

// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if directory exists, if not create it
if (!is_dir($repoPath)) {
    mkdir($repoPath, 0755, true);
    chdir($repoPath);
}

// Check if it's a git repository, if not initialize or clone
if (!is_dir(".git")) {
    // Check if the directory is empty
    if (count(glob("*")) === 0) {
        // Clone the repository
        exec("git clone https://$token@github.com/$username/$repo.git . 2>&1", $cloneOutput, $cloneReturn);
        if ($cloneReturn !== 0) {
            echo "Error cloning repository:\n" . implode("\n", $cloneOutput) . "\n";
            exit(1);
        }
    } else {
        // Initialize new repository
        exec("git init 2>&1", $initOutput, $initReturn);
        if ($initReturn !== 0) {
            echo "Error initializing repository:\n" . implode("\n", $initOutput) . "\n";
            exit(1);
        }
        
        // Add remote
        exec("git remote add origin https://$token@github.com/$username/$repo.git 2>&1", $remoteOutput, $remoteReturn);
        if ($remoteReturn !== 0) {
            echo "Error adding remote:\n" . implode("\n", $remoteOutput) . "\n";
            exit(1);
        }
    }
}

// Update (or create) the .gitignore file to exclude "media", "tempchunks" and MP3 files
$gitignoreFile = '.gitignore';
$exclusions = [
    "media",
    "tempchunks",
    "*.mp3",    // Add MP3 files to exclusions
    "**/*.mp3"  // Also exclude MP3s in subdirectories
];

if (file_exists($gitignoreFile)) {
    $contents = file_get_contents($gitignoreFile);
    foreach ($exclusions as $pattern) {
        if (strpos($contents, $pattern) === false) {
            file_put_contents($gitignoreFile, "$pattern\n", FILE_APPEND);
        }
    }
} else {
    file_put_contents($gitignoreFile, implode("\n", $exclusions) . "\n");
}

// Remove any already tracked MP3 files from git
exec('git ls-files "*.mp3" -z | xargs -0 git rm --cached', $removeOutput, $removeReturn);

// Check git status first
exec('git status 2>&1', $statusOutput, $statusReturn);
if ($statusReturn !== 0) {
    echo "Error checking git status:\n" . implode("\n", $statusOutput) . "\n";
    exit(1);
}

// Execute git commands with error output - Modified to handle large repositories
// First, get a list of changed files
exec('git status --porcelain 2>&1', $changedFiles, $statusReturn);
if ($statusReturn !== 0) {
    echo "Error getting changed files:\n" . implode("\n", $changedFiles) . "\n";
    exit(1);
}

// Configure git user before operations
exec('git config user.name "Automated Script"', $configOutput, $configReturn);
exec('git config user.email "automated@script.com"', $configOutput, $configReturn);

// Make sure we're on the correct branch and it exists
exec("git checkout -B $branch 2>&1", $checkoutOutput, $checkoutReturn);
if ($checkoutReturn !== 0) {
    echo "Error setting up branch:\n" . implode("\n", $checkoutOutput) . "\n";
    exit(1);
}

// Add Git configuration function
function configureGit($repoPath) {
    $config = array(
        'core.compression' => '0',
        'http.postBuffer' => '524288000',
        'http.maxRequestBuffer' => '100M',
        'core.packedGitLimit' => '512m',
        'core.packedGitWindowSize' => '512m',
        'pack.deltaCacheSize' => '512m',
        'pack.packSizeLimit' => '512m',
        'pack.windowMemory' => '512m',
        'pack.threads' => '1',
        'branch.main.remote' => 'origin',
        'branch.main.merge' => 'refs/heads/main'
    );

    foreach ($config as $key => $value) {
        exec(sprintf('cd "%s" && git config %s %s', $repoPath, $key, $value));
    }
}

// Add a function to properly escape file names
function escapeFilename($filename) {
    return escapeshellarg($filename);
}

// Add function to check if a file should be included
function shouldIncludeFile($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $extension === 'php' || !in_array($extension, ['mp3']);
}

// Add this function after the shouldIncludeFile function
function deleteRemovedFiles($repoPath) {
    exec('git ls-files --deleted 2>&1', $deletedFiles, $return);
    
    if ($return === 0 && !empty($deletedFiles)) {
        foreach ($deletedFiles as $file) {
            $file = trim($file);
            if (empty($file)) continue;
            
            $escapedFile = escapeFilename($file);
            exec(sprintf('cd "%s" && git rm %s 2>&1', $repoPath, $escapedFile));
        }
        
        $commitMessage = 'Remove deleted files ' . date('Y-m-d H:i:s');
        exec(sprintf('cd "%s" && git commit -m "%s" 2>&1', $repoPath, $commitMessage));
    }
}

// Add modified files function to explicitly track PHP changes
function addModifiedPhpFiles($repoPath) {
    exec('git diff --name-only --diff-filter=M "*.php" 2>&1', $modifiedFiles, $return);
    foreach ($modifiedFiles as $file) {
        if (file_exists($file)) {
            $escapedFile = escapeFilename($file);
            exec(sprintf('cd "%s" && git add -f %s', $repoPath, $escapedFile));
            echo "Added modified PHP file: **" . $file . "**\n";
        }
    }
    
    exec('git ls-files --others --exclude-standard "*.php" 2>&1', $untrackedFiles, $return);
    foreach ($untrackedFiles as $file) {
        if (file_exists($file)) {
            $escapedFile = escapeFilename($file);
            exec(sprintf('cd "%s" && git add -f %s', $repoPath, $escapedFile));
            echo "Added new PHP file: **" . $file . "**\n";
        }
    }
}

// Call the function before processing batches
addModifiedPhpFiles($repoPath);

// Add this call right before the batches processing loop
deleteRemovedFiles($repoPath);

// Modify the file adding and commit section to handle smaller batches
$batchSize = 5; // Smaller batch size for commits
$totalFiles = count($changedFiles);
$batches = array_chunk($changedFiles, $batchSize);
$batchNumber = 1;

foreach ($batches as $batch) {
    $filesAdded = false;
    $fileNames = [];

    foreach ($batch as $line) {
        $status = substr($line, 0, 2);
        $filename = substr($line, 3);
        $filename = trim($filename);
        
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'php') {
            $escapedFilename = escapeFilename($filename);
            $command = sprintf('cd "%s" && git add -f %s', $repoPath, $escapedFilename);
            exec($command, $addOutput, $addReturn);
            if ($addReturn === 0) {
                echo "Added PHP file: **" . $filename . "**\n";
                $filesAdded = true;
                $fileNames[] = $filename;
            } else {
                echo "Failed to add PHP file: **" . $filename . "**\n";
            }
        }
        
        if (!shouldIncludeFile($filename)) {
            continue;
        }
        
        if ($status === '??' || $status === ' M' || $status === 'A ' || $status === 'M ') {
            $escapedFilename = escapeFilename($filename);
            $command = sprintf('cd "%s" && git add -f %s', $repoPath, $escapedFilename);
            
            exec($command, $addOutput, $addReturn);
            
            if ($addReturn === 0 && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'php') {
                echo "Added file: **" . $filename . "**\n";
                $filesAdded = true;
                $fileNames[] = $filename;
            } elseif ($addReturn !== 0) {
                echo "Failed to add file: **" . $filename . "**\n";
            }
        }
    }
    
    if ($filesAdded) {
        gc_collect_cycles();
        
        $commitMessage = sprintf(
            'Batch %d/%d: Server Commit - %s - Files: %s', 
            $batchNumber, 
            ceil($totalFiles/$batchSize),
            date('Y-m-d H:i:s'),
            implode(', ', $fileNames)
        );
        
        $command = sprintf('cd "%s" && git commit -m "%s" 2>&1', $repoPath, $commitMessage);
        exec($command);
        
        $pushCommand = sprintf(
            'cd "%s" && git push origin main 2>&1',
            $repoPath
        );
        
        gc_collect_cycles();
        exec('git gc --aggressive');
        exec($pushCommand);
        
        sleep(2);
    }
    
    $batchNumber++;
}

// Initialize output arrays
$addOutput = array();
$commitOutput = array();
$pushOutput = array();
$statusOutput = array();

// Final cleanup
exec('git gc --aggressive');

echo "\nAll batches processed successfully!\n";

// Determine the current branch name
$branch = trim(shell_exec("git rev-parse --abbrev-ref HEAD"));
if (empty($branch)) {
    $branch = "main";
}

// Push changes to the current branch and set the upstream if not already set
$pushCommand = sprintf(
    'cd "%s" && git push --verbose --progress --force -u origin %s 2>&1',
    $repoPath,
    $branch
);

// Add memory cleanup before push
gc_collect_cycles();

// Execute push with retries
$maxPushRetries = 3;
$pushRetryCount = 0;
$pushSuccess = false;

while ($pushRetryCount < $maxPushRetries && !$pushSuccess) {
    exec($pushCommand, $pushOutput, $pushReturn);
    
    if ($pushReturn === 0) {
        $pushSuccess = true;
    } else {
        $pushRetryCount++;
        if (strpos(implode("\n", $pushOutput), "out of memory") !== false) {
            // Clean up and wait before retry
            gc_collect_cycles();
            exec('git gc --aggressive');
            sleep(5);
            echo "Retrying push after memory cleanup (attempt $pushRetryCount of $maxPushRetries)...\n";
        } else {
            echo "Error during push:\n" . implode("\n", $pushOutput) . "\n";
            exit(1);
        }
    }
}

// Safely display outputs with array checking
echo "\nStatus Output:";
if (is_array($statusOutput) && !empty($statusOutput)) {
    echo "\n" . implode("\n", $statusOutput);
}

echo "\nAdd Output:";
if (is_array($addOutput) && !empty($addOutput)) {
    echo "\n" . implode("\n", $addOutput);
}

echo "\nCommit Output:";
if (is_array($commitOutput) && !empty($commitOutput)) {
    echo "\n" . implode("\n", $commitOutput);
}

echo "\nPush Output:";
if (is_array($pushOutput) && !empty($pushOutput)) {
    echo "\n" . implode("\n", $pushOutput);
}

if ($pushReturn !== 0) {
    echo "\nError: Git push failed with code **" . $pushReturn . "**\n";
    exit(1);
} else {
    echo "\nPush succeeded!\n";
}
?>

