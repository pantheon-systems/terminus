{ pkgs, ... }:
let
  fs = pkgs.lib.fileset;
  sourceFiles = ../.;
in
fs.trace sourceFiles

pkgs.php.buildComposerProject2 {
  pname = "terminus";
  name = "terminus";
  version = "4.3.3";
  src = fs.toSource {
    root = ../.;
    fileset = sourceFiles;
  };
  meta = {
    mainProgram = "terminus";
  };
  vendorHash = "sha256-ErnhMqaKAYYIMybDEAZKrRNekT2gLLbe3SWJlJR80Ts=";
}
