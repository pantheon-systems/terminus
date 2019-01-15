<?php

// Terminus Installer Script
$pathcommand = false;
$pathupdate = false;
$paths = explode(":", getenv('PATH'));             // Creates an array with all paths in $PATH
$installdir = (getenv('HOME') . "/.terminus/bin"); // Creates a string with the desired installation path
$rcfiles = array(                                  // Array of common .rc files to look for.
  ".bashrc",
  ".zshrc",
  ".config/fish/config.fish",
  ".profile",
);


if (!in_array($installdir, $paths)) {              // Searches for ~/.terminus/bin within the existing $PATH
    $pathcommand = true;                           // If it isn't there, we'll execute commands later to add it.
    print_r("Creating " . $installdir . "/\n");
    mkdir($installdir, 0700, true);                // Makes the directory
}


// Build $url from which to download terminus
$opts = [
        'http' => [
          'method' => 'GET',
          'header' => [
            'User-Agent: PHP'
          ]
        ]
];
$context  = stream_context_create($opts);
$package  = "updatinate"; // Temporarily set to another Repo for testing
$releases = file_get_contents("https://api.github.com/repos/pantheon-systems/" . $package . "/releases", false, $context);
$releases = json_decode($releases);
$version  = $releases[0]->tag_name;
$url      = $releases[0]->assets[0]->browser_download_url; //  Currently broken, awaiting release phar to point to.


// Download Terminus
print_r("\nDownloading Terminus " . $version . " from " . $url . "\n");
// Defines a function to download Terminus, which throws an exception on failure.
function downloadterminus()
{
    global $installdir;
    global $url;
    global $package;

    if (false === file_put_contents($installdir . "/" . $package . ".phar", file_get_contents($url))) {
        throw new Exception('Unable to download Terminus.');
    }
}
// Call the function, and handle the exception.
try{
    echo downloadterminus() . "\n";
} catch (Exception $e){
    echo "\n \n";
    echo $e->getMessage(), "\n \n";
    exit(1);
}


// Make Terminus executable
print_r("Making Terminus executable... \n\n");
chmod($installdir . "/" . $package . ".phar", 0755)
  or exit("\nUnable to set Terminus as executable.\n");

// If the installation directory wasn't found in $PATH earlier, define a function to add it to common shell conf files.
if ($pathcommand == true) {
    function ammendpath($rcfile)
    {
        global $installdir;
        global $pathupdate;
        $pathupdate = file_put_contents(getenv('HOME') . "/$rcfile", "# Adds Terminus to \$PATH\nPATH=\$PATH:" . $installdir . "\n\n", FILE_APPEND | LOCK_EX)
        if (!$pathupdate) {
            throw new Exception($rcfile . " found, but unable to write to it.");
        }
    }

    // Iterates through common shell configuration file possibilities to write to.
    foreach ($rcfiles as $rcfile){
        if (file_exists(getenv('HOME') . "/$rcfile")) {
            try{
                ammendpath($rcfile);
                print_r("Found " . $rcfile . " and added " . $installdir . " to your \$PATH.\nIn order to run Terminus, you must first run:\n\nsource ~/" . $rcfile . "\n");
            }
            catch (Exception $e){
                echo "\n \n" . $e->getMessage(), "\n \n";
            }
        }
    }
    // If no configuration file was updated to amend $PATH, this lets the user know.
    if (!$pathcommand){
        print_r("Terminus has been installed to " . $installdir . " But no suitable configuration file was found to update \$PATH.\n\nYou must manually add " . $installdir . " to your PATH, or execute Terminus from the full path.");
    }

}
// If the installation directory was found in $PATH, exit with this message.
else{
    print_r("Terminus successfully installed.\n");
}
exit();
?>



