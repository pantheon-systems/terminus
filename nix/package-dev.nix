{ pkgs, ... }:
let
  fs = pkgs.lib.fileset;
  sourceFiles = ../.;
in
fs.trace sourceFiles

pkgs.php.buildComposerProject2 {
  pname = "terminus-dev";
  name = "terminus-dev";
  version = "4.3.3-dev";
  composerNoDev = false;
  src = fs.toSource {
    root = ../.;
    fileset = sourceFiles;
  };
  meta = {
    mainProgram = "terminus";
  };
  vendorHash = "sha256-TXlMt+EIqZuVR2BuPiNXc1vqVJeQrJn8V/bHe/cQq4I=";
}
